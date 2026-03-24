package httpserver

import (
	"encoding/json"
	"net/http"
	"strings"
	"time"

	"family-meal-planning/meal-planning/internal/plan"
)

type Server struct {
	internalAuth string
	store        *plan.Store
}

func New(internalAuth string, store *plan.Store) *Server {
	return &Server{internalAuth: internalAuth, store: store}
}

func (s *Server) Handler() http.Handler {
	mux := http.NewServeMux()
	mux.HandleFunc("/api/planning/v1/health", s.health)
	mux.HandleFunc("/api/planning/v1/week-plans/current", s.weekCurrent)
	mux.HandleFunc("/api/planning/v1/assignments/plan", s.assignmentsPlan)
	mux.HandleFunc("/api/planning/v1/slots/", s.slotsPrefix)
	return s.withInternalAuth(mux)
}

func (s *Server) health(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		w.WriteHeader(http.StatusMethodNotAllowed)
		return
	}
	writeJSON(w, 200, map[string]any{"status": "ok", "checks": map[string]string{}})
}

func (s *Server) weekCurrent(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		w.WriteHeader(http.StatusMethodNotAllowed)
		return
	}
	userID := r.Header.Get("X-User-Id")
	anchor := r.URL.Query().Get("anchorDate")
	if anchor == "" {
		writeErr(w, 400, "VALIDATION_ERROR", "anchorDate is required.")
		return
	}
	t, err := plan.ParseDate(anchor)
	if err != nil {
		writeErr(w, 400, "VALIDATION_ERROR", "Invalid anchorDate.")
		return
	}
	ws, we := plan.WeekRange(t)
	ctx := r.Context()
	if err := s.store.EnsureSlots(ctx, userID, ws, we); err != nil {
		writeErr(w, 500, "INTERNAL_ERROR", err.Error())
		return
	}
	rows, err := s.store.ListWeek(ctx, userID, ws, we)
	if err != nil {
		writeErr(w, 500, "INTERNAL_ERROR", err.Error())
		return
	}
	resp := buildWeekResponse(ws, we, rows, r.URL.Query().Get("recipeSearch"))
	writeJSON(w, 200, resp)
}

func (s *Server) assignmentsPlan(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		w.WriteHeader(http.StatusMethodNotAllowed)
		return
	}
	userID := r.Header.Get("X-User-Id")
	from := r.URL.Query().Get("from")
	to := r.URL.Query().Get("to")
	if from == "" || to == "" {
		writeErr(w, 400, "VALIDATION_ERROR", "from and to are required.")
		return
	}
	items, err := s.store.AssignmentsInRange(r.Context(), userID, from, to)
	if err != nil {
		writeErr(w, 500, "INTERNAL_ERROR", err.Error())
		return
	}
	writeJSON(w, 200, map[string]any{"items": items})
}

func (s *Server) slotsPrefix(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPatch {
		w.WriteHeader(http.StatusMethodNotAllowed)
		return
	}
	const p = "/api/planning/v1/slots/"
	if !strings.HasPrefix(r.URL.Path, p) {
		http.NotFound(w, r)
		return
	}
	slotID := strings.TrimSuffix(strings.TrimPrefix(r.URL.Path, p), "/")
	if slotID == "" {
		http.NotFound(w, r)
		return
	}
	userID := r.Header.Get("X-User-Id")
	var body struct {
		RecipeIDs       []string `json:"recipeIds"`
		ExpectedVersion *int     `json:"expectedVersion"`
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil {
		writeErr(w, 400, "VALIDATION_ERROR", "Invalid JSON body.")
		return
	}
	if err := plan.ValidateRecipeUUIDs(body.RecipeIDs); err != nil {
		writeErr(w, 400, "VALIDATION_ERROR", err.Error())
		return
	}
	row, conflict, err := s.store.PatchSlot(r.Context(), userID, slotID, body.RecipeIDs, body.ExpectedVersion)
	if err != nil {
		writeErr(w, 500, "INTERNAL_ERROR", err.Error())
		return
	}
	if conflict {
		writeErr(w, 400, "VERSION_CONFLICT", "expectedVersion does not match current version.")
		return
	}
	if row == nil {
		writeErr(w, 404, "NOT_FOUND", "Slot not found.")
		return
	}
	writeJSON(w, 200, slotToAssignment(*row))
}

func (s *Server) withInternalAuth(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path == "/api/planning/v1/health" {
			next.ServeHTTP(w, r)
			return
		}
		token := r.Header.Get("X-Internal-Auth")
		if token == "" || token != s.internalAuth {
			writeErr(w, 401, "INTERNAL_AUTH_FAILED", "Missing or invalid internal auth token.")
			return
		}
		uid := r.Header.Get("X-User-Id")
		if uid == "" || !isUUIDLike(uid) {
			writeErr(w, 401, "USER_REQUIRED", "Missing or invalid X-User-Id.")
			return
		}
		next.ServeHTTP(w, r)
	})
}

func buildWeekResponse(ws, we time.Time, rows []plan.SlotRow, recipeSearch string) map[string]any {
	byDate := make(map[string][]plan.SlotRow)
	for _, row := range rows {
		byDate[row.Date] = append(byDate[row.Date], row)
	}
	order := make(map[string]int)
	for i, c := range plan.SlotCodes {
		order[c] = i
	}
	var days []map[string]any
	for d := ws; !d.After(we); d = d.AddDate(0, 0, 1) {
		ds := plan.DateString(d)
		slots := byDate[ds]
		// stable slot order
		for i := 0; i < len(slots); i++ {
			for j := i + 1; j < len(slots); j++ {
				if order[slots[j].SlotCode] < order[slots[i].SlotCode] {
					slots[i], slots[j] = slots[j], slots[i]
				}
			}
		}
		var slotObjs []map[string]any
		for _, sr := range slots {
			slotObjs = append(slotObjs, slotToAssignment(sr))
		}
		days = append(days, map[string]any{
			"date":  ds,
			"slots": slotObjs,
		})
	}
	out := map[string]any{
		"weekStart": plan.DateString(ws),
		"weekEnd":   plan.DateString(we),
		"days":      days,
	}
	if recipeSearch != "" {
		out["recipeSearchHint"] = recipeSearch
	}
	return out
}

func slotToAssignment(sr plan.SlotRow) map[string]any {
	return map[string]any{
		"slotId":    sr.ID,
		"date":      sr.Date,
		"slotCode":  sr.SlotCode,
		"recipeIds": sr.RecipeIDs,
		"version":   sr.Version,
	}
}

func isUUIDLike(s string) bool {
	if len(s) != 36 {
		return false
	}
	for i, c := range s {
		if i == 8 || i == 13 || i == 18 || i == 23 {
			if c != '-' {
				return false
			}
			continue
		}
		if c >= '0' && c <= '9' || c >= 'a' && c <= 'f' || c >= 'A' && c <= 'F' {
			continue
		}
		return false
	}
	return strings.Count(s, "-") == 4
}

func writeJSON(w http.ResponseWriter, status int, v any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(v)
}

func writeErr(w http.ResponseWriter, status int, code, message string) {
	writeJSON(w, status, map[string]any{"code": code, "message": message})
}

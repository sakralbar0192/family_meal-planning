package httpserver

import (
	"encoding/json"
	"net/http"
	"strings"

	"family-meal-planning/recipe-import/internal/config"
	"family-meal-planning/recipe-import/internal/importer"
)

type Server struct {
	cfg     config.Config
	import_ *importer.Service
}

func New(cfg config.Config, svc *importer.Service) *Server {
	return &Server{cfg: cfg, import_: svc}
}

func (s *Server) Handler() http.Handler {
	mux := http.NewServeMux()
	mux.HandleFunc("/api/import/v1/health", s.health)
	mux.HandleFunc("/api/import/v1/imports/url", s.importURL)
	return s.withInternalAuth(mux)
}

func (s *Server) health(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		w.WriteHeader(http.StatusMethodNotAllowed)
		return
	}
	writeJSON(w, 200, map[string]any{
		"status": "ok",
		"checks": map[string]string{},
	})
}

func (s *Server) importURL(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		w.WriteHeader(http.StatusMethodNotAllowed)
		return
	}
	var body struct {
		URL string `json:"url"`
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil || body.URL == "" {
		writeErr(w, 400, "VALIDATION_ERROR", "Request body must contain url.")
		return
	}
	draft, status, code, msg := s.import_.ImportByURL(r.Context(), body.URL)
	if status != 200 {
		writeErr(w, status, code, msg)
		return
	}
	writeJSON(w, 200, draft)
}

func (s *Server) withInternalAuth(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path == "/api/import/v1/health" {
			next.ServeHTTP(w, r)
			return
		}
		token := r.Header.Get("X-Internal-Auth")
		if token == "" || token != s.cfg.InternalAuth {
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

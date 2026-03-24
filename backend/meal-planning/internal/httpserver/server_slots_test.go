package httpserver

import (
	"bytes"
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"family-meal-planning/meal-planning/internal/plan"

	"github.com/DATA-DOG/go-sqlmock"
)

func TestPatchSlotHTTP_VersionConflict(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	slotID := "550e8400-e29b-41d4-a716-446655440001"
	userID := "550e8400-e29b-41d4-a716-446655440002"
	recipeID := "550e8400-e29b-41d4-a716-446655440099"
	ev := 1

	mock.ExpectBegin()
	mock.ExpectQuery(`SELECT version, recipe_ids::text, slot_date::text, slot_code FROM planning_slots WHERE id = \$1 AND user_id = \$2 FOR UPDATE`).
		WithArgs(slotID, userID).
		WillReturnRows(sqlmock.NewRows([]string{"version", "recipe_ids", "slot_date", "slot_code"}).
			AddRow(2, "[]", "2026-03-02", "BREAKFAST"))
	mock.ExpectRollback()

	store := &plan.Store{DB: db}
	srv := New("dev-token", store)

	body := map[string]any{
		"recipeIds":       []string{recipeID},
		"expectedVersion": ev,
	}
	raw, err := json.Marshal(body)
	if err != nil {
		t.Fatal(err)
	}

	req := httptest.NewRequest(
		http.MethodPatch,
		"/api/planning/v1/slots/"+slotID,
		bytes.NewReader(raw),
	)
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-Internal-Auth", "dev-token")
	req.Header.Set("X-User-Id", userID)
	req = req.WithContext(context.Background())

	rec := httptest.NewRecorder()
	srv.Handler().ServeHTTP(rec, req)

	if rec.Code != http.StatusBadRequest {
		t.Fatalf("status %d, body %s", rec.Code, rec.Body.String())
	}
	var msg map[string]any
	if err := json.Unmarshal(rec.Body.Bytes(), &msg); err != nil {
		t.Fatal(err)
	}
	if msg["code"] != "VERSION_CONFLICT" {
		t.Fatalf("body %+v", msg)
	}
	if err := mock.ExpectationsWereMet(); err != nil {
		t.Fatal(err)
	}
}

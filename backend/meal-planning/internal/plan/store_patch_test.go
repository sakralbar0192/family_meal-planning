package plan

import (
	"context"
	"testing"

	"github.com/DATA-DOG/go-sqlmock"
)

func TestPatchSlot_VersionConflict(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatal(err)
	}
	defer db.Close()

	store := &Store{DB: db}
	ctx := context.Background()
	ev := 1
	slotID := "550e8400-e29b-41d4-a716-446655440001"
	userID := "550e8400-e29b-41d4-a716-446655440002"

	mock.ExpectBegin()
	mock.ExpectQuery(`SELECT version, recipe_ids::text, slot_date::text, slot_code FROM planning_slots WHERE id = \$1 AND user_id = \$2 FOR UPDATE`).
		WithArgs(slotID, userID).
		WillReturnRows(sqlmock.NewRows([]string{"version", "recipe_ids", "slot_date", "slot_code"}).
			AddRow(2, "[]", "2026-03-02", "BREAKFAST"))
	mock.ExpectRollback()

	row, conflict, err := store.PatchSlot(ctx, userID, slotID, []string{"550e8400-e29b-41d4-a716-446655440099"}, &ev)
	if err != nil {
		t.Fatal(err)
	}
	if !conflict {
		t.Fatal("expected version conflict")
	}
	if row != nil {
		t.Fatal("expected nil row on conflict")
	}
	if err := mock.ExpectationsWereMet(); err != nil {
		t.Fatal(err)
	}
}

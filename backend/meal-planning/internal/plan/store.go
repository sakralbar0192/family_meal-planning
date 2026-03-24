package plan

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"time"

	"github.com/google/uuid"
)

type Store struct {
	DB *sql.DB
}

func (s *Store) EnsureSlots(ctx context.Context, userID string, weekStart, weekEnd time.Time) error {
	for d := weekStart; !d.After(weekEnd); d = d.AddDate(0, 0, 1) {
		ds := DateString(d)
		for _, code := range SlotCodes {
			id := uuid.New()
			_, _ = s.DB.ExecContext(ctx, `
INSERT INTO planning_slots (id, user_id, slot_date, slot_code, recipe_ids, version)
VALUES ($1, $2, $3::date, $4, '[]', 1)
ON CONFLICT (user_id, slot_date, slot_code) DO NOTHING`,
				id.String(), userID, ds, code)
		}
	}
	return nil
}

type SlotRow struct {
	ID        string
	Date      string
	SlotCode  string
	RecipeIDs []string
	Version   int
}

func (s *Store) ListWeek(ctx context.Context, userID string, weekStart, weekEnd time.Time) ([]SlotRow, error) {
	rows, err := s.DB.QueryContext(ctx, `
SELECT id, slot_date::text, slot_code, recipe_ids::text, version
FROM planning_slots
WHERE user_id = $1 AND slot_date >= $2::date AND slot_date <= $3::date
ORDER BY slot_date, slot_code`,
		userID, DateString(weekStart), DateString(weekEnd))
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var out []SlotRow
	for rows.Next() {
		var r SlotRow
		var idsJSON string
		if err := rows.Scan(&r.ID, &r.Date, &r.SlotCode, &idsJSON, &r.Version); err != nil {
			return nil, err
		}
		if err := json.Unmarshal([]byte(idsJSON), &r.RecipeIDs); err != nil {
			return nil, err
		}
		out = append(out, r)
	}
	return out, rows.Err()
}

func (s *Store) GetSlot(ctx context.Context, userID, slotID string) (*SlotRow, error) {
	var r SlotRow
	var idsJSON string
	err := s.DB.QueryRowContext(ctx, `
SELECT id, slot_date::text, slot_code, recipe_ids::text, version
FROM planning_slots WHERE id = $1 AND user_id = $2`, slotID, userID).
		Scan(&r.ID, &r.Date, &r.SlotCode, &idsJSON, &r.Version)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	if err := json.Unmarshal([]byte(idsJSON), &r.RecipeIDs); err != nil {
		return nil, err
	}
	return &r, nil
}

// PatchSlot updates recipe ids; returns updated row or versionConflict.
func (s *Store) PatchSlot(ctx context.Context, userID, slotID string, recipeIDs []string, expectedVersion *int) (*SlotRow, bool, error) {
	tx, err := s.DB.BeginTx(ctx, nil)
	if err != nil {
		return nil, false, err
	}
	defer func() { _ = tx.Rollback() }()

	var curVer int
	var idsJSON string
	var dateStr, code string
	err = tx.QueryRowContext(ctx, `
SELECT version, recipe_ids::text, slot_date::text, slot_code
FROM planning_slots WHERE id = $1 AND user_id = $2 FOR UPDATE`,
		slotID, userID).Scan(&curVer, &idsJSON, &dateStr, &code)
	if err == sql.ErrNoRows {
		return nil, false, nil
	}
	if err != nil {
		return nil, false, err
	}
	if expectedVersion != nil && *expectedVersion != curVer {
		return nil, true, nil
	}

	newJSON, err := json.Marshal(recipeIDs)
	if err != nil {
		return nil, false, err
	}
	_, err = tx.ExecContext(ctx, `
UPDATE planning_slots SET recipe_ids = $1::jsonb, version = version + 1, updated_at = NOW()
WHERE id = $2 AND user_id = $3`, string(newJSON), slotID, userID)
	if err != nil {
		return nil, false, err
	}
	if err := tx.Commit(); err != nil {
		return nil, false, err
	}
	row, err := s.GetSlot(ctx, userID, slotID)
	return row, false, err
}

func (s *Store) AssignmentsInRange(ctx context.Context, userID, from, to string) ([]map[string]any, error) {
	rows, err := s.DB.QueryContext(ctx, `
SELECT slot_date::text, slot_code, recipe_ids::text
FROM planning_slots
WHERE user_id = $1 AND slot_date >= $2::date AND slot_date <= $3::date`,
		userID, from, to)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var items []map[string]any
	for rows.Next() {
		var dateStr, code, idsJSON string
		if err := rows.Scan(&dateStr, &code, &idsJSON); err != nil {
			return nil, err
		}
		var ids []string
		if err := json.Unmarshal([]byte(idsJSON), &ids); err != nil {
			return nil, err
		}
		for _, rid := range ids {
			items = append(items, map[string]any{
				"date":      dateStr,
				"slotCode":  code,
				"recipeId":  rid,
			})
		}
	}
	return items, rows.Err()
}

// ValidateRecipeUUIDs returns error message key if invalid.
func ValidateRecipeUUIDs(ids []string) error {
	for _, id := range ids {
		if _, err := uuid.Parse(id); err != nil {
			return fmt.Errorf("invalid recipe id")
		}
	}
	return nil
}

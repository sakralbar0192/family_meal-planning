package db

import (
	"context"
	"database/sql"
	"fmt"

	_ "github.com/jackc/pgx/v5/stdlib"
)

func Open(dsn string) (*sql.DB, error) {
	d, err := sql.Open("pgx", dsn)
	if err != nil {
		return nil, err
	}
	if err := d.Ping(); err != nil {
		_ = d.Close()
		return nil, err
	}
	return d, nil
}

func Migrate(ctx context.Context, db *sql.DB) error {
	stmts := []string{
		`CREATE TABLE IF NOT EXISTS planning_slots (
    id UUID PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    slot_date DATE NOT NULL,
    slot_code VARCHAR(32) NOT NULL,
    recipe_ids JSONB NOT NULL DEFAULT '[]',
    version INT NOT NULL DEFAULT 1,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, slot_date, slot_code)
)`,
		`CREATE INDEX IF NOT EXISTS planning_slots_user_date ON planning_slots (user_id, slot_date)`,
	}
	for _, q := range stmts {
		if _, err := db.ExecContext(ctx, q); err != nil {
			return fmt.Errorf("migrate: %w", err)
		}
	}
	return nil
}

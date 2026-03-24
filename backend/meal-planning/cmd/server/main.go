package main

import (
	"context"
	"log"
	"net/http"
	"os"
	"time"

	"family-meal-planning/meal-planning/internal/db"
	"family-meal-planning/meal-planning/internal/httpserver"
	"family-meal-planning/meal-planning/internal/plan"
)

func main() {
	dsn := os.Getenv("PLANNING_DATABASE_URL")
	if dsn == "" {
		log.Fatal("PLANNING_DATABASE_URL is required (postgres://...)")
	}
	internal := os.Getenv("INTERNAL_AUTH_TOKEN")
	if internal == "" {
		log.Fatal("INTERNAL_AUTH_TOKEN is required")
	}

	pgdb, err := db.Open(dsn)
	if err != nil {
		log.Fatalf("db: %v", err)
	}
	defer pgdb.Close()

	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()
	if err := db.Migrate(ctx, pgdb); err != nil {
		log.Fatalf("migrate: %v", err)
	}

	store := &plan.Store{DB: pgdb}
	srv := httpserver.New(internal, store)

	addr := os.Getenv("LISTEN_ADDR")
	if addr == "" {
		addr = ":8084"
	}
	log.Printf("meal-planning listening on %s", addr)
	if err := http.ListenAndServe(addr, srv.Handler()); err != nil {
		log.Fatal(err)
	}
}

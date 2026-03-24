package main

import (
	"log"
	"net/http"
	"time"

	"family-meal-planning/recipe-import/internal/config"
	"family-meal-planning/recipe-import/internal/httpserver"
	"family-meal-planning/recipe-import/internal/importer"
)

func main() {
	cfg := config.Load()
	if cfg.InternalAuth == "" {
		log.Fatal("INTERNAL_AUTH_TOKEN is required")
	}
	if len(cfg.AllowedHosts) == 0 {
		log.Println("warning: IMPORT_ALLOWED_HOSTS is empty; all import URLs will be rejected")
	}

	svc := &importer.Service{
		Client: &http.Client{
			Timeout: cfg.FetchTimeout + 500*time.Millisecond,
		},
		AllowedHosts: cfg.AllowedHosts,
		FetchTimeout: cfg.FetchTimeout,
	}

	srv := httpserver.New(cfg, svc)
	addr := cfg.ListenAddr
	log.Printf("recipe-import listening on %s", addr)
	if err := http.ListenAndServe(addr, srv.Handler()); err != nil {
		log.Fatal(err)
	}
}

package config

import (
	"os"
	"strings"
	"time"
)

type Config struct {
	ListenAddr       string
	InternalAuth     string
	AllowedHosts     map[string]struct{}
	FetchTimeout     time.Duration
}

func Load() Config {
	hosts := make(map[string]struct{})
	for _, h := range strings.Split(os.Getenv("IMPORT_ALLOWED_HOSTS"), ",") {
		h = strings.ToLower(strings.TrimSpace(h))
		if h != "" {
			hosts[h] = struct{}{}
		}
	}
	timeout := 5 * time.Second
	if v := os.Getenv("IMPORT_FETCH_TIMEOUT_SECONDS"); v != "" {
		if d, err := time.ParseDuration(v + "s"); err == nil {
			timeout = d
		}
	}
	addr := os.Getenv("LISTEN_ADDR")
	if addr == "" {
		addr = ":8083"
	}
	return Config{
		ListenAddr:   addr,
		InternalAuth: os.Getenv("INTERNAL_AUTH_TOKEN"),
		AllowedHosts: hosts,
		FetchTimeout: timeout,
	}
}

package importer

import (
	"context"
	"net/http"
	"net/http/httptest"
	"net/url"
	"strings"
	"testing"
	"time"
)

func TestImportByURL_Allowlist(t *testing.T) {
	svc := &Service{
		Client:       http.DefaultClient,
		AllowedHosts: map[string]struct{}{"allowed.example": {}},
		FetchTimeout: time.Second,
	}
	_, status, code, _ := svc.ImportByURL(context.Background(), "https://other.example/x")
	if status != 400 || code != "URL_NOT_ALLOWED" {
		t.Fatalf("got %d %s", status, code)
	}
}

func TestImportByURL_Success(t *testing.T) {
	ts := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "text/html")
		_, _ = w.Write([]byte("<html><head><title>Soup</title></head></html>"))
	}))
	t.Cleanup(ts.Close)

	pu, err := url.Parse(ts.URL)
	if err != nil {
		t.Fatal(err)
	}
	host := strings.ToLower(strings.Split(pu.Host, ":")[0])

	svc := &Service{
		Client:       ts.Client(),
		AllowedHosts: map[string]struct{}{host: {}},
		FetchTimeout: 5 * time.Second,
	}
	draft, status, _, _ := svc.ImportByURL(context.Background(), ts.URL+"/page")
	if status != 200 {
		t.Fatalf("status %d", status)
	}
	if draft["title"] != "Soup" {
		t.Fatalf("draft %+v", draft)
	}
}

package importer

import "testing"

func TestExtractDraftTitle(t *testing.T) {
	html := `<!doctype html><html><head><title>  Borsch Recipe  </title></head><body></body></html>`
	title, steps, ing := ExtractDraft(html, "https://example.com/r")
	if title != "Borsch Recipe" {
		t.Fatalf("title: %q", title)
	}
	if len(steps) != 0 || len(ing) != 0 {
		t.Fatalf("expected empty steps/ingredients")
	}
}

func TestExtractDraftEmpty(t *testing.T) {
	title, _, _ := ExtractDraft("<html></html>", "u")
	if title != "" {
		t.Fatalf("expected empty title")
	}
}

func TestExtractDraftH1Fallback(t *testing.T) {
	htmlDoc := `<!doctype html><html><body><h1>  Soup from h1  </h1></body></html>`
	title, steps, ing := ExtractDraft(htmlDoc, "https://example.com/r")
	if title != "Soup from h1" {
		t.Fatalf("title: %q", title)
	}
	if len(steps) != 0 || len(ing) != 0 {
		t.Fatalf("expected empty steps/ingredients")
	}
}

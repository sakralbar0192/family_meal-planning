package importer

import (
	"os"
	"path/filepath"
	"runtime"
	"testing"
)

func fixturePath(parts ...string) string {
	_, file, _, _ := runtime.Caller(0)
	base := filepath.Join(filepath.Dir(file), "..", "..", "testdata", "http")
	return filepath.Join(append([]string{base}, parts...)...)
}

func readFixture(t *testing.T, parts ...string) string {
	t.Helper()
	b, err := os.ReadFile(fixturePath(parts...))
	if err != nil {
		t.Fatal(err)
	}
	return string(b)
}

func TestExtractDraftTitle(t *testing.T) {
	html := `<!doctype html><html><head><title>  Borsch Recipe  </title></head><body></body></html>`
	d := ExtractDraft(html, "https://example.com/r")
	if d.Title != "Borsch Recipe" {
		t.Fatalf("title: %q", d.Title)
	}
	if len(d.Steps) != 0 || len(d.Ingredients) != 0 {
		t.Fatalf("expected empty steps/ingredients")
	}
}

func TestExtractDraftEmpty(t *testing.T) {
	d := ExtractDraft("<html></html>", "u")
	if d.Title != "" {
		t.Fatalf("expected empty title")
	}
}

func TestExtractDraftH1Fallback(t *testing.T) {
	htmlDoc := `<!doctype html><html><body><h1>  Soup from h1  </h1></body></html>`
	d := ExtractDraft(htmlDoc, "https://example.com/r")
	if d.Title != "Soup from h1" {
		t.Fatalf("title: %q", d.Title)
	}
}

func TestExtractDraftOgTitleFallback(t *testing.T) {
	htmlDoc := `<!doctype html><html><head>
<meta property="og:title" content="  Pie from OG  " />
</head><body></body></html>`
	d := ExtractDraft(htmlDoc, "https://example.com/r")
	if d.Title != "Pie from OG" {
		t.Fatalf("title: %q", d.Title)
	}
}

func TestExtractDraftEdaFixture(t *testing.T) {
	html := readFixture(t, "eda", "borsch.html")
	d := ExtractDraft(html, "https://eda.ru/recepty/sup/borsch")
	if d.Title != "Борщ классический" {
		t.Fatalf("title: %q", d.Title)
	}
	if len(d.Ingredients) < 4 {
		t.Fatalf("ingredients: %d", len(d.Ingredients))
	}
	if len(d.Steps) < 3 {
		t.Fatalf("steps: %d", len(d.Steps))
	}
	if d.CookTimeMinutes == nil || *d.CookTimeMinutes != 90 {
		t.Fatalf("cook time: %v", d.CookTimeMinutes)
	}
	if d.Nutrition == nil {
		t.Fatal("expected nutrition")
	}
}

func TestExtractDraftPovarenokFixture(t *testing.T) {
	html := readFixture(t, "povarenok", "soup.html")
	d := ExtractDraft(html, "https://povarenok.ru/recept/soup")
	if d.Title != "Куриный суп" {
		t.Fatalf("title: %q", d.Title)
	}
	if len(d.Ingredients) < 4 {
		t.Fatalf("ingredients: %d", len(d.Ingredients))
	}
	if len(d.Steps) < 3 {
		t.Fatalf("steps: %d", len(d.Steps))
	}
	if d.CookTimeMinutes == nil || *d.CookTimeMinutes != 45 {
		t.Fatalf("cook time: %v", d.CookTimeMinutes)
	}
}

func TestParseIngredientLine(t *testing.T) {
	m := parseIngredientLine("200 г муки")
	if m["name"] != "муки" {
		t.Fatalf("name: %v", m["name"])
	}
	m2 := parseIngredientLine("соль по вкусу")
	if m2["quantity"] != nil {
		t.Fatalf("expected nil quantity")
	}
}

func TestParseIngredientLineEdaRamblerComma(t *testing.T) {
	m := parseIngredientLine("Говядина с костями, 400 г")
	if m["name"] != "Говядина с костями" {
		t.Fatalf("name: %v", m["name"])
	}
	q, ok := m["quantity"].(float64)
	if !ok || q != 400 {
		t.Fatalf("quantity: %v", m["quantity"])
	}
	if m["unit"] != "г" {
		t.Fatalf("unit: %v", m["unit"])
	}

	m2 := parseIngredientLine("Томатная паста , 2 столовые ложки")
	if m2["name"] != "Томатная паста" {
		t.Fatalf("name: %v", m2["name"])
	}
	if m2["unit"] != "столовые ложки" {
		t.Fatalf("unit: %v", m2["unit"])
	}

	m3 := parseIngredientLine("Соль,  по вкусу")
	if m3["quantity"] != nil {
		t.Fatalf("expected nil quantity for to-taste, got %v", m3["quantity"])
	}
}

func TestExtractDraftEdaLiveRamblerJSONLD(t *testing.T) {
	html := readFixture(t, "eda", "live-rambler-ld.html")
	d := ExtractDraft(html, "https://eda.rambler.ru/recepty/supy/borsch-po-klassicheskomu-receptu-114490")
	if d.Title != "Борщ по классическому рецепту" {
		t.Fatalf("title: %q", d.Title)
	}
	if len(d.Ingredients) < 4 {
		t.Fatalf("ingredients: %d", len(d.Ingredients))
	}
	foundBeef := false
	for _, ing := range d.Ingredients {
		if ing["name"] == "Говядина с костями" {
			foundBeef = true
			if ing["quantity"] != 400.0 {
				t.Fatalf("beef qty: %v", ing["quantity"])
			}
		}
	}
	if !foundBeef {
		t.Fatalf("expected parsed comma-quantity beef ingredient, got %#v", d.Ingredients)
	}
	if len(d.Steps) < 3 {
		t.Fatalf("steps: %d", len(d.Steps))
	}
	if d.CookTimeMinutes == nil || *d.CookTimeMinutes != 130 {
		t.Fatalf("cook time: %v", d.CookTimeMinutes)
	}
}

func TestExtractDraftPovarenokItempropFallback(t *testing.T) {
	html := readFixture(t, "povarenok", "no-jsonld.html")
	d := ExtractDraft(html, "https://www.povarenok.ru/recipes/show/1/")
	if d.Title != "Гороховый суп" {
		t.Fatalf("title: %q", d.Title)
	}
	if len(d.Ingredients) < 3 {
		t.Fatalf("ingredients: %d %#v", len(d.Ingredients), d.Ingredients)
	}
	if len(d.Steps) < 3 {
		t.Fatalf("steps: %d %#v", len(d.Steps), d.Steps)
	}
}

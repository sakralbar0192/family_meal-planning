package importer

import "testing"

func TestIngredientParseQtyUnitName(t *testing.T) {
	m := parseIngredientLine("500 г говядина")
	if m["name"] != "говядина" {
		t.Fatalf("name: %v", m["name"])
	}
	q, ok := m["quantity"].(float64)
	if !ok || q != 500 {
		t.Fatalf("quantity: %v", m["quantity"])
	}
}

func TestIngredientParseNameCommaQty(t *testing.T) {
	m := parseIngredientLine("Вода, 2 л")
	if m["name"] != "Вода" {
		t.Fatalf("name: %v", m["name"])
	}
	q, ok := m["quantity"].(float64)
	if !ok || q != 2 {
		t.Fatalf("quantity: %v", m["quantity"])
	}
	if m["unit"] != "л" {
		t.Fatalf("unit: %v", m["unit"])
	}
}

package importer

import (
	"regexp"
	"strconv"
	"strings"
)

var (
	ingQtyUnitName = regexp.MustCompile(`(?i)^([\d.,]+)\s*([а-яa-z]+(?:\.|\/[а-яa-z]+)?)\.?\s+(.+)$`)
	ingNameDashQty = regexp.MustCompile(`(?i)^(.+?)\s*[—–-]\s*([\d.,]+)\s*([а-яa-z]+(?:\.|\/[а-яa-z]+)?)\.?\s*$`)
	// Live eda.rambler.ru JSON-LD: "Говядина с костями, 400 г", "Томатная паста , 2 столовые ложки"
	ingNameCommaQty = regexp.MustCompile(`(?i)^(.+?),\s*([\d.,]+)\s+(.+)$`)
)

func parseIngredientLine(raw string) map[string]any {
	line := strings.TrimSpace(raw)
	if line == "" {
		return nil
	}
	lower := strings.ToLower(line)
	if strings.Contains(lower, "по вкусу") {
		name := strings.TrimSpace(strings.ReplaceAll(line, "по вкусу", ""))
		name = strings.Trim(name, "—–-, ")
		if name == "" {
			name = line
		}
		return ingredientDraft(name, nil, "")
	}
	if m := ingQtyUnitName.FindStringSubmatch(line); len(m) == 4 {
		qty := parseFloat(m[1])
		return ingredientDraft(strings.TrimSpace(m[3]), qty, strings.TrimSpace(m[2]))
	}
	if m := ingNameDashQty.FindStringSubmatch(line); len(m) == 4 {
		qty := parseFloat(m[2])
		return ingredientDraft(strings.TrimSpace(m[1]), qty, strings.TrimSpace(m[3]))
	}
	if m := ingNameCommaQty.FindStringSubmatch(line); len(m) == 4 {
		unit := strings.TrimSpace(m[3])
		if unit != "" && len([]rune(unit)) <= 40 {
			return ingredientDraft(strings.TrimSpace(m[1]), parseFloat(m[2]), unit)
		}
	}
	return ingredientDraft(line, nil, "")
}

func parseFloat(s string) any {
	s = strings.ReplaceAll(s, ",", ".")
	f, err := strconv.ParseFloat(s, 64)
	if err != nil {
		return nil
	}
	return f
}

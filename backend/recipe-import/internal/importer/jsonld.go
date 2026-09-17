package importer

import (
	"encoding/json"
	"regexp"
	"strconv"
	"strings"
)

var jsonLDScriptRe = regexp.MustCompile(`(?is)<script[^>]*type\s*=\s*["']application/ld\+json["'][^>]*>(.*?)</script>`)

func extractJSONLDDraft(htmlDoc string) *Draft {
	for _, block := range jsonLDScriptRe.FindAllStringSubmatch(htmlDoc, -1) {
		if len(block) < 2 {
			continue
		}
		raw := strings.TrimSpace(block[1])
		var data any
		if err := json.Unmarshal([]byte(raw), &data); err != nil {
			continue
		}
		if d := recipeFromJSONLD(data); d != nil && d.Title != "" {
			return d
		}
	}
	return nil
}

func recipeFromJSONLD(data any) *Draft {
	switch v := data.(type) {
	case map[string]any:
		if isRecipeType(v["@type"]) {
			return mapRecipeObject(v)
		}
		if graph, ok := v["@graph"].([]any); ok {
			for _, item := range graph {
				if m, ok := item.(map[string]any); ok && isRecipeType(m["@type"]) {
					return mapRecipeObject(m)
				}
			}
		}
	case []any:
		for _, item := range v {
			if d := recipeFromJSONLD(item); d != nil {
				return d
			}
		}
	}
	return nil
}

func isRecipeType(t any) bool {
	switch v := t.(type) {
	case string:
		return strings.EqualFold(v, "Recipe") || strings.HasSuffix(strings.ToLower(v), "recipe")
	case []any:
		for _, item := range v {
			if s, ok := item.(string); ok && (strings.EqualFold(s, "Recipe") || strings.HasSuffix(strings.ToLower(s), "recipe")) {
				return true
			}
		}
	}
	return false
}

func mapRecipeObject(o map[string]any) *Draft {
	title := stringField(o, "name")
	if title == "" {
		title = stringField(o, "headline")
	}
	if title == "" {
		return nil
	}
	d := &Draft{Title: title}

	if img := o["image"]; img != nil {
		if u := imageURL(img); u != "" {
			d.ImageURL = &u
		}
	}

	mins := durationMinutes(o["totalTime"])
	if mins == nil {
		mins = durationMinutes(o["cookTime"])
	}
	if mins == nil {
		mins = durationMinutes(o["prepTime"])
	}
	d.CookTimeMinutes = mins

	d.Steps = instructionsFrom(o["recipeInstructions"])
	d.Ingredients = ingredientsFrom(o["recipeIngredient"])

	if n := o["nutrition"]; n != nil {
		if m, ok := n.(map[string]any); ok {
			d.Nutrition = nutritionFrom(m)
		}
	}

	return d
}

func stringField(o map[string]any, key string) string {
	v, ok := o[key]
	if !ok || v == nil {
		return ""
	}
	switch s := v.(type) {
	case string:
		return strings.TrimSpace(s)
	default:
		return strings.TrimSpace(stringifyJSON(v))
	}
}

func stringifyJSON(v any) string {
	b, _ := json.Marshal(v)
	return string(b)
}

func imageURL(v any) string {
	switch img := v.(type) {
	case string:
		return strings.TrimSpace(img)
	case []any:
		for _, item := range img {
			if u := imageURL(item); u != "" {
				return u
			}
		}
	case map[string]any:
		if u := stringField(img, "url"); u != "" {
			return u
		}
		if u := stringField(img, "contentUrl"); u != "" {
			return u
		}
	}
	return ""
}

func durationMinutes(v any) *int {
	s, ok := v.(string)
	if !ok || s == "" {
		return nil
	}
	s = strings.ToUpper(strings.TrimSpace(s))
	if !strings.HasPrefix(s, "PT") {
		return parseMinutesFromText(s)
	}
	total := 0
	hRe := regexp.MustCompile(`(\d+)H`)
	mRe := regexp.MustCompile(`(\d+)M`)
	if m := hRe.FindStringSubmatch(s); len(m) == 2 {
		if h, err := strconv.Atoi(m[1]); err == nil {
			total += h * 60
		}
	}
	if m := mRe.FindStringSubmatch(s); len(m) == 2 {
		if min, err := strconv.Atoi(m[1]); err == nil {
			total += min
		}
	}
	if total == 0 {
		return nil
	}
	return &total
}

func parseMinutesFromText(s string) *int {
	re := regexp.MustCompile(`(\d+)`)
	m := re.FindStringSubmatch(strings.ToLower(s))
	if len(m) != 2 {
		return nil
	}
	n, err := strconv.Atoi(m[1])
	if err != nil {
		return nil
	}
	return &n
}

func instructionsFrom(v any) []string {
	switch inst := v.(type) {
	case string:
		return splitSteps(inst)
	case []any:
		var steps []string
		for _, item := range inst {
			switch step := item.(type) {
			case string:
				if t := strings.TrimSpace(step); t != "" {
					steps = append(steps, t)
				}
			case map[string]any:
				if t := stringField(step, "text"); t != "" {
					steps = append(steps, t)
				} else if t := stringField(step, "name"); t != "" {
					steps = append(steps, t)
				}
			}
		}
		return steps
	}
	return nil
}

func splitSteps(s string) []string {
	parts := regexp.MustCompile(`\n+`).Split(strings.TrimSpace(s), -1)
	var steps []string
	for _, p := range parts {
		if t := strings.TrimSpace(p); t != "" {
			steps = append(steps, t)
		}
	}
	return steps
}

func ingredientsFrom(v any) []map[string]any {
	list, ok := v.([]any)
	if !ok {
		return nil
	}
	var out []map[string]any
	for _, item := range list {
		switch ing := item.(type) {
		case string:
			if m := parseIngredientLine(ing); m != nil {
				out = append(out, m)
			}
		case map[string]any:
			name := stringField(ing, "name")
			if name == "" {
				continue
			}
			qty := any(nil)
			if val := ing["value"]; val != nil {
				qty = val
			}
			unit := stringField(ing, "unitCode")
			out = append(out, ingredientDraft(name, qty, unit))
		}
	}
	return out
}

func nutritionFrom(n map[string]any) map[string]any {
	out := map[string]any{}
	if c := parseNutritionNumber(n["calories"]); c != nil {
		out["calories"] = *c
	}
	if p := parseNutritionNumber(n["proteinContent"]); p != nil {
		out["proteinG"] = *p
	} else if p := parseNutritionNumber(n["protein"]); p != nil {
		out["proteinG"] = *p
	}
	if f := parseNutritionNumber(n["fatContent"]); f != nil {
		out["fatG"] = *f
	} else if f := parseNutritionNumber(n["fat"]); f != nil {
		out["fatG"] = *f
	}
	if c := parseNutritionNumber(n["carbohydrateContent"]); c != nil {
		out["carbsG"] = *c
	} else if c := parseNutritionNumber(n["carbohydrates"]); c != nil {
		out["carbsG"] = *c
	}
	if len(out) == 0 {
		return nil
	}
	return out
}

func parseNutritionNumber(v any) *float64 {
	switch n := v.(type) {
	case float64:
		return &n
	case json.Number:
		if f, err := n.Float64(); err == nil {
			return &f
		}
	case string:
		re := regexp.MustCompile(`[\d.,]+`)
		m := re.FindString(strings.ReplaceAll(n, ",", "."))
		if m == "" {
			return nil
		}
		f, err := strconv.ParseFloat(m, 64)
		if err != nil {
			return nil
		}
		return &f
	}
	return nil
}

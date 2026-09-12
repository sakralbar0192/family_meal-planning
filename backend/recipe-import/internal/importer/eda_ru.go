package importer

import (
	"regexp"
	"strings"

	"golang.org/x/net/html"
)

func extractEdaRuDraft(doc *html.Node, htmlDoc string) *Draft {
	if d := extractJSONLDDraft(htmlDoc); d != nil && len(d.Ingredients) > 0 {
		return d
	}
	d := &Draft{}
	if d2 := extractJSONLDDraft(htmlDoc); d2 != nil {
		d = d2
	}
	if d.Title == "" {
		d.Title = strings.TrimSpace(findFirstH1(doc))
	}
	if d.ImageURL == nil {
		if u := findOgImage(doc); u != "" {
			d.ImageURL = &u
		}
	}
	if len(d.Ingredients) == 0 {
		d.Ingredients = edaIngredientsFromHTML(doc)
	}
	if len(d.Steps) == 0 {
		d.Steps = edaStepsFromHTML(doc)
	}
	if d.CookTimeMinutes == nil {
		d.CookTimeMinutes = edaCookTime(doc)
	}
	if len(d.Nutrition) == 0 {
		d.Nutrition = edaNutrition(doc)
	}
	if d.Title == "" {
		return nil
	}
	return d
}

func edaIngredientsFromHTML(doc *html.Node) []map[string]any {
	var lines []string
	walk(doc, func(n *html.Node) {
		if n.Type != html.ElementNode {
			return
		}
		cls := classAttr(n)
		if strings.EqualFold(attrVal(n, "itemprop"), "recipeIngredient") {
			t := strings.TrimSpace(textContent(n))
			if t != "" && len(t) < 200 {
				lines = append(lines, t)
			}
		}
		if strings.Contains(cls, "ingredient") || strings.Contains(cls, "Ingredient") {
			t := strings.TrimSpace(textContent(n))
			if t != "" && len(t) < 200 {
				lines = append(lines, t)
			}
		}
		if n.Data == "li" && (strings.Contains(cls, "ingredients") || hasAncestorClass(n, "ingredients")) {
			t := strings.TrimSpace(textContent(n))
			if t != "" && len(t) < 200 {
				lines = append(lines, t)
			}
		}
	})
	return parseIngredientLines(uniqueNonEmpty(lines))
}

func edaStepsFromHTML(doc *html.Node) []string {
	var steps []string
	walk(doc, func(n *html.Node) {
		if n.Type != html.ElementNode {
			return
		}
		cls := classAttr(n)
		if strings.Contains(cls, "instruction") || strings.Contains(cls, "step") || strings.Contains(cls, "Instruction") {
			if n.Data == "p" || n.Data == "li" {
				t := strings.TrimSpace(textContent(n))
				if t != "" && len(t) > 5 {
					steps = append(steps, t)
				}
			}
		}
	})
	return uniqueNonEmpty(steps)
}

func edaCookTime(doc *html.Node) *int {
	re := regexp.MustCompile(`(\d+)\s*мин`)
	var text string
	walk(doc, func(n *html.Node) {
		if n.Type == html.ElementNode && (n.Data == "time" || strings.Contains(classAttr(n), "time")) {
			text += " " + textContent(n)
		}
	})
	m := re.FindStringSubmatch(text)
	if len(m) != 2 {
		return nil
	}
	return parseMinutesFromText(m[0])
}

func edaNutrition(doc *html.Node) map[string]any {
	text := strings.ToLower(textContent(doc))
	out := map[string]any{}
	calRe := regexp.MustCompile(`(\d+(?:[.,]\d+)?)\s*(?:ккал|kcal)`)
	protRe := regexp.MustCompile(`белк\w*[:\s]+(\d+(?:[.,]\d+)?)`)
	fatRe := regexp.MustCompile(`жир\w*[:\s]+(\d+(?:[.,]\d+)?)`)
	carbRe := regexp.MustCompile(`углев\w*[:\s]+(\d+(?:[.,]\d+)?)`)
	if m := calRe.FindStringSubmatch(text); len(m) == 2 {
		if f := parseNutritionNumber(m[1]); f != nil {
			out["calories"] = *f
		}
	}
	if m := protRe.FindStringSubmatch(text); len(m) == 2 {
		if f := parseNutritionNumber(m[1]); f != nil {
			out["proteinG"] = *f
		}
	}
	if m := fatRe.FindStringSubmatch(text); len(m) == 2 {
		if f := parseNutritionNumber(m[1]); f != nil {
			out["fatG"] = *f
		}
	}
	if m := carbRe.FindStringSubmatch(text); len(m) == 2 {
		if f := parseNutritionNumber(m[1]); f != nil {
			out["carbsG"] = *f
		}
	}
	if len(out) == 0 {
		return nil
	}
	return out
}

func findOgImage(doc *html.Node) string {
	var url string
	walk(doc, func(n *html.Node) {
		if url != "" || n.Type != html.ElementNode || n.Data != "meta" {
			return
		}
		var prop, content string
		for _, a := range n.Attr {
			switch a.Key {
			case "property":
				prop = a.Val
			case "content":
				content = a.Val
			}
		}
		if strings.EqualFold(prop, "og:image") && strings.TrimSpace(content) != "" {
			url = strings.TrimSpace(content)
		}
	})
	return url
}

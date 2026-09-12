package importer

import (
	"regexp"
	"strings"

	"golang.org/x/net/html"
)

func extractPovarenokDraft(doc *html.Node, htmlDoc string) *Draft {
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
		d.Ingredients = povarenokIngredients(doc)
	}
	if len(d.Steps) == 0 {
		d.Steps = povarenokSteps(doc)
	}
	if d.CookTimeMinutes == nil {
		d.CookTimeMinutes = povarenokCookTime(doc)
	}
	if d.Title == "" {
		return nil
	}
	return d
}

func povarenokIngredients(doc *html.Node) []map[string]any {
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
		if n.Data == "tr" && (strings.Contains(cls, "ingredient") || hasAncestorClass(n, "ingredients") || strings.Contains(cls, "ingr")) {
			t := strings.TrimSpace(textContent(n))
			if t != "" {
				lines = append(lines, t)
			}
		}
		if n.Data == "li" && (strings.Contains(cls, "ingredient") || hasAncestorClass(n, "ingredients_list")) {
			t := strings.TrimSpace(textContent(n))
			if t != "" && len(t) < 200 {
				lines = append(lines, t)
			}
		}
	})
	return parseIngredientLines(uniqueNonEmpty(lines))
}

func povarenokSteps(doc *html.Node) []string {
	var steps []string
	walk(doc, func(n *html.Node) {
		if n.Type != html.ElementNode {
			return
		}
		cls := classAttr(n)
		if strings.EqualFold(attrVal(n, "itemprop"), "recipeInstructions") || strings.EqualFold(attrVal(n, "itemprop"), "text") {
			if n.Data == "p" || n.Data == "li" || n.Data == "div" {
				t := strings.TrimSpace(textContent(n))
				if t != "" && len(t) > 5 && len(t) < 2000 {
					steps = append(steps, t)
				}
			}
		}
		if (strings.Contains(cls, "step") || strings.Contains(cls, "instruction") || hasAncestorClass(n, "steps")) &&
			(n.Data == "p" || n.Data == "li" || n.Data == "div") {
			t := strings.TrimSpace(textContent(n))
			if t != "" && len(t) > 5 && len(t) < 2000 {
				steps = append(steps, t)
			}
		}
	})
	if len(steps) == 0 {
		// Plain numbered paragraphs fallback
		re := regexp.MustCompile(`^\d+\.\s+`)
		walk(doc, func(n *html.Node) {
			if n.Type == html.ElementNode && n.Data == "p" {
				t := strings.TrimSpace(textContent(n))
				if re.MatchString(t) {
					steps = append(steps, t)
				}
			}
		})
	}
	return uniqueNonEmpty(steps)
}

func povarenokCookTime(doc *html.Node) *int {
	re := regexp.MustCompile(`(\d+)\s*мин`)
	text := textContent(doc)
	m := re.FindStringSubmatch(strings.ToLower(text))
	if len(m) != 2 {
		return nil
	}
	return parseMinutesFromText(m[0])
}

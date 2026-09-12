package importer

// Draft holds parsed recipe fields before catalog persistence.
type Draft struct {
	Title           string
	Steps           []string
	Ingredients     []map[string]any
	CookTimeMinutes *int
	ImageURL        *string
	Nutrition       map[string]any
}

func (d Draft) toMap(sourceURL string) map[string]any {
	m := map[string]any{
		"title":           d.Title,
		"steps":           d.Steps,
		"ingredients":     d.Ingredients,
		"sourceUrl":       sourceURL,
		"cookTimeMinutes": nil,
		"mealCategory":    nil,
		"nutrition":       nil,
		"imageUrl":        nil,
	}
	if d.CookTimeMinutes != nil {
		m["cookTimeMinutes"] = *d.CookTimeMinutes
	}
	if d.ImageURL != nil && *d.ImageURL != "" {
		m["imageUrl"] = *d.ImageURL
	}
	if len(d.Nutrition) > 0 {
		m["nutrition"] = d.Nutrition
	}
	if d.Steps == nil {
		m["steps"] = []string{}
	}
	if d.Ingredients == nil {
		m["ingredients"] = []map[string]any{}
	}
	return m
}

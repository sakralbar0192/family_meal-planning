package importer

import "strings"

const (
	CategoryDairy     = "молочные"
	CategoryMeat      = "мясо"
	CategoryGroceries = "бакалея"
	CategoryVeg       = "овощи"
	CategoryOther     = "прочее"
)

// ClassifyProductCategory maps an ingredient name to a shopping category.
// Unknown names become «прочее» so an import draft can be saved without a blank category.
func ClassifyProductCategory(name string) string {
	n := strings.ToLower(strings.TrimSpace(name))
	n = strings.ReplaceAll(n, "ё", "е")
	if n == "" {
		return CategoryOther
	}
	if containsAny(n, dairyKeys) {
		return CategoryDairy
	}
	if containsAny(n, meatKeys) {
		return CategoryMeat
	}
	if containsAny(n, groceryKeys) {
		return CategoryGroceries
	}
	if containsAny(n, vegKeys) {
		return CategoryVeg
	}
	return CategoryOther
}

func containsAny(haystack string, keys []string) bool {
	for _, k := range keys {
		if strings.Contains(haystack, k) {
			return true
		}
	}
	return false
}

var dairyKeys = []string{
	"молок", "сливк", "сметан", "творог", "сыр", "йогурт", "кефир", "сливочн",
	"яйц", "ряженк", "простокваш",
	"milk", "cream", "cheese", "yogurt", "yoghurt", "butter", "egg", "dairy",
}

var meatKeys = []string{
	"говядин", "свинин", "курин", "куриц", "индейк", "баранин", "фарш", "бекон",
	"колбас", "мясо", "филе", "грудинк", "крыл", "бедр",
	"рыб", "лосос", "треск", "сельдь", "кревет", "миди", "кальмар",
	"chicken", "beef", "pork", "bacon", "turkey", "fish", "salmon", "meat",
}

var groceryKeys = []string{
	"томатн", "паста", "мук", "круп", "рис", "греч", "макарон", "спагет",
	"сахар", "соль", "специ", "лавр", "овсян", "хлоп", "бакале",
	"оливк", "подсолнеч", "растительн", "масло",
	"flour", "pasta", "spaghetti", "rice", "sugar", "salt", "oil", "oat",
}

var vegKeys = []string{
	"картофел", "морков", "лук", "капуст", "свекл", "помидор", "томат",
	"огурец", "огурц", "перец", "чеснок", "зелен", "укроп", "петрушк",
	"салат", "кабачок", "баклажан", "гриб", "ягод", "яблок", "лимон",
	"фрук", "овощ", "свекл", "свекла",
	"tomato", "onion", "potato", "carrot", "cabbage", "cucumber", "garlic",
	"beet", "vegetable", "apple", "lemon", "berry",
}

func ingredientDraft(name string, qty any, unit string) map[string]any {
	return map[string]any{
		"name":            name,
		"quantity":        qty,
		"unit":            unit,
		"productCategory": ClassifyProductCategory(name),
	}
}

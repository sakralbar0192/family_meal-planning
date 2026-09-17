package importer

import "testing"

func TestClassifyProductCategory(t *testing.T) {
	cases := []struct {
		name string
		want string
	}{
		{"говядина", CategoryMeat},
		{"Говядина с костями", CategoryMeat},
		{"Куриное филе", CategoryMeat},
		{"Молоко", CategoryDairy},
		{"сыр фета", CategoryDairy},
		{"сливочное масло", CategoryDairy},
		{"муки", CategoryGroceries},
		{"Томатная паста", CategoryGroceries},
		{"соль", CategoryGroceries},
		{"растительное масло", CategoryGroceries},
		{"помидор", CategoryVeg},
		{"Свёкла", CategoryVeg},
		{"картофель", CategoryVeg},
		{"Вода", CategoryOther},
		{"", CategoryOther},
	}
	for _, tc := range cases {
		got := ClassifyProductCategory(tc.name)
		if got != tc.want {
			t.Errorf("%q: got %q want %q", tc.name, got, tc.want)
		}
	}
}

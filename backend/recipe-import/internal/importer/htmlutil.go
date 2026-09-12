package importer

import (
	"strings"

	"golang.org/x/net/html"
)

func isEdaHost(host string) bool {
	switch host {
	case "eda.ru", "www.eda.ru", "eda.rambler.ru", "www.eda.rambler.ru":
		return true
	default:
		return strings.HasSuffix(host, ".eda.ru")
	}
}

func isEdaFixture(host, sourceURL string) bool {
	if isEdaHost(host) {
		return true
	}
	if host == "import-fixtures" || host == "localhost" || host == "127.0.0.1" || host == "example.com" || host == "www.example.com" {
		return strings.Contains(strings.ToLower(sourceURL), "/eda/")
	}
	return false
}

func isPovarenokFixture(host, sourceURL string) bool {
	if host == "povarenok.ru" || host == "www.povarenok.ru" {
		return true
	}
	if host == "import-fixtures" || host == "localhost" || host == "127.0.0.1" || host == "example.com" || host == "www.example.com" {
		return strings.Contains(strings.ToLower(sourceURL), "/povarenok/")
	}
	return false
}

func walk(n *html.Node, fn func(*html.Node)) {
	if n == nil {
		return
	}
	fn(n)
	for c := n.FirstChild; c != nil; c = c.NextSibling {
		walk(c, fn)
	}
}

func classAttr(n *html.Node) string {
	return attrVal(n, "class")
}

func attrVal(n *html.Node, key string) string {
	for _, a := range n.Attr {
		if a.Key == key {
			return a.Val
		}
	}
	return ""
}

func hasAncestorClass(n *html.Node, fragment string) bool {
	for p := n.Parent; p != nil; p = p.Parent {
		if strings.Contains(classAttr(p), fragment) {
			return true
		}
	}
	return false
}

func uniqueNonEmpty(items []string) []string {
	seen := map[string]struct{}{}
	var out []string
	for _, item := range items {
		t := strings.TrimSpace(item)
		if t == "" {
			continue
		}
		if _, ok := seen[t]; ok {
			continue
		}
		seen[t] = struct{}{}
		out = append(out, t)
	}
	return out
}

func parseIngredientLines(lines []string) []map[string]any {
	var out []map[string]any
	for _, line := range lines {
		if m := parseIngredientLine(line); m != nil {
			out = append(out, m)
		}
	}
	return out
}

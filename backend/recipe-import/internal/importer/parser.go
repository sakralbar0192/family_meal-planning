package importer

import (
	"io"
	"strings"

	"golang.org/x/net/html"
)

// ExtractDraft parses minimal fields from HTML (title + placeholder ingredients for MVP).
func ExtractDraft(htmlDoc string, sourceURL string) (title string, steps []string, ingredients []map[string]any) {
	doc, err := html.Parse(strings.NewReader(htmlDoc))
	if err != nil {
		return "", nil, nil
	}
	title = findTitle(doc)
	if title == "" {
		title = findFirstH1(doc)
	}
	if title == "" {
		return "", nil, nil
	}
	steps = []string{}
	ingredients = []map[string]any{}
	return title, steps, ingredients
}

func findTitle(n *html.Node) string {
	if n.Type == html.ElementNode && n.Data == "title" && n.FirstChild != nil {
		return strings.TrimSpace(n.FirstChild.Data)
	}
	for c := n.FirstChild; c != nil; c = c.NextSibling {
		if t := findTitle(c); t != "" {
			return t
		}
	}
	return ""
}

func findFirstH1(n *html.Node) string {
	if n.Type == html.ElementNode && n.Data == "h1" {
		return strings.TrimSpace(textContent(n))
	}
	for c := n.FirstChild; c != nil; c = c.NextSibling {
		if t := findFirstH1(c); t != "" {
			return t
		}
	}
	return ""
}

func textContent(n *html.Node) string {
	if n.Type == html.TextNode {
		return n.Data
	}
	var b strings.Builder
	for c := n.FirstChild; c != nil; c = c.NextSibling {
		b.WriteString(textContent(c))
	}
	return b.String()
}

// ReadAllString reads full body (caller limits size).
func ReadAllString(r io.Reader, max int64) (string, error) {
	lr := &io.LimitedReader{R: r, N: max}
	b, err := io.ReadAll(lr)
	if err != nil {
		return "", err
	}
	return string(b), nil
}

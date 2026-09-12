package importer

import (
	"io"
	"net/url"
	"strings"

	"golang.org/x/net/html"
)

// ExtractDraft parses recipe fields from HTML based on host-specific extractors.
func ExtractDraft(htmlDoc string, sourceURL string) Draft {
	doc, err := html.Parse(strings.NewReader(htmlDoc))
	if err != nil {
		return Draft{}
	}

	host := hostFromURL(sourceURL)
	var d *Draft

	switch {
	case isEdaFixture(host, sourceURL):
		d = extractEdaRuDraft(doc, htmlDoc)
	case isPovarenokFixture(host, sourceURL):
		d = extractPovarenokDraft(doc, htmlDoc)
	default:
		d = extractJSONLDDraft(htmlDoc)
		if d == nil {
			d = &Draft{}
		}
	}

	if d.Title == "" {
		d.Title = findTitle(doc)
	}
	if d.Title == "" {
		d.Title = findOgTitle(doc)
	}
	if d.Title == "" {
		d.Title = findFirstH1(doc)
	}

	if d.Steps == nil {
		d.Steps = []string{}
	}
	if d.Ingredients == nil {
		d.Ingredients = []map[string]any{}
	}

	return *d
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

func findOgTitle(n *html.Node) string {
	if n.Type == html.ElementNode && n.Data == "meta" {
		var prop, content string
		for _, a := range n.Attr {
			switch a.Key {
			case "property":
				prop = a.Val
			case "content":
				content = a.Val
			}
		}
		if strings.EqualFold(prop, "og:title") && strings.TrimSpace(content) != "" {
			return strings.TrimSpace(content)
		}
	}
	for c := n.FirstChild; c != nil; c = c.NextSibling {
		if t := findOgTitle(c); t != "" {
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

func hostFromURL(sourceURL string) string {
	u, err := url.Parse(sourceURL)
	if err != nil {
		return ""
	}
	return strings.ToLower(strings.Split(u.Host, ":")[0])
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

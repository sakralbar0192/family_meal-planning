package importer

import (
	"context"
	"fmt"
	"net/http"
	"net/url"
	"strings"
	"time"
)

const maxBodyBytes = 2 << 20 // 2 MiB

type Service struct {
	Client       *http.Client
	AllowedHosts map[string]struct{}
	FetchTimeout time.Duration
}

func (s *Service) ImportByURL(ctx context.Context, rawURL string) (draft map[string]any, status int, code, msg string) {
	u, err := url.Parse(rawURL)
	if err != nil || u.Scheme == "" || u.Host == "" {
		return nil, 400, "INVALID_URL", "URL is invalid."
	}
	host := strings.ToLower(strings.Split(u.Host, ":")[0])
	if _, ok := s.AllowedHosts[host]; !ok {
		return nil, 400, "URL_NOT_ALLOWED", "Host is not on the import allowlist."
	}

	ctx, cancel := context.WithTimeout(ctx, s.FetchTimeout)
	defer cancel()

	req, err := http.NewRequestWithContext(ctx, http.MethodGet, rawURL, nil)
	if err != nil {
		return nil, 422, "FETCH_FAILED", err.Error()
	}
	req.Header.Set("User-Agent", "family-meal-planning-recipe-import/1.0")

	resp, err := s.Client.Do(req)
	if err != nil {
		if ctx.Err() == context.DeadlineExceeded {
			return nil, 504, "UPSTREAM_TIMEOUT", "Timed out fetching the recipe page."
		}
		return nil, 422, "FETCH_FAILED", "Could not fetch the URL."
	}
	defer resp.Body.Close()

	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		return nil, 422, "FETCH_FAILED", fmt.Sprintf("HTTP status %d.", resp.StatusCode)
	}

	body, err := ReadAllString(resp.Body, maxBodyBytes)
	if err != nil {
		return nil, 422, "PARSE_FAILED", "Could not read response body."
	}

	title, steps, ingredients := ExtractDraft(body, rawURL)
	if title == "" {
		return nil, 422, "PARSE_FAILED", "Could not extract a recipe title from the page."
	}

	draft = map[string]any{
		"title":        title,
		"steps":        steps,
		"ingredients":  ingredients,
		"sourceUrl":    rawURL,
		"cookTimeMinutes": nil,
		"mealCategory":    nil,
		"nutrition":       nil,
		"imageUrl":        nil,
	}
	return draft, 200, "", ""
}

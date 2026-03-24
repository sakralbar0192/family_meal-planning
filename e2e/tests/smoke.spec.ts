import { test, expect } from '@playwright/test';

/**
 * Placeholder until the app is served in CI (docker compose + BFF + frontend).
 * Keeps Playwright wired; extend with UC 1–3 from docs/business-doc.md.
 */
test.describe('smoke', () => {
  test('project placeholder passes', async () => {
    expect(1 + 1).toBe(2);
  });
});

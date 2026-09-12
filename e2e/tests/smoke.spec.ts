import { test, expect } from '@playwright/test';
import { isBffHealthy } from '../helpers/bff';

test.describe('smoke', () => {
  test('login page renders when frontend is up', async ({ page, request }) => {
    await page.goto('/login');
    await expect(page.getByRole('heading', { name: /вход/i })).toBeVisible({ timeout: 15_000 });

    const healthy = await isBffHealthy(request);
    if (healthy) {
      await expect(page.getByRole('button', { name: /войти/i })).toBeEnabled();
    }
  });
});

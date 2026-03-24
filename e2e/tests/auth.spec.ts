import { test, expect, type APIRequestContext } from '@playwright/test';

async function isBffHealthy(request: APIRequestContext): Promise<boolean> {
  const base = (process.env.E2E_BFF_BASE_URL ?? 'http://localhost:8080/bff/v1').replace(/\/$/, '');
  try {
    const res = await request.get(`${base}/health`, { timeout: 5000 });
    return res.status() === 200;
  } catch {
    return false;
  }
}

/**
 * Сквозной UI: регистрация + авто-вход (см. host RegisterView).
 * Требует: compose + фронт; без BFF тест пропускается.
 */
test.describe('auth UI', () => {
  test('регистрация показывает баннер сессии', async ({ page, request }) => {
    const healthy = await isBffHealthy(request);
    const requireBff = process.env.E2E_FULL_STACK === '1' || process.env.E2E_REQUIRE_BFF === '1';
    if (!healthy && requireBff) {
      expect(healthy, 'В режиме full-stack BFF должен отвечать на /health').toBe(true);
      return;
    }
    if (!healthy) {
      test.skip(
        true,
        'BFF недоступен. Поднимите docker compose и задайте E2E_BFF_BASE_URL при необходимости.',
      );
    }

    const email = `e2e_${Date.now()}@example.com`;
    const password = 'e2e-secret12';

    await page.goto('/register');
    await page.getByTestId('register-email').fill(email);
    await page.getByTestId('register-password').fill(password);
    await page.getByTestId('register-submit').click();

    await expect(page).toHaveURL('/');
    await expect(page.getByTestId('session-banner')).toBeVisible({ timeout: 20_000 });
  });
});

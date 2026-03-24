import { test, expect } from '@playwright/test';
import { isBffHealthy, requiresBff } from '../helpers/bff';

/**
 * Сквозной UI: регистрация + авто-вход (см. host RegisterView).
 * Требует: compose + фронт; без BFF тест пропускается.
 */
test.describe('auth UI', () => {
  test('регистрация показывает баннер сессии', async ({ page, request }) => {
    const healthy = await isBffHealthy(request);
    const requireBff = requiresBff();
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

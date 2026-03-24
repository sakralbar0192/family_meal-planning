import { test, expect } from '@playwright/test';
import { isBffHealthy, requiresBff } from '../helpers/bff';

/**
 * Короткий smoke без регистрации: BFF жив, shell отдаёт страницу.
 * В CI без стека — пропуск; в e2e-stack.yml с E2E_FULL_STACK=1 — обязательно.
 */
test.describe('smoke stack (no auth)', () => {
  test('GET /bff/v1/health → 200', async ({ request }) => {
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
      return;
    }
    expect(healthy, 'BFF /health').toBe(true);
  });

  test('host: страница входа открывается', async ({ page, request }) => {
    const healthy = await isBffHealthy(request);
    const requireBff = requiresBff();
    if (!healthy && requireBff) {
      expect(healthy, 'В режиме full-stack BFF должен отвечать на /health').toBe(true);
      return;
    }
    if (!healthy) {
      test.skip(true, 'BFF недоступен — полный smoke не запускаем.');
      return;
    }

    await page.goto('/login');
    await expect(page.getByTestId('login-email')).toBeVisible({ timeout: 15_000 });
  });
});

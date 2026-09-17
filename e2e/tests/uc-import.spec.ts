import { test, expect } from '@playwright/test';
import { isBffHealthy, requiresBff } from '../helpers/bff';
import { registerAndLandHome } from '../helpers/session';

const FIXTURE_EDA_URL = 'http://import-fixtures/eda/borsch.html';

test.describe('UC-1 импорт рецепта', () => {
  test('импорт фикстуры eda.ru → редактор → библиотека', async ({ page, request }) => {
    const healthy = await isBffHealthy(request);
    const requireBff = requiresBff();
    if (!healthy && requireBff) {
      expect(healthy, 'В режиме full-stack BFF должен отвечать на /health').toBe(true);
      return;
    }
    if (!healthy) {
      test.skip(true, 'BFF недоступен. Поднимите docker compose и превью фронта.');
      return;
    }

    const email = `e2e_import_${Date.now()}@example.com`;
    const password = 'e2e-secret12';

    await registerAndLandHome(page, email, password);
    await page.goto('/recipes/import');

    await page.getByPlaceholder('https://eda.ru/recepty/').fill(FIXTURE_EDA_URL);
    await page.getByRole('button', { name: 'Импортировать' }).click();

    await expect(page).toHaveURL(/\/recipes\/new/, { timeout: 30_000 });
    await expect(page.getByLabel('Название *')).toHaveValue('Борщ классический', { timeout: 15_000 });

    await page.getByRole('button', { name: 'Сохранить' }).click();
    await expect(page).toHaveURL(/\/recipes\/[0-9a-f-]{36}/i, { timeout: 25_000 });
    await expect(page.getByRole('img', { name: 'Борщ классический' })).toBeVisible({ timeout: 15_000 });

    await page.goto('/recipes');
    await expect(page.getByText('Борщ классический')).toBeVisible({ timeout: 15_000 });
  });

  test('отклоняет URL с неразрешённого домена', async ({ page, request }) => {
    const healthy = await isBffHealthy(request);
    const requireBff = requiresBff();
    if (!healthy && requireBff) {
      expect(healthy, 'В режиме full-stack BFF должен отвечать на /health').toBe(true);
      return;
    }
    if (!healthy) {
      test.skip(true, 'BFF недоступен. Поднимите docker compose и превью фронта.');
      return;
    }

    const email = `e2e_import_deny_${Date.now()}@example.com`;
    const password = 'e2e-secret12';

    await registerAndLandHome(page, email, password);
    await page.goto('/recipes/import');

    await page.getByPlaceholder('https://eda.ru/recepty/').fill('https://example.com/recipe');
    await page.getByRole('button', { name: 'Импортировать' }).click();

    await expect(page.getByTestId('import-error')).toBeVisible({ timeout: 15_000 });
    await expect(page.getByTestId('import-error')).toContainText(/не в списке разрешённых/i);
    await expect(page).toHaveURL(/\/recipes\/import/);
  });
});

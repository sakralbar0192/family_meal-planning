import { test, expect } from '@playwright/test';
import { isBffHealthy, requiresBff } from '../helpers/bff';
import { createRecipeViaBffCookie, parseWeekStartFromRange, registerAndLandHome } from '../helpers/session';

test.describe('UC-2 / UC-3 план → список покупок', () => {
  test('UC-2: рецепт в слот «Ужин» понедельника; UC-3: список содержит ингредиент', async ({
    page,
    context,
    request,
  }) => {
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

    const email = `e2e_uc_${Date.now()}@example.com`;
    const password = 'e2e-secret12';
    const recipeTitle = `E2E блюдо ${Date.now()}`;

    await registerAndLandHome(page, email, password);
    const { id: recipeId } = await createRecipeViaBffCookie(page, recipeTitle);
    expect(recipeId).toBeTruthy();

    await page.goto('/planner');
    await expect(page.getByText('Загрузка плана…')).toBeHidden({ timeout: 30_000 });

    const rangeText = await page.getByTestId('planner-week-range').textContent();
    expect(rangeText).toBeTruthy();
    const monday = parseWeekStartFromRange(rangeText!);

    await page.getByTestId('planner-active-slot').selectOption({ label: `${monday} — Ужин` });

    const row = page.getByTestId('planner-sidebar-recipe-row').filter({ hasText: recipeTitle });
    await expect(row).toBeVisible({ timeout: 15_000 });
    await row.getByRole('button', { name: 'В слот' }).click();

    await expect(page.locator('.slot-recipes').filter({ hasText: recipeTitle })).toBeVisible({
      timeout: 15_000,
    });

    await page.getByTestId('planner-build-shopping-list').click();
    await expect(page).toHaveURL(/\/shopping\/[^/]+$/i, { timeout: 25_000 });
    await expect(page.getByTestId('shopping-period')).toBeVisible();

    await expect(page.getByTestId('shopping-empty-state')).toBeHidden();
    await expect(page.getByTestId('shopping-line').filter({ hasText: 'Мука' })).toBeVisible({
      timeout: 15_000,
    });

    await context.grantPermissions(['clipboard-read', 'clipboard-write']);
    await page.getByTestId('shopping-copy-list').click();
    const clip = await page.evaluate(() => navigator.clipboard.readText());
    expect(clip).toContain('Мука');
  });

  test('UC-3 альтернатива: пустой список без назначений в плане', async ({ page, request }) => {
    const healthy = await isBffHealthy(request);
    const requireBff = requiresBff();
    if (!healthy && requireBff) {
      expect(healthy, 'В режиме full-stack BFF должен отвечать на /health').toBe(true);
      return;
    }
    if (!healthy) {
      test.skip(true, 'BFF недоступен.');
      return;
    }

    const email = `e2e_empty_${Date.now()}@example.com`;
    const password = 'e2e-secret12';

    await registerAndLandHome(page, email, password);
    await page.goto('/planner');
    await expect(page.getByText('Загрузка плана…')).toBeHidden({ timeout: 30_000 });

    await expect(page.getByTestId('planner-shopping-date-from')).not.toHaveValue('');
    await page.getByTestId('planner-build-shopping-list').click();
    await expect(page).toHaveURL(/\/shopping\//, { timeout: 25_000 });

    await expect(page.getByTestId('shopping-empty-state')).toBeVisible({ timeout: 15_000 });
  });
});

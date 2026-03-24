import { expect, type Page } from '@playwright/test';
import { bffBaseUrl } from './bff';

export async function registerAndLandHome(page: Page, email: string, password: string): Promise<void> {
  await page.goto('/register');
  await page.getByTestId('register-email').fill(email);
  await page.getByTestId('register-password').fill(password);
  await page.getByTestId('register-submit').click();
  await expect(page).toHaveURL('/');
  await expect(page.getByTestId('session-banner')).toBeVisible({ timeout: 20_000 });
}

export async function createRecipeViaBffCookie(page: Page, title: string): Promise<{ id: string }> {
  const base = bffBaseUrl();
  return page.evaluate(
    async ({ url, body }) => {
      const r = await fetch(`${url}/recipes`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
      const text = await r.text();
      if (!r.ok) {
        throw new Error(`POST /recipes ${r.status}: ${text}`);
      }
      return JSON.parse(text) as { id: string };
    },
    {
      url: base,
      body: {
        title,
        ingredients: [{ name: 'Мука пшеничная', productCategory: 'бакалея', quantity: 500, unit: 'г' }],
        steps: ['Смешать'],
      },
    },
  );
}

/** Первая дата недели (понедельник) из «YYYY-MM-DD — YYYY-MM-DD». */
export function parseWeekStartFromRange(text: string): string {
  const part = text.split('—')[0]?.trim() ?? '';
  return part;
}

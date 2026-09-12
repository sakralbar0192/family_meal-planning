import { test, expect } from '@playwright/test';
import { isBffHealthy } from '../helpers/bff';
import { registerAndLandHome } from '../helpers/session';

const VIEWPORTS = [
  { name: 'mobile', width: 390, height: 844 },
  { name: 'tablet', width: 768, height: 1024 },
  { name: 'desktop', width: 1200, height: 900 },
] as const;

test.describe('Viewport screenshots', () => {
  for (const vp of VIEWPORTS) {
    test(`recipes library @ ${vp.name}`, async ({ page, request }) => {
      const healthy = await isBffHealthy(request);
      if (!healthy) {
        test.skip(true, 'BFF недоступен.');
        return;
      }

      await page.setViewportSize({ width: vp.width, height: vp.height });
      const email = `e2e_vp_${vp.name}_${Date.now()}@example.com`;
      await registerAndLandHome(page, email, 'e2e-secret12');
      await page.goto('/recipes');
      await expect(page.getByTestId('nav-recipes')).toBeVisible({ timeout: 15_000 });
      await expect(page).toHaveScreenshot(`library-${vp.name}.png`, {
        fullPage: true,
        maxDiffPixelRatio: 0.05,
      });
    });
  }
});

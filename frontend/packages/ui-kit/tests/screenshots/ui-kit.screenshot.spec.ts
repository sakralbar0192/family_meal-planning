import { expect, test } from '@playwright/test';

const viewports = [
  { name: 'mobile', width: 390, height: 844 },
  { name: 'tablet', width: 768, height: 1024 },
  { name: 'desktop', width: 1280, height: 900 },
];

const stories = [
  '/?path=/story/ui-kit-uibutton--primary',
  '/?path=/story/ui-kit-uiinput--default',
  '/?path=/story/ui-kit-uimodalshell--default',
  '/?path=/story/ui-kit-uirecipecard--default',
];

for (const viewport of viewports) {
  for (const storyPath of stories) {
    test(`${viewport.name} screenshot ${storyPath}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await page.goto(storyPath);
      await page.waitForLoadState('networkidle');
      const screenshot = await page.screenshot({ fullPage: true });
      expect(screenshot.byteLength).toBeGreaterThan(1000);
    });
  }
}

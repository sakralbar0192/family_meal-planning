import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './packages/ui-kit/tests/screenshots',
  retries: 0,
  use: {
    baseURL: 'http://127.0.0.1:6006',
  },
  webServer: {
    command: 'npm run storybook -- --ci --port 6006',
    port: 6006,
    reuseExistingServer: true,
    timeout: 120000,
  },
});

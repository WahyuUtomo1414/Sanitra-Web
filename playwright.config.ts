import { defineConfig, devices } from '@playwright/test';

/**
 * baseURL points at the local `php artisan serve` / Herd instance by default.
 * Override with PLAYWRIGHT_BASE_URL to point at a staging smoke test instead.
 * See docs/testing/playwright-strategy.md §0 for the local-vs-staging decision.
 */
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://localhost:8000';

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',

  use: {
    baseURL,
    trace: 'on-first-retry',
  },

  projects: [
    { name: 'setup', testMatch: /.*\.setup\.ts/ },

    {
      name: 'chromium-super-admin',
      use: { ...devices['Desktop Chrome'], storageState: 'playwright/.auth/super-admin.json' },
      dependencies: ['setup'],
    },
    {
      name: 'chromium-marketing',
      use: { ...devices['Desktop Chrome'], storageState: 'playwright/.auth/marketing.json' },
      dependencies: ['setup'],
    },
    {
      name: 'chromium-finance',
      use: { ...devices['Desktop Chrome'], storageState: 'playwright/.auth/finance.json' },
      dependencies: ['setup'],
    },
    {
      name: 'chromium-teknis',
      use: { ...devices['Desktop Chrome'], storageState: 'playwright/.auth/teknis.json' },
      dependencies: ['setup'],
    },
  ],
});

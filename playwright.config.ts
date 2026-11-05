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
  // Serial even locally: the app runs against a single local dev server
  // (php artisan serve / Herd). Any concurrency — even 2 workers — causes
  // flaky/vanishing Livewire notification toasts under load. Confirmed by
  // testing: workers:1 is reliably green, workers:2 intermittently isn't.
  // See docs/testing/playwright-strategy.md §2.1 gotcha.
  workers: 1,
  reporter: 'html',
  // Default 5s is too tight for Livewire form submits (create/edit/delete
  // notifications) on the local dev server under load — bump assertion
  // timeout, not just action timeouts.
  expect: { timeout: 10_000 },

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

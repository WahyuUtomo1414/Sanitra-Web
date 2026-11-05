import { defineConfig, devices } from '@playwright/test';

const baseURL = 'http://127.0.0.1:8011';

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

  webServer: {
    command: 'bash tests/e2e/start-server.sh',
    url: `${baseURL}/dashboard/login`,
    reuseExistingServer: false,
    timeout: 120_000,
  },

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

import { test, expect } from '@playwright/test';

/**
 * Sanity check for the auth setup project (tests/e2e/auth.setup.ts) and
 * playwright.config.ts projects. Not a real feature test — just confirms
 * each role lands on the dashboard already authenticated via storageState.
 */
test('authenticated session reaches the dashboard', async ({ page }) => {
  await page.goto('/dashboard');

  await expect(page).not.toHaveURL(/\/dashboard\/login/);
});

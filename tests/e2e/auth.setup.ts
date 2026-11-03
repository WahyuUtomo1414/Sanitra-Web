import { test as setup, expect } from '@playwright/test';
import { ROLES } from './fixtures/roles';

/**
 * Serial on purpose: Filament's login page rate-limits repeated attempts.
 * Logging in all 4 roles in parallel trips that limiter ("Terlalu banyak
 * permintaan"). Running them one after another avoids it.
 */
setup.describe.configure({ mode: 'serial' });

for (const [roleKey, role] of Object.entries(ROLES)) {
  setup(`authenticate as ${roleKey}`, async ({ page }) => {
    await page.goto('/dashboard/login');

    await page.getByRole('textbox', { name: 'Alamat email' }).fill(role.email);
    await page.getByRole('textbox', { name: 'Kata sandi' }).fill(role.password);
    await page.getByRole('button', { name: 'Masuk' }).click();

    await expect(page).not.toHaveURL(/\/dashboard\/login/);

    await page.context().storageState({ path: role.storageStatePath });
  });
}

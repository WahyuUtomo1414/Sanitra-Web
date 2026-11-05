import { test, expect } from '@playwright/test';

/**
 * Permission matrix (database/seeders/RoleSeeder.php): MsUnit has no entry
 * for Marketing/Finance/Teknis in ROLE_PERMISSIONS — only super_admin has
 * access (synced with every `web` permission). So this resource is
 * super-admin-only; every other role must be denied with Filament's 403 page.
 */
const INDEX_URL = '/dashboard/units';

test.describe('Ms Unit resource', () => {
  test('access follows the permission matrix for the logged-in role', async ({ page }, testInfo) => {
    const isSuperAdmin = testInfo.project.name === 'chromium-super-admin';

    await page.goto(INDEX_URL);

    if (isSuperAdmin) {
      await expect(page.getByRole('heading', { name: 'Unit', exact: true })).toBeVisible();
      await expect(page.getByRole('link', { name: 'Buat Unit' })).toBeVisible();
    } else {
      await expect(page.getByRole('heading', { name: '403' })).toBeVisible();
      await expect(page.getByText('Forbidden')).toBeVisible();
    }
  });

  test('super admin can create, edit, and delete a unit', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'chromium-super-admin', 'Unit is super-admin-only, covered once');

    const code = `E2E${Date.now().toString().slice(-6)}`;

    await page.goto(INDEX_URL);
    await page.getByRole('link', { name: 'Buat Unit' }).click();

    await page.getByRole('textbox', { name: 'Kode' }).fill(code);
    await page.getByRole('textbox', { name: 'Nama' }).fill(`Unit ${code}`);
    await page.getByRole('combobox', { name: 'Tipe' }).selectOption({ label: 'Barang' });
    await page.getByRole('button', { name: 'Buat', exact: true }).click();

    await expect(page.getByText('Data berhasil dibuat').first()).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Lihat Unit' })).toBeVisible();

    await page.getByRole('link', { name: 'Ubah' }).click();
    await page.getByRole('textbox', { name: 'Nama' }).fill(`Unit ${code} Updated`);
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await expect(page.getByText('Data berhasil disimpan').first()).toBeVisible();

    await page.goto(INDEX_URL);
    await page.getByRole('searchbox', { name: 'Cari' }).fill(code);

    const row = page.getByRole('row', { name: new RegExp(code) });
    await expect(row).toBeVisible();
    await row.getByRole('button', { name: 'Hapus' }).click();
    await page
      .getByRole('alertdialog', { name: 'Hapus Unit' })
      .getByRole('button', { name: 'Hapus', exact: true })
      .click();

    await expect(page.getByText('Data berhasil dihapus').first()).toBeVisible();
  });
});

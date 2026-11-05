import { test, expect } from '@playwright/test';

/**
 * Permission matrix (database/seeders/RoleSeeder.php): Marketing gets
 * ViewAny:MsItemCategory + View:MsItemCategory only (read-only, no Create/
 * Update/Delete). Finance and Teknis have no entry at all. super_admin has
 * full access (synced with every `web` permission).
 */
const INDEX_URL = '/dashboard/item-categories';

test.describe('Ms Item Category resource', () => {
  test('access follows the permission matrix for the logged-in role', async ({ page }, testInfo) => {
    const role = testInfo.project.name;

    await page.goto(INDEX_URL);

    if (role === 'chromium-super-admin' || role === 'chromium-marketing') {
      await expect(page.getByRole('heading', { name: 'Kategori Item', exact: true })).toBeVisible();

      if (role === 'chromium-super-admin') {
        await expect(page.getByRole('link', { name: 'Buat Kategori Item' })).toBeVisible();
      } else {
        // Marketing: read-only — no create link, no Ubah/Hapus row actions.
        await expect(page.getByRole('link', { name: 'Buat Kategori Item' })).toHaveCount(0);
        await expect(page.getByRole('link', { name: 'Ubah' })).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Hapus' })).toHaveCount(0);
      }
    } else {
      await expect(page.getByRole('heading', { name: '403' })).toBeVisible();
      await expect(page.getByText('Forbidden')).toBeVisible();
    }
  });

  test('super admin can create, edit, and delete a category with auto-generated code', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'chromium-super-admin', 'CRUD is super-admin-only here, covered once');

    const name = `E2E Category ${Date.now().toString().slice(-6)}`;

    await page.goto(INDEX_URL);
    await page.getByRole('link', { name: 'Buat Kategori Item' }).click();

    // Code field is disabled/auto-generated — must not be filled manually.
    await expect(page.getByRole('textbox', { name: 'Kode' })).toBeDisabled();

    await page.getByRole('textbox', { name: 'Nama Kategori' }).fill(name);
    await page.getByRole('button', { name: 'Buat', exact: true }).click();

    await expect(page.getByText('Data berhasil dibuat').first()).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Lihat Kategori Item' })).toBeVisible();

    await page.getByRole('link', { name: 'Ubah' }).click();
    await page.getByRole('textbox', { name: 'Nama Kategori' }).fill(`${name} Updated`);
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await expect(page.getByText('Data berhasil disimpan').first()).toBeVisible();

    await page.goto(INDEX_URL);
    await page.getByRole('searchbox', { name: 'Cari' }).fill(name);

    const row = page.getByRole('row', { name: new RegExp(name) });
    await expect(row).toBeVisible();
    await row.getByRole('button', { name: 'Hapus' }).click();
    await page
      .getByRole('alertdialog', { name: 'Hapus Kategori Item' })
      .getByRole('button', { name: 'Hapus', exact: true })
      .click();

    await expect(page.getByText('Data berhasil dihapus').first()).toBeVisible();
  });
});

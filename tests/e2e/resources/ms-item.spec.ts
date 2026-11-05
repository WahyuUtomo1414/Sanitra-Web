import { expect, test } from '@playwright/test';
import { registerMasterResourceTests, selectOption } from './master-resource';

registerMasterResourceTests({
  slug: 'items',
  label: 'Item',
  nameField: 'Nama Item',
  access: { 'super-admin': 'write', marketing: 'read' },
  codeIsAutomatic: true,
  fillCreate: async (page, name) => {
    await page.getByRole('textbox', { name: 'Nama Item' }).fill(name);
    await selectOption(page, 'Kategori Item', 'Jasa IPAL');
  },
});

test('first item unit becomes the base unit and cannot be deleted', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'chromium-super-admin', 'Unit management requires write access');

  const name = `E2E Item Unit ${Date.now()}`;
  await page.goto('/dashboard/items/create');
  await page.getByRole('textbox', { name: 'Nama Item' }).fill(name);
  await selectOption(page, 'Kategori Item', 'Jasa IPAL');
  await page.getByRole('button', { name: 'Buat', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Lihat Item' })).toBeVisible();

  await page.getByRole('link', { name: 'Ubah' }).click();
  await expect(page.getByRole('heading', { name: 'Ubah Item' })).toBeVisible();
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
  await page.getByRole('button', { name: 'Tambah Unit' }).click();
  await selectOption(page, 'Unit', 'Paket');
  await page.getByRole('button', { name: 'Buat', exact: true }).click();

  const unitRow = page.getByRole('row', { name: /Paket/ });
  await expect(unitRow).toBeVisible();
  await expect(unitRow.getByRole('button', { name: 'Hapus' })).toHaveCount(0);
});

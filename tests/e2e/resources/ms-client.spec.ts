import { expect, test } from '@playwright/test';
import { registerMasterResourceTests } from './master-resource';

registerMasterResourceTests({
  slug: 'clients',
  label: 'Klien',
  nameField: 'Nama Klien',
  access: { 'super-admin': 'write', marketing: 'write', finance: 'list', teknis: 'read' },
  codeIsAutomatic: true,
  fillCreate: async (page, name) => {
    await page.getByRole('textbox', { name: 'Nama Klien' }).fill(name);
    await page.getByRole('textbox', { name: 'Email Umum' }).fill(`e2e-${Date.now()}@example.test`);
    await page.getByRole('textbox', { name: 'Telepon Umum' }).fill('081234567890');
    await page.getByRole('textbox', { name: 'Alamat' }).fill('Alamat khusus pengujian E2E');
  },
});

test('marketing can add client contacts and documents', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'chromium-marketing', 'Marketing owns client sub-data');
  test.setTimeout(60_000);

  const name = `E2E Client Detail ${Date.now()}`;
  await page.goto('/dashboard/clients/create');
  await page.getByRole('textbox', { name: 'Nama Klien' }).fill(name);
  await page.getByRole('textbox', { name: 'Email Umum' }).fill(`e2e-${Date.now()}@example.test`);
  await page.getByRole('textbox', { name: 'Telepon Umum' }).fill('081234567890');
  await page.getByRole('textbox', { name: 'Alamat' }).fill('Alamat pengujian relasi klien');
  await page.getByRole('button', { name: 'Buat', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Lihat Klien' })).toBeVisible();

  await page.getByRole('link', { name: 'Ubah' }).click();
  await page.getByRole('tab', { name: 'Kontak Klien' }).click();
  await page.getByRole('button', { name: 'Buat ms client contact' }).click();
  await page.getByRole('textbox', { name: 'Nama Kontak' }).fill('Kontak E2E');
  await page.getByRole('textbox', { name: 'Email' }).fill('contact@example.test');
  await page.getByRole('switch', { name: 'Kontak Utama' }).check();
  await page.getByRole('button', { name: 'Buat', exact: true }).click();
  await expect(page.getByRole('row', { name: /Kontak E2E/ })).toBeVisible();

  await page.getByRole('tab', { name: 'Dokumen Klien' }).click();
  await page.getByRole('button', { name: 'Buat ms document' }).click();
  await page.getByRole('textbox', { name: 'Nama Dokumen' }).fill('Dokumen E2E');
  await page.locator('input[type="file"]').setInputFiles({
    name: 'e2e.png',
    mimeType: 'image/png',
    buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lXcAAAAASUVORK5CYII=', 'base64'),
  });
  await page.getByRole('button', { name: 'Buat', exact: true }).click();
  await expect(page.getByRole('row', { name: /Dokumen E2E/ })).toBeVisible();
});

import { registerMasterResourceTests, selectOption } from './master-resource';

registerMasterResourceTests({
  slug: 'employees',
  label: 'Karyawan',
  nameField: 'Nama',
  access: { 'super-admin': 'write' },
  codeIsAutomatic: true,
  fillCreate: async (page, name) => {
    await page.getByRole('textbox', { name: /^Nama\*?$/ }).fill(name);
    await selectOption(page, 'Jabatan', 'Teknis');
    await page.getByRole('textbox', { name: 'Email' }).fill(`e2e-${Date.now()}@example.test`);
    await page.getByRole('textbox', { name: 'Telepon' }).fill('081234567890');
    await selectOption(page, 'Perusahaan', 'Sanitra Sapta Utama');
  },
});

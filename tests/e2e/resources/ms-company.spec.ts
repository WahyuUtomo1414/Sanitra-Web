import { registerMasterResourceTests } from './master-resource';

registerMasterResourceTests({
  slug: 'companies',
  label: 'Perusahaan',
  nameField: 'Nama',
  access: { 'super-admin': 'write' },
  codeIsAutomatic: true,
  fillCreate: async (page, name) => {
    await page.getByRole('textbox', { name: /^Nama\*?$/ }).fill(name);
    await page.getByRole('textbox', { name: 'Nama Legal' }).fill(`PT ${name}`);
    await page.getByRole('textbox', { name: 'Email' }).fill(`e2e-${Date.now()}@example.test`);
    await page.getByRole('textbox', { name: 'Telepon' }).fill('081234567890');
    await page.getByRole('textbox', { name: 'Alamat' }).fill('Alamat perusahaan pengujian E2E');
    await page.getByRole('textbox', { name: 'Kode Pos' }).fill('12345');
  },
});

import { registerMasterResourceTests } from './master-resource';

registerMasterResourceTests({
  slug: 'bank-accounts',
  label: 'Rekening Bank',
  nameField: 'Label Rekening',
  access: { 'super-admin': 'write', finance: 'read' },
  codeIsAutomatic: true,
  fillCreate: async (page, name) => {
    await page.getByRole('textbox', { name: 'Label Rekening' }).fill(name);
    await page.getByRole('textbox', { name: 'Nama Bank' }).fill('Bank E2E');
    await page.getByRole('textbox', { name: 'Nomor Rekening' }).fill(`99${Date.now().toString().slice(-10)}`);
    await page.getByRole('textbox', { name: 'Nama Pemilik Rekening' }).fill('PT E2E');
  },
});

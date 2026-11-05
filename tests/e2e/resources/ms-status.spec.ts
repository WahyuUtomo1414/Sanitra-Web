import { registerMasterResourceTests, selectOption } from './master-resource';

registerMasterResourceTests({
  slug: 'statuses',
  label: 'Status',
  nameField: 'Nama',
  access: { 'super-admin': 'write' },
  fillCreate: async (page, name) => {
    await selectOption(page, 'Tipe Entitas', 'quotation');
    await page.getByRole('textbox', { name: 'Kode' }).fill(`e2e_${Date.now()}`);
    await page.getByRole('textbox', { name: /^Nama\*?$/ }).fill(name);
  },
});

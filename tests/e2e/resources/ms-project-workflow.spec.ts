import { registerMasterResourceTests, selectOption } from './master-resource';

registerMasterResourceTests({
  slug: 'project-workflows',
  label: 'Workflow Project',
  nameField: 'Nama Tahap',
  access: { 'super-admin': 'write', marketing: 'read', teknis: 'read' },
  fillCreate: async (page, name) => {
    await selectOption(page, 'Tipe Project', 'Konsultan');
    await page.getByRole('textbox', { name: 'Nama Tahap' }).fill(name);
    await page.getByRole('spinbutton', { name: 'Urutan' }).fill('99');
    await page.getByRole('spinbutton', { name: 'Bobot Progress' }).fill('0');
  },
});

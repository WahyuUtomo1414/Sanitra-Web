import { registerMasterResourceTests } from './master-resource';

registerMasterResourceTests({
  slug: 'project-types',
  label: 'Tipe Project',
  nameField: 'Nama Tipe Project',
  access: { 'super-admin': 'write', marketing: 'read', teknis: 'read' },
  codeIsAutomatic: true,
  fillCreate: async (page, name) => {
    await page.getByRole('textbox', { name: 'Nama Tipe Project' }).fill(name);
  },
});

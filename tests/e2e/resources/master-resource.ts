import { expect, test, type Page } from '@playwright/test';

type Role = 'super-admin' | 'marketing' | 'finance' | 'teknis';

type MasterResource = {
  slug: string;
  label: string;
  nameField: string;
  access: Partial<Record<Role, 'write' | 'read' | 'list'>>;
  fillCreate: (page: Page, name: string) => Promise<void>;
  codeIsAutomatic?: boolean;
};

export function registerMasterResourceTests(resource: MasterResource): void {
  const indexUrl = `/dashboard/${resource.slug}`;

  test.describe(`${resource.label} master resource`, () => {
    test('index access and actions follow the role permissions', async ({ page }, testInfo) => {
      const role = testInfo.project.name.replace('chromium-', '') as Role;
      const access = resource.access[role];

      await page.goto(indexUrl);

      if (!access) {
        await expect(page.getByRole('heading', { name: '403' })).toBeVisible();
        await expect(page.getByText('Forbidden')).toBeVisible();
        return;
      }

      await expect(page.getByRole('heading', { name: resource.label, exact: true })).toBeVisible();
      if (access === 'write') {
        await expect(page.getByRole('link', { name: `Buat ${resource.label}` })).toBeVisible();
      } else {
        await expect(page.getByRole('link', { name: `Buat ${resource.label}` })).toHaveCount(0);
        await expect(page.getByRole('link', { name: 'Ubah' })).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Hapus' })).toHaveCount(0);
      }
      if (access === 'list') {
        await expect(page.getByRole('link', { name: 'Lihat' })).toHaveCount(0);
      }
    });

    test('super admin can create, edit, find, and delete a record', async ({ page }, testInfo) => {
      test.skip(testInfo.project.name !== 'chromium-super-admin', 'CRUD runs once as super admin');

      const name = `E2E ${resource.label} ${Date.now()}`;
      await page.goto(indexUrl);
      await page.getByRole('link', { name: `Buat ${resource.label}` }).click();

      if (resource.codeIsAutomatic) {
        await expect(page.getByRole('textbox', { name: 'Kode', exact: true })).toBeDisabled();
      }
      await resource.fillCreate(page, name);
      await page.getByRole('button', { name: 'Buat', exact: true }).click();

      await expect(page.getByText('Data berhasil dibuat').first()).toBeVisible();
      await expect(page.getByRole('heading', { name: `Lihat ${resource.label}` })).toBeVisible();
      await expect(page.getByText(name, { exact: true }).first()).toBeVisible();

      await page.getByRole('link', { name: 'Ubah' }).click();
      const nameLabel = new RegExp(`^${resource.nameField.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\*?$`);
      await page.getByRole('textbox', { name: nameLabel }).fill(`${name} Updated`);
      await page.getByRole('button', { name: 'Simpan', exact: true }).click();
      await expect(page.getByText('Data berhasil disimpan').first()).toBeVisible();

      await page.goto(indexUrl);
      await page.getByRole('searchbox', { name: 'Cari' }).fill(`${name} Updated`);
      const row = page.getByRole('row', { name: new RegExp(`${name} Updated`) });
      await expect(row).toBeVisible();
      await row.getByRole('button', { name: 'Hapus' }).click();
      await page.getByRole('alertdialog', { name: `Hapus ${resource.label}` })
        .getByRole('button', { name: 'Hapus', exact: true }).click();
      await expect(page.getByText('Data berhasil dihapus').first()).toBeVisible();
      await expect(row).toHaveCount(0);
    });
  });
}

export async function selectOption(page: Page, label: string, option: string): Promise<void> {
  const select = page.getByRole('combobox', { name: label });
  if (await select.evaluate((element) => element instanceof HTMLSelectElement)) {
    await select.selectOption({ label: option });
    return;
  }

  await select.click();
  await page.getByRole('option', { name: option, exact: true }).click();
}

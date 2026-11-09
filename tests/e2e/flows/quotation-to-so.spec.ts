import { randomUUID } from 'node:crypto';
import { expect, test, type Locator, type Page } from '@playwright/test';
import { selectOption } from '../resources/master-resource';

const quotationIndex = '/dashboard/quotations';
const orderIndex = '/dashboard/sales-orders';
const itemName = 'Jasa Survei IPAL';
const unitName = 'Hari';
const quotationCodePattern = /^QUO\/\d{2}\/\d{2}\/\d{4,}$/;
const orderCodePattern = /^SO\/\d{2}\/\d{2}\/\d{4,}$/;

/**
 * Filament's `role="dialog"` element is `.fi-modal.fi-absolute-positioning-context`
 * — a positioning context whose children are absolutely positioned, so the element
 * itself has no bounding box. That makes `filter({ visible: true })` never match it
 * even while the modal is fully open and interactive. Scope on Filament's own open
 * marker (`fi-modal-open`, bound by `x-bind:class` in the modal blade) instead;
 * closed modals are `display:none` via `x-show`, so they can't match either.
 */
function visibleDialog(page: Page): Locator {
  return page.locator(
    '.fi-modal.fi-modal-open[role="dialog"], .fi-modal.fi-modal-open[role="alertdialog"]',
  );
}

async function submitCreate(container: Page | Locator): Promise<void> {
  const button = container.getByRole('button', { name: 'Buat', exact: true });
  await expect(button).toBeEnabled();
  await button.click();
}

async function addTerm(page: Page, name: string, percentage: string, threshold: string): Promise<void> {
  await page.getByRole('button', { name: /^Buat skema termin$/i }).click();

  const dialog = visibleDialog(page);
  await dialog.getByRole('textbox', { name: 'Nama Termin' }).fill(name);
  await dialog.getByRole('spinbutton', { name: 'Persentase' }).fill(percentage);
  await dialog.getByRole('spinbutton', { name: 'Threshold Progres' }).fill(threshold);
  await submitCreate(dialog);

  await expect(dialog).toBeHidden();
  const row = page.getByRole('row').filter({ has: page.getByRole('cell', { name, exact: true }) });
  await expect(row.getByRole('cell', { name: percentage + '%', exact: true })).toBeVisible();
}

async function createDraftQuotation(page: Page) {
  const suffix = randomUUID();
  const clientName = 'Klien E2E Quotation ' + suffix;

  await page.goto(quotationIndex + '/create');
  await page.getByRole('button', { name: /Tambah Klien/i }).click();

  const clientDialog = visibleDialog(page);
  await clientDialog.getByRole('textbox', { name: 'Nama Klien' }).fill(clientName);
  await clientDialog.getByRole('textbox', { name: 'Email Umum' }).fill(suffix + '@example.test');
  await clientDialog.getByRole('textbox', { name: 'Telepon Umum' }).fill('081234567890');
  await clientDialog.getByRole('textbox', { name: 'Alamat' }).fill('Alamat Klien pengujian Quotation');
  await submitCreate(clientDialog);

  await expect(clientDialog).toBeHidden();
  await expect(page.getByRole('combobox', { name: /^Klien\*?$/ }).getByText(clientName, { exact: true })).toBeVisible();
  await selectOption(page, 'Tipe Proyek', 'Konsultan');
  await submitCreate(page);
  await expect(page).toHaveURL(/\/dashboard\/quotations\/[^/]+\/edit$/);

  const editUrl = page.url();
  const viewUrl = editUrl.replace(/\/edit$/, '');
  const quotationNumber = page.getByRole('textbox', { name: 'Nomor Quotation' });
  await expect(quotationNumber).toHaveValue(quotationCodePattern);
  const code = await quotationNumber.inputValue();

  await page.getByRole('tab', { name: 'Item', exact: true }).click();
  await page.getByRole('button', { name: /^Buat item$/i }).click();

  const itemDialog = visibleDialog(page);
  await selectOption(page, 'Item', itemName);
  await selectOption(page, 'Unit', unitName);
  await itemDialog.getByRole('spinbutton', { name: 'Jumlah' }).fill('2');
  await itemDialog.getByRole('textbox', { name: 'Harga Satuan' }).fill('1000000');
  await submitCreate(itemDialog);

  await expect(itemDialog).toBeHidden();
  await expect(page.getByRole('row').filter({ hasText: itemName })).toContainText('Rp 2.000.000');

  await page.getByRole('tab', { name: 'Skema Termin' }).click();
  await addTerm(page, 'DP', '30', '0');
  await addTerm(page, 'Pelunasan', '70', '100');

  await page.goto(viewUrl);
  await expect(page.getByText('Draft', { exact: true }).first()).toBeVisible();
  await expect(page.getByText(clientName, { exact: true })).toBeVisible();
  await expect(page.getByText('Rp 2.000.000', { exact: true }).first()).toBeVisible();

  return { clientName, code, editUrl, viewUrl };
}

async function confirmAction(page: Page, action: string, confirmation: string): Promise<void> {
  await page.getByRole('button', { name: action, exact: true }).click();
  await visibleDialog(page).getByRole('button', { name: confirmation, exact: true }).click();
}

async function sendQuotation(page: Page): Promise<void> {
  await confirmAction(page, 'Kirim Penawaran', 'Ya, kirim');
  await expect(page.getByText('Dikirim', { exact: true })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Setujui Penawaran', exact: true })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Ubah Detail', exact: true })).toBeHidden();
}

async function searchOrders(page: Page, quotationCode: string): Promise<Locator> {
  await page.goto(orderIndex);
  await page.getByRole('searchbox', { name: 'Cari' }).fill(quotationCode);
  return page.getByRole('row').filter({ has: page.getByRole('cell', { name: quotationCode, exact: true }) });
}

async function expectNoOrder(page: Page, quotationCode: string): Promise<void> {
  const rows = await searchOrders(page, quotationCode);
  await expect(page.getByText('Tidak ada data yang ditemukan', { exact: true })).toBeVisible();
  await expect(rows).toHaveCount(0);
}

test.describe('Alur Quotation sampai Sales Order', () => {
  test.beforeEach(({}, testInfo) => {
    test.skip(testInfo.project.name !== 'chromium-marketing', 'Alur Quotation dijalankan sebagai Marketing');
  });
  test.describe.configure({ timeout: 90_000 });

  test('Quotation Ditolak tidak menghasilkan Sales Order', async ({ page }) => {
    const quotation = await createDraftQuotation(page);
    await sendQuotation(page);

    await confirmAction(page, 'Tolak', 'Ya, tolak');
    await expect(page.getByText('Ditolak', { exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Ubah Detail', exact: true })).toBeHidden();
    await expect(page.getByRole('button', { name: 'Revisi', exact: true })).toBeHidden();
    await expect(page.getByRole('button', { name: 'Setujui Penawaran', exact: true })).toBeHidden();

    await expectNoOrder(page, quotation.code);
  });

  test('Quotation direvisi lalu disetujui dan menghasilkan satu Sales Order', async ({ page }) => {
    const quotation = await createDraftQuotation(page);
    await sendQuotation(page);

    await confirmAction(page, 'Revisi', 'Ya, revisi');
    await expect(page.getByRole('link', { name: 'Ubah Detail', exact: true })).toBeVisible();
    await expect(page.getByText('Draft', { exact: true }).first()).toBeVisible();

    const revisedNotes = 'Catatan revisi ' + quotation.code;
    await page.goto(quotation.editUrl);
    await expect(page.getByRole('textbox', { name: 'Nomor Quotation' })).toHaveValue(quotation.code);
    await page.getByRole('textbox', { name: 'Catatan', exact: true }).fill(revisedNotes);
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await expect(page.getByText('Data berhasil disimpan').first()).toBeVisible();

    await page.goto(quotation.viewUrl);
    await expect(page.getByText(revisedNotes, { exact: true })).toBeVisible();
    await sendQuotation(page);
    await expectNoOrder(page, quotation.code);

    await page.goto(quotation.viewUrl);
    await confirmAction(page, 'Setujui Penawaran', 'Ya, setujui');
    await expect(page.getByText('Disetujui', { exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Ubah Detail', exact: true })).toBeHidden();
    await expect(page.getByRole('button', { name: 'Revisi', exact: true })).toBeHidden();
    await expect(page.getByRole('button', { name: 'Setujui Penawaran', exact: true })).toBeHidden();

    const orderRow = await searchOrders(page, quotation.code);
    await expect(orderRow).toHaveCount(1);
    await expect(orderRow.getByRole('cell', { name: orderCodePattern })).toBeVisible();
    await expect(orderRow).toContainText('Menunggu DP');
    await expect(orderRow).toContainText('Rp 2.000.000');
    await orderRow.getByRole('link', { name: 'Lihat', exact: true }).click();

    for (const value of [quotation.code, quotation.clientName, 'PT Sanitra Sapta Utama', 'Konsultan', 'Tanpa PPN', revisedNotes]) {
      await expect(page.getByText(value, { exact: true })).toBeVisible();
    }

    await page.getByRole('tab', { name: 'Item Sales Order' }).click();
    const itemRow = page.getByRole('row').filter({ hasText: itemName });
    await expect(itemRow).toHaveCount(1);
    for (const value of [unitName, '2', 'Rp 1.000.000', 'Rp 0', 'Rp 2.000.000']) {
      await expect(itemRow.getByRole('cell', { name: value, exact: true })).toBeVisible();
    }

    await page.getByRole('tab', { name: 'Skema Termin' }).click();
    const downPayment = page.getByRole('row').filter({ has: page.getByRole('cell', { name: 'DP', exact: true }) });
    await expect(downPayment).toContainText('Siap Ditagih');
    await expect(downPayment).toContainText('Rp 600.000');

    const finalPayment = page.getByRole('row').filter({ has: page.getByRole('cell', { name: 'Pelunasan', exact: true }) });
    await expect(finalPayment).toContainText('Menunggu');
    await expect(finalPayment).toContainText('Rp 1.400.000');
  });
});

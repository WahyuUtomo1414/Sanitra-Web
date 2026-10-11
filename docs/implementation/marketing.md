# Implementation Checklist — Marketing

**Scope:** Full pekerjaan untuk Quotation + Sales Order + Term — migration, model, service, Filament resource, relation manager, custom action, PDF, notification.

**Acuan:** [bisnis-flow/marketing.md](../bisnis-flow/marketing.md), [filament-resource.md](../filament-resource.md), [filament-lifecycle-hook.md](../filament-lifecycle-hook.md)

> **Reminder:** Tiap selesai item, update `docs/work/YYYY-MM-DD.md` (ganti tanggal tiap hari, buat file baru per hari).

## 1. Migration

- [ ] `trans_sales_quotation_hd`
- [ ] `trans_sales_quotation_dt`
- [ ] `trans_sales_order_hd` (unique `quotation_hd_id`, kolom tambahan `payment_status_id`)
- [ ] `trans_sales_order_dt`
- [ ] `trans_sales_term` (FK `quotation_hd_id` wajib, `order_hd_id` nullable)
- [ ] Jalankan `php artisan migrate` dan cek `migrate:status`

## 2. Model Eloquent

- [ ] `TransSalesQuotationHd` + relasi `items`, `terms`, `order`, `client`, `projectType`, `company`, `status`
- [ ] `TransSalesQuotationDt` + relasi `quotation`, `item`, `unit`
- [ ] `TransSalesOrderHd` + relasi `quotation`, `items`, `terms`, `project`, `invoices`, `status`, `paymentStatus`
- [ ] `TransSalesOrderDt` + relasi `order`, `quotationDetail`, `item`, `unit`
- [ ] `TransSalesTerm` + relasi `quotation`, `order`, `status`, `invoiceDetails`
- [ ] Cast `vat_type` (VatType), nominal (`decimal:6`), tanggal (`date`)
- [ ] Pasang `AuditedBySoftDelete` + `SoftDeletes` + (opsional) `LogsMasterActivity`

## 3. Service (`app/Services/`)

- [ ] `Quotation/SendQuotationService` — Draft → Sent, validasi total termin = 100%
- [ ] `Quotation/ApproveQuotationService` — Sent → Approved, dalam DB transaction panggil `CreateSalesOrderFromQuotationService`
- [ ] `Quotation/RejectQuotationService` — Sent → Rejected
- [ ] `Quotation/ReviseQuotationService` — Sent → Draft (histori status tercatat)
- [ ] `SalesOrder/CreateSalesOrderFromQuotationService` — copy HD + DT, bind termin ke `order_hd_id`, hitung `amount`, set status SO (Waiting DP / On Progress)
- [ ] `SalesOrder/CheckTermReadyToBillService` — dipanggil saat progress project berubah; cek termin pending yang melewati threshold
- [ ] `Shared/GeneratePdfService` (atau pakai facade `Pdf::loadView`) untuk render Blade ke PDF

## 4. Filament Resource

### 4.1 TransSalesQuotationHdResource (Group: Penjualan)

- [ ] Properti resource: `$navigationLabel = 'Quotation'`, `$slug = 'quotations'`, icon
- [ ] Form (section Informasi Utama + Relasi + PPN & Diskon + Catatan)
  - [ ] Field `code` disabled dehydrated(false) — di-generate oleh service di hook
  - [ ] Field finansial dihitung otomatis dari items
- [ ] Table: `code`, `transaction_date`, `client.name`, `grand_total`, `status.name` (badge), audit
- [ ] Infolist (section Informasi Utama + Nominal + Audit Data)
- [ ] Page: List, Create, View, Edit
- [ ] CreatePage hook `mutateFormDataBeforeCreate` → generate `code` via `GenerateCodeService`
- [ ] Export Excel

### 4.2 Relation Manager di Quotation

- [ ] `ItemsRelationManager` → `trans_sales_quotation_dt`. Hanya editable saat status Draft.
- [ ] `TermsRelationManager` → `trans_sales_term` (filter `quotation_hd_id`). Hanya editable saat Draft; validasi total 100%.

### 4.3 Custom Action di Quotation

- [ ] `SendAction` — visible status `draft`
- [ ] `ApproveAction` — visible status `sent`, requiresConfirmation + modal description
- [ ] `RejectAction` — visible status `sent`
- [ ] `ReviseAction` — visible status `sent`
- [ ] `PrintPdfAction` — visible status `sent`/`approved`, stream PDF ke browser

### 4.4 TransSalesOrderHdResource (Group: Penjualan)

- [ ] Properti resource: `$navigationLabel = 'Sales Order'`, `$slug = 'sales-orders'`
- [ ] **Tidak ada CreateAction** (SO dibuat otomatis). Hanya List, View, Edit terbatas.
- [ ] Table: `code`, `transaction_date`, `quotation.code`, `client.name`, `grand_total`, `status.name`, `paymentStatus.name` (keduanya badge)
- [ ] Infolist menampilkan ringkasan + status pembayaran
- [ ] Filter table: `status_id`, `payment_status_id`, `client_id`

### 4.5 Relation Manager di Sales Order

- [ ] `ItemsRelationManager` → `trans_sales_order_dt` (read-only)
- [ ] `TermsRelationManager` → `trans_sales_term` filter `order_hd_id`. Edit hanya catatan.
- [ ] `InvoicesRelationManager` → `trans_sales_invoice_hd` (read-only, link ke resource invoice)

## 5. PDF Quotation

- [ ] Install `composer require barryvdh/laravel-dompdf` (jika belum)
- [ ] Blade template `resources/views/pdf/quotation.blade.php`:
  - [ ] Header: logo + nama company penerbit + alamat + kontak
  - [ ] Info client, nomor quotation, tanggal, PIC
  - [ ] Tabel items (no, nama, qty, unit, harga, diskon, total)
  - [ ] Subtotal, VAT, diskon, grand total
  - [ ] Tabel termin (sequence, nama, persentase, threshold, nilai)
  - [ ] Footer: syarat & ketentuan, tanda tangan
- [ ] Trigger via `PrintPdfAction`: `Pdf::loadView('pdf.quotation', ['quotation' => $record])->stream("quotation-{$record->code}.pdf")`
- [ ] Format Rupiah dan tanggal bahasa Indonesia

## 6. Notification

- [ ] `TermReadyToBillNotification` — dikirim ke Finance saat termin DP otomatis Ready to Bill setelah SO dibuat
- [ ] (Opsional) `SalesOrderCreatedNotification` — ke Marketing sebagai konfirmasi

## 7. Test (opsional tapi direkomendasikan)

- [ ] Feature test flow Approve → SO otomatis terbentuk (item disalin, termin bind ke order_hd_id)
- [ ] Feature test validasi total termin 100% saat Send
- [ ] Feature test revisi Sent → Draft
- [ ] Unit test `CreateSalesOrderFromQuotationService` guard duplikat SO

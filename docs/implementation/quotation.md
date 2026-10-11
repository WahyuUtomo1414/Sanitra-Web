# Implementation Checklist — Quotation

**Scope:** Entitas Quotation (`trans_sales_quotation_hd`, `trans_sales_quotation_dt`) termasuk form input, revisi, dan PDF. Pembentukan Sales Order saat Approved dilacak di [sales-order-term.md](sales-order-term.md).

**Acuan:** [bisnis-flow/marketing.md](../bisnis-flow/marketing.md) §2, [filament-resource.md](../filament-resource.md), [filament-lifecycle-hook.md](../filament-lifecycle-hook.md), [database.md](../database.md) §4.13–4.14

> **Reminder:** Tiap selesai item, update `docs/work/YYYY-MM-DD.md` (ganti tanggal tiap hari, buat file baru per hari).

## 1. Schema & Model

- [x] Migration `trans_sales_quotation_hd` (Ran)
- [x] Migration `trans_sales_quotation_dt` (Ran)
- [x] Model `TransSalesQuotationHd` dengan relasi `items`, `terms`, `order`, `client`, `projectType`, `company`, `status`; cast `vat_type` (`VatType`), nominal `decimal:6`, `transaction_date` `date`
- [x] Model `TransSalesQuotationDt` dengan relasi `quotation`, `item`, `unit`; cast nominal `decimal:6`
- [x] Factory `TransSalesQuotationHdFactory` dan `TransSalesQuotationDtFactory`
- [x] Trait `AuditedBySoftDelete` + `SoftDeletes` terpasang
- [x] Enum case `AutoCodePrefix::TransSalesQuotationHd = 'QUO'`
- [x] Enum `QuotationStatus` (`draft`, `sent`, `approved`, `rejected`) + 4 row aktif di `ms_status` tipe `quotation`
- [x] Enum `VatType` (`none`, `include`, `exclude`) terpasang pada cast model

## 2. Service (`app/Services/Quotation/`)

- [ ] `SendQuotationService` — Draft → Sent; validasi total `percentage` termin = 100 (BR-01); minimal 1 item dan 1 termin
- [ ] `ApproveQuotationService` — Sent → Approved; dalam `DB::transaction` panggil `CreateSalesOrderFromQuotationService` (BR-17)
- [ ] `RejectQuotationService` — Sent → Rejected
- [ ] `ReviseQuotationService` — Sent → Draft (histori status tetap tercatat, tanpa nomor revisi; BR-12)
- [ ] `Shared/GeneratePdfService` (atau pakai facade `Pdf::loadView` dari `barryvdh/laravel-dompdf`)

## 3. Filament Resource `TransSalesQuotationHdResource`

Group: **Penjualan**.

- [ ] Properti: `$navigationLabel = 'Quotation'`, `$slug = 'quotations'`, icon
- [ ] Form Section Informasi Utama + Relasi + PPN & Diskon + Catatan
  - [ ] Field `code` `disabled()->dehydrated(false)`; di-generate di `mutateFormDataBeforeCreate` via `GenerateCodeService::next(AutoCodePrefix::TransSalesQuotationHd)`
  - [ ] Field finansial (`subtotal`, `vat_amount`, `discount_total`, `grand_total`) dihitung otomatis dari items (reactive)
- [ ] Table: `code`, `transaction_date`, `client.name`, `grand_total`, `status.name` badge (via `QuotationStatus::from($state)->label()/color()`), audit
- [ ] Filter table: `status_id`, `client_id`, `company_id`, `transaction_date` range
- [ ] Infolist: Informasi Utama + Nominal + Audit Data
- [ ] Page: List, Create, View, Edit (Edit hanya saat status `draft`; BR-11)
- [ ] Export Excel via `pxlrbt/filament-excel`

### 3.1 Relation Manager

- [ ] `ItemsRelationManager` → `trans_sales_quotation_dt`; editable hanya saat status `draft`
- [ ] `TermsRelationManager` → `trans_sales_term` (filter `quotation_hd_id`); editable hanya saat status `draft`; validasi total `percentage` = 100 di submit

### 3.2 Custom Action

- [ ] `SendAction` — visible saat status `draft`; panggil `SendQuotationService`
- [ ] `ApproveAction` — visible saat status `sent`; `requiresConfirmation` + modal deskripsi (SO akan dibuat, data terkunci)
- [ ] `RejectAction` — visible saat status `sent`
- [ ] `ReviseAction` — visible saat status `sent`
- [ ] `PrintPdfAction` — visible saat status `sent` / `approved`; stream PDF

## 4. PDF Quotation

- [ ] `composer require barryvdh/laravel-dompdf`
- [ ] Blade template `resources/views/pdf/quotation.blade.php`
  - [ ] Header: logo + nama company penerbit + alamat + kontak
  - [ ] Info client, nomor quotation, tanggal, PIC
  - [ ] Tabel items (no, nama, qty, unit, harga, diskon, total)
  - [ ] Subtotal, VAT, diskon, grand total
  - [ ] Tabel termin (sequence, nama, persentase, threshold, nilai)
  - [ ] Footer: syarat & ketentuan, tanda tangan
  - [ ] Format Rupiah dan tanggal bahasa Indonesia
- [ ] Trigger via `PrintPdfAction`: `Pdf::loadView('pdf.quotation', ['quotation' => $record])->stream("quotation-{$record->code}.pdf")`

## 5. Notification

Notifikasi yang terkait langsung dengan transisi Quotation:

- [ ] (Opsional) `QuotationApprovedNotification` — ke Marketing/Owner saat Approved (log internal)

Notifikasi `TermReadyToBillNotification` (DP langsung ready-to-bill setelah SO dibuat) dilacak di [sales-order-term.md](sales-order-term.md).

## 6. Test

- [x] `TransactionSchemaTest::test_transaction_tables_and_relationships_support_the_sales_flow` — relasi Quotation ↔ Items ↔ Terms ↔ Order
- [x] `TransactionSchemaTest::test_transaction_sample_seeder_creates_an_idempotent_dp_flow` — seeder sampel Draft/Sent/Approved
- [ ] Feature test flow **Send**: validasi total termin 100%, Draft → Sent
- [ ] Feature test flow **Revisi**: Sent → Draft, histori status tercatat
- [ ] Feature test flow **Reject**: Sent → Rejected
- [ ] Unit test `ApproveQuotationService` memicu `CreateSalesOrderFromQuotationService` dalam satu transaction

> Catatan: `TransactionSchemaTest` yang ada hanya memverifikasi schema, relasi, dan seeder. Flow end-to-end (Send → Approve → SO terbentuk → termin bind) belum diuji.

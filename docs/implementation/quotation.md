# Implementation Checklist — Quotation

**Scope:** Entitas Quotation (`trans_sales_quotation_hd`, `trans_sales_quotation_dt`) termasuk form input, revisi, dan PDF. Pembentukan Sales Order saat Approved dilacak di [sales-order-term.md](sales-order-term.md).

**Pedoman UI:** Layout form, input uang, tampilan tabel, dan detail mengikuti [ui-conventions.md](ui-conventions.md).

**Acuan:** [bisnis-flow/quotation.md](../bisnis-flow/quotation.md), [filament-resource.md](../filament-resource.md), [filament-lifecycle-hook.md](../filament-lifecycle-hook.md), [database.md](../database.md) §4.13–4.14

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

- [x] `SendQuotationService` — Draft → Sent; validasi total `percentage` termin = 100 (BR-01), minimal 1 item, dan tepat satu termin DP threshold 0%
- [x] `RecalculateQuotationService` — satu service dengan dua method: `totals()` menghitung ulang subtotal/diskon/PPN/grand total lalu delegasi ke `termAmounts()`; `termAmounts()` simpan nilai `amount` termin proporsional (sisa pembulatan jatuh ke termin terakhir saat total persentase 100%)
- [x] `ApproveQuotationService` — Sent → Approved; dalam `DB::transaction` panggil `CreateSalesOrderFromQuotationService` (BR-17)
- [x] `RejectQuotationService` — Sent → Rejected
- [x] `ReviseQuotationService` — Sent → Draft (histori status tetap tercatat, tanpa nomor revisi; BR-12)
- [x] PDF memakai facade `Pdf::loadView` dari `barryvdh/laravel-dompdf`

## 3. Filament Resource `TransSalesQuotationHdResource`

Group: **Penjualan**.

- [x] Properti: `$navigationLabel = 'Quotation'`, `$slug = 'quotations'`, icon
- [x] Form Section Informasi Utama + Relasi + PPN & Diskon + Catatan
  - [x] Field `code` `disabled()->dehydrated(false)`; di-generate di `mutateFormDataBeforeCreate` via `GenerateCodeService::next(AutoCodePrefix::TransSalesQuotationHd)`
  - [x] Field finansial dihitung otomatis: `total_price` detail sudah setelah diskon; `discount_total = Σ discount`; `subtotal = Σ total_price`; `grand_total` menerapkan PPN sesuai `vat_type`. Tarif dibaca dari `SANITRA_VAT_RATE`.
  - [x] Setelah item dibuat, diedit, atau dihapus, ringkasan finansial di form header di-refresh dari nilai terbaru di database.
- [x] Table: `code`, `transaction_date`, `client.name`, `grand_total`, `status.name` badge; warna dari `QuotationStatus::from($record->status->code)->color()`, audit
- [x] Filter table: `status_id`, `client_id`, `company_id`, `transaction_date` range
- [x] Infolist: Informasi Utama + Nominal + Audit Data
- [x] Page: List, Create, View, Edit (Edit hanya saat status `draft`; BR-11)
- [x] Export Excel via `pxlrbt/filament-excel`

### 3.1 Relation Manager

- [x] `ItemsRelationManager` → `trans_sales_quotation_dt`; editable hanya saat status `draft`; perubahan item menghitung ulang total quotation dan nilai termin di database
- [x] `TermsRelationManager` → `trans_sales_term` (filter `quotation_hd_id`); editable hanya saat status `draft`; `amount` tersimpan dan dihitung ulang saat termin atau total quotation berubah; validasi total `percentage` = 100 sebelum Send

### 3.2 Custom Action

- [x] `SendAction` — visible saat status `draft`; panggil `SendQuotationService`
- [x] `ApproveAction` — visible saat status `sent`; `requiresConfirmation` + modal deskripsi (SO akan dibuat, data terkunci)
- [x] `RejectAction` — visible saat status `sent`
- [x] `ReviseAction` — visible saat status `sent`
- [x] Action `Generate PDF` tersedia saat status `draft` di halaman Edit dan View; PDF memakai nilai termin yang tersimpan

## 4. PDF Quotation

- [x] `barryvdh/laravel-dompdf` terpasang
- [x] Blade template `resources/views/pdf/quotation.blade.php`
  - [x] Header: logo + nama company penerbit + alamat + kontak
  - [x] Info client, nomor quotation, tanggal, PIC
  - [x] Tabel items (no, nama, qty, unit, harga, diskon, total)
  - [x] Subtotal, VAT, diskon, grand total
  - [x] Tabel termin (sequence, nama, persentase, threshold, nilai dari `trans_sales_term.amount`)
  - [x] Footer: syarat & ketentuan, tanda tangan
  - [x] Format Rupiah dan tanggal bahasa Indonesia
- [x] Generate PDF dari action di halaman Edit dan View saat quotation masih Draft; file diunduh dengan nama berdasarkan kode quotation

## 5. Notification

Notifikasi yang terkait langsung dengan transisi Quotation:

- [ ] (Opsional) `QuotationApprovedNotification` — ke Marketing/Owner saat Approved (log internal)

Notifikasi `TermReadyToBillNotification` (DP langsung ready-to-bill setelah SO dibuat) dilacak di [sales-order-term.md](sales-order-term.md).

## 6. Test

- [x] `TransactionSchemaTest::test_transaction_tables_and_relationships_support_the_sales_flow` — relasi Quotation ↔ Items ↔ Terms ↔ Order
- [x] `TransactionSchemaTest::test_transaction_sample_seeder_creates_an_idempotent_dp_flow` — seeder sampel Draft/Sent/Approved
- [ ] Feature test flow **Send**: total termin 100%, tepat satu DP threshold 0%, Draft → Sent; tanpa DP atau DP ganda ditolak
- [ ] Feature test flow **Revisi**: Sent → Draft, histori status tercatat
- [ ] Feature test flow **Reject**: Sent → Rejected
- [ ] Unit test `ApproveQuotationService` memicu `CreateSalesOrderFromQuotationService` dalam satu transaction

> Catatan: `TransactionSchemaTest` yang ada hanya memverifikasi schema, relasi, dan seeder. Flow end-to-end (Send → Approve → SO terbentuk → termin bind) belum diuji.

# Implementation Checklist — Invoice

**Scope:** Entitas Invoice (`trans_sales_invoice_hd`, `trans_sales_invoice_dt`) termasuk pembuatan dari termin Ready to Bill, Void, PDF, dan tampilan Finance. Pembayaran dilacak terpisah di [payment.md](payment.md).

**Acuan:** [bisnis-flow/finance.md](../bisnis-flow/finance.md) §2, [filament-resource.md](../filament-resource.md), [filament-lifecycle-hook.md](../filament-lifecycle-hook.md), [database.md](../database.md) §4.21–4.22

> **Reminder:** Tiap selesai item, update `docs/work/YYYY-MM-DD.md`.

## 1. Schema & Model

- [x] Migration `trans_sales_invoice_hd` (Ran) — FK `order_hd_id` wajib, `project_hd_id` nullable, `quotation_hd_id`, `bank_account_id` wajib
- [x] Migration `trans_sales_invoice_dt` (Ran) — FK `sales_term_id`
- [x] Model `TransSalesInvoiceHd` dengan relasi `order`, `project`, `quotation`, `client`, `company`, `bankAccount`, `status`, `items`, `payments`; cast `vat_type` (`VatType`), nominal `decimal:6`, tanggal `date`
- [x] Model `TransSalesInvoiceDt` dengan relasi `invoice`, `salesTerm`
- [x] Factory `TransSalesInvoiceHdFactory`, `TransSalesInvoiceDtFactory`
- [x] Trait `AuditedBySoftDelete` + `SoftDeletes`
- [x] Enum case `AutoCodePrefix::TransSalesInvoiceHd = 'INV'`
- [x] Enum `InvoiceStatus` (`unpaid`, `partial`, `paid`, `void`) + 4 row aktif di `ms_status` tipe `invoice`
- [ ] Accessor `isOverdue()` dan `remainingAmount()` di `TransSalesInvoiceHd`

## 2. Service

### 2.1 `app/Services/Invoice/CreateInvoiceFromTermsService`

Semua dalam satu `DB::transaction` (NFR-04).

- [ ] Guard semua termin yang dipilih milik `order_hd_id` yang sama
- [ ] Guard semua termin status `ready_to_bill`; lock baris dengan `lockForUpdate()`
- [ ] Guard termin bebas invoice aktif (cek `trans_sales_invoice_dt` yang join ke invoice status ≠ `void`)
- [ ] Buat HD invoice; generate `code` via `GenerateCodeService::next(AutoCodePrefix::TransSalesInvoiceHd)`; hitung `subtotal`, `vat_amount`, `grand_total` dari termin; `project_hd_id` boleh null (invoice DP)
- [ ] Buat baris DT per termin dengan snapshot `percentage`, `amount`
- [ ] Update status termin → `invoiced`
- [ ] Jika invoice memiliki `project_hd_id`: set `editable = false` pada `trans_project_dt` yang status `completed` (BR-05)
- [ ] (Opsional) tulis log ke `trans_project_monitoring` per tahap yang terkunci (BR-10)
- [ ] Setelah commit: kirim `InvoiceCreatedNotification` (opsional) + hitung ulang `payment_status` SO

### 2.2 `app/Services/Invoice/VoidInvoiceService`

- [ ] Guard: tidak ada payment Confirmed (jika ada, lempar `DomainException`)
- [ ] Update invoice `status = void`
- [ ] Kembalikan status termin yang terkait → `ready_to_bill` (BR-09)
- [ ] Untuk setiap tahap yang terkunci karena invoice ini, cek apakah masih dikunci invoice aktif lain; kalau tidak, set `editable = true`
- [ ] Append log ke `trans_project_monitoring` per tahap yang berubah (BR-10)

## 3. Filament Resource `TransSalesInvoiceHdResource`

Group: **Keuangan**.

- [ ] Properti: `$navigationLabel = 'Invoice'`, `$slug = 'invoices'`, icon currency
- [ ] Page: List, View, Edit terbatas (edit disabled kalau sudah ada payment Confirmed; BR-11)
- [ ] Table: `code`, `transaction_date`, `due_date` (highlight warna `danger` saat `isOverdue()`), `order.code`, `client.name`, `grand_total`, `status.name` badge (via `InvoiceStatus::from($state)->label()/color()`), audit
- [ ] Filter table: `status_id`, `due_date` range, `client_id`, `company_id`
- [ ] Infolist: Informasi Utama + Nominal + Termin yang ditagih + Audit
- [ ] Export Excel

### 3.1 Custom Page — Create Invoice dari Termin Ready to Bill

- [ ] Entry: `BuatInvoiceAction` di `TransSalesOrderHdResource` view page (lihat [sales-order-term.md](sales-order-term.md) §3.2)
- [ ] Page menampilkan daftar termin `ready_to_bill` untuk SO terpilih
- [ ] Checkbox pemilihan termin + preview `subtotal` / `vat` / `grand_total`
- [ ] Field: `transaction_date`, `due_date`, `bank_account_id`, `notes`
- [ ] Submit → panggil `CreateInvoiceFromTermsService`
- [ ] Redirect ke view page invoice yang baru dibuat

### 3.2 Relation Manager

- [ ] `ItemsRelationManager` → `trans_sales_invoice_dt` (read-only, snapshot termin)
- [ ] `PaymentsRelationManager` → `trans_sales_payment` (lihat [payment.md](payment.md); create + Confirm/Reject action)

### 3.3 Custom Action

- [ ] `VoidAction` — visible saat status `unpaid` / `partial` **dan** belum ada payment Confirmed; wajib isi alasan ke `notes`
- [ ] `PrintPdfAction` — selalu visible, stream PDF

## 4. PDF Invoice

- [ ] `composer require barryvdh/laravel-dompdf` (dipakai bersama dengan Quotation; sudah di-track di [quotation.md](quotation.md))
- [ ] Blade template `resources/views/pdf/invoice.blade.php`
  - [ ] Header: logo + nama company penerbit
  - [ ] Info client, nomor invoice, tanggal, jatuh tempo
  - [ ] Tabel termin yang ditagih (sequence, nama, persentase, nilai)
  - [ ] Subtotal, VAT, grand total
  - [ ] Rekening tujuan (`bank_name`, `account_number`, `account_name`)
  - [ ] Catatan + tanda tangan
  - [ ] Format Rupiah dan tanggal bahasa Indonesia
- [ ] Trigger via `PrintPdfAction`: `Pdf::loadView('pdf.invoice', ['invoice' => $record])->stream("invoice-{$record->code}.pdf")`

## 5. Notification

- [ ] (Opsional) `InvoiceCreatedNotification` — ke Finance sebagai log
- [ ] (Scheduler, opsional) `InvoiceOverdueReminder` — reminder H-3 jatuh tempo via Laravel Scheduler (shared hosting cron, bukan queue worker)

## 6. Test

- [x] `TransactionSchemaTest::test_transaction_tables_and_relationships_support_the_sales_flow` — relasi Invoice ↔ Items ↔ Payment ↔ Term
- [x] `TransactionSchemaTest::test_transaction_sample_seeder_creates_an_idempotent_dp_flow` — seeder sampel sudah menghasilkan 1 invoice DP (`project_hd_id` null, status `unpaid`)
- [ ] Feature test: pembuatan invoice dari termin Ready to Bill → termin jadi `invoiced` + tahap `completed` terkunci (`editable = false`)
- [ ] Feature test: void invoice → termin kembali `ready_to_bill`, tahap unlock bila tidak ada invoice aktif lain
- [ ] Feature test: edit invoice ditolak setelah ada payment Confirmed (BR-11)
- [ ] Unit test race condition: 2 user bikin invoice dari termin yang sama → request kedua ditolak oleh `lockForUpdate`

> Catatan: `TransactionSchemaTest` yang ada hanya memverifikasi schema, relasi, dan seeder. Flow invoice end-to-end (pembuatan oleh service, kunci tahap, void) belum diuji.

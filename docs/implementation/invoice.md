# Implementation Checklist — Invoice

**Scope:** Entitas Invoice (`trans_sales_invoice_hd`, `trans_sales_invoice_dt`) termasuk pembuatan dari termin Ready to Bill, Void, PDF, dan tampilan Finance. Pembayaran dilacak terpisah di [payment.md](payment.md).

**Acuan:** [bisnis-flow/invoice.md](../bisnis-flow/invoice.md), [filament-resource.md](../filament-resource.md), [filament-lifecycle-hook.md](../filament-lifecycle-hook.md), [database.md](../database.md) §4.21–4.22

> **Reminder:** Tiap selesai item, update `docs/work/YYYY-MM-DD.md`.

## 1. Schema & Model

- [x] Migration `trans_sales_invoice_hd` (Ran) — FK `order_hd_id` wajib, `project_hd_id` nullable, `quotation_hd_id`, `bank_account_id` wajib
- [x] Migration `trans_sales_invoice_dt` (Ran) — FK `sales_term_id`
- [x] Model `TransSalesInvoiceHd` dengan relasi `order`, `project`, `quotation`, `client`, `company`, `bankAccount`, `status`, `items`, `payments`; cast `vat_type` (`VatType`), nominal `decimal:6`, tanggal `date`
- [x] Model `TransSalesInvoiceDt` dengan relasi `invoice`, `salesTerm`
- [x] Factory `TransSalesInvoiceHdFactory`, `TransSalesInvoiceDtFactory`
- [x] Trait `AuditedBySoftDelete` + `SoftDeletes`
- [x] Enum case `AutoCodePrefix::TransSalesInvoiceHd = 'INV'`
- [x] Enum `InvoiceStatus` (`unpaid`, `paid`, `void`) + 3 row aktif di `ms_status` tipe `invoice` (status `partial` dihapus)
- [x] Accessor `isOverdue()` dan `remainingAmount()` di `TransSalesInvoiceHd`

## 2. Service

### 2.1 `app/Services/Invoice/CreateInvoiceFromTermsService`

Semua dalam satu `DB::transaction` (NFR-04).

- [x] Guard semua termin yang dipilih milik `order_hd_id` yang sama
- [x] Guard semua termin status `ready_to_bill`; lock baris dengan `lockForUpdate()`
- [x] Guard termin bebas invoice aktif (cek `trans_sales_invoice_dt` yang join ke invoice status ≠ `void`)
- [x] Buat HD invoice; generate `code` via `GenerateCodeService::next(AutoCodePrefix::TransSalesInvoiceHd)`; `grand_total = Σ amount` termin, `vat_amount` dialokasikan dari SO tanpa PPN ganda, `subtotal` mengikuti `vat_type`; selisih pembulatan dibebankan pada invoice terakhir; `project_hd_id` boleh null (invoice DP)
- [x] Buat baris DT per termin dengan snapshot `percentage`, `amount`
- [x] Update status termin → `invoiced`
- [x] Jika invoice memiliki `project_hd_id`: set `editable = false` pada `trans_project_dt` yang status `completed` (BR-05)
- [ ] (Opsional) tulis log ke `trans_project_monitoring` per tahap yang terkunci (BR-10)
- [ ] Setelah commit: kirim `InvoiceCreatedNotification` (opsional) + hitung ulang `payment_status` SO

### 2.2 `app/Services/Invoice/VoidInvoiceService`

- [x] Guard: invoice `unpaid` dan tidak ada payment Pending/Confirmed; payment Pending harus ditolak sebelum Void
- [x] Update invoice `status = void`
- [x] Kembalikan status termin yang terkait → `ready_to_bill` (BR-09)
- [ ] Untuk setiap tahap yang terkunci karena invoice ini, cek apakah masih dikunci invoice aktif lain; kalau tidak, set `editable = true`
- [ ] Append log ke `trans_project_monitoring` per tahap yang berubah (BR-10)

> Fase ini mengizinkan Void hanya untuk invoice DP (`project_hd_id` null). Void invoice progress ditahan sampai aturan unlock tahap Project dikerjakan.

## 3. Filament Resource `TransSalesInvoiceHdResource`

Group: **Keuangan**.

- [x] Properti: `$navigationLabel = 'Invoice'`, `$slug = 'invoices'`, icon currency
- [x] Page: List, View, Edit terbatas (edit disabled begitu ada payment, termasuk Pending/Rejected; BR-11); akses URL edit setelah ada payment redirect ke View dengan notifikasi warning (bukan 403 mentah)
- [x] Table: `code`, `transaction_date`, `due_date` (highlight warna `danger` saat `isOverdue()`), `order.code`, `client.name`, `grand_total`, `status.name` badge; warna dari `InvoiceStatus::from($record->status->code)->color()`, audit
- [x] Filter table: `status_id`, `due_date` range, `client_id`, `company_id`
- [x] Infolist: Informasi Utama + Nominal + Audit; termin yang ditagih tampil di `ItemsRelationManager`
- [x] Export Excel

### 3.1 Custom Page — Create Invoice dari Termin Ready to Bill

- [x] Entry: `BuatInvoiceAction` di `TransSalesOrderHdResource` view page (lihat [sales-order-term.md](sales-order-term.md) §3.2)
- [x] Page menampilkan daftar termin `ready_to_bill` untuk SO terpilih
- [x] Checkbox pemilihan termin + preview `subtotal` / `vat` / `grand_total`
- [x] Field: `transaction_date`, `due_date`, `bank_account_id`, `notes`
- [x] DatePicker `transaction_date` dan `due_date` menggunakan `native(false)`; `transaction_date` dibatasi `minDate` = `transaction_date` SO
- [x] Dropdown `bank_account_id` menampilkan format `Nama Rekening · Nama Bank`
- [x] Submit → panggil `CreateInvoiceFromTermsService`
- [x] Redirect ke view page invoice yang baru dibuat

### 3.2 Relation Manager

- [x] `ItemsRelationManager` → `trans_sales_invoice_dt` (read-only, snapshot termin)
- [x] `PaymentsRelationManager` → `trans_sales_payment` (lihat [payment.md](payment.md); create + Confirm/Reject action); hanya muncul di halaman View, tidak di Edit (`getRelationManagers()` di-override di `EditTransSalesInvoiceHd`)

### 3.3 Custom Action

- [x] `VoidAction` untuk invoice DP — visible saat status `unpaid` dan tidak ada payment Pending/Confirmed; wajib isi alasan ke `notes`
- [x] `PrintPdfAction` — selalu visible, stream PDF

## 4. PDF Invoice

- [x] `barryvdh/laravel-dompdf` sudah terpasang dan dipakai bersama Quotation
- [x] Blade template `resources/views/pdf/invoice.blade.php` — desain corporate navy (`#1e3a8a`), konsisten dengan PDF Quotation
  - [x] Header tiga kolom: logo kiri, legal name + alamat + kontak tengah, judul "INVOICE" kanan; garis navy pemisah di bawah
  - [x] Meta band 4 kolom: Nomor Invoice, Tanggal Diterbitkan, Jatuh Tempo, No. Referensi (kode SO)
  - [x] Blok client: nama + alamat
  - [x] Tabel termin: No / Deskripsi Termin (+ notes termin bila ada) / Persentase / Nilai; header solid navy, row even light blue
  - [x] Summary: Subtotal + PPN (hanya jika vat_type ≠ none) + Total Tagihan solid navy
  - [x] Kotak Informasi Pembayaran: nama bank, nomor rekening, atas nama, keterangan transfer
  - [x] Notes invoice (jika ada), rata kanan-kiri
  - [x] Area tanda tangan dua kolom (klien kiri, Sanitra kanan) dengan kotak dan baris stempel
  - [x] Format Rupiah dan tanggal bahasa Indonesia
- [x] Trigger via action `Download PDF` pada halaman View Invoice; data disiapkan `InvoicePdfDataService`, lalu `Pdf::loadView('pdf.invoice', ...)->setPaper('a4', 'portrait')` dikirim sebagai file unduhan

## 5. Notification

- [ ] (Opsional) `InvoiceCreatedNotification` — ke Finance sebagai log
- [ ] (Scheduler, opsional) `InvoiceOverdueReminder` — reminder H-3 jatuh tempo via Laravel Scheduler (shared hosting cron, bukan queue worker)

## 6. Test

- [x] `TransactionSchemaTest::test_transaction_tables_and_relationships_support_the_sales_flow` — relasi Invoice ↔ Items ↔ Payment ↔ Term
- [x] `TransactionSchemaTest::test_transaction_sample_seeder_creates_an_idempotent_dp_flow` — seeder sampel sudah menghasilkan 1 invoice DP (`project_hd_id` null, status `unpaid`)
- [ ] Feature test: pembuatan invoice dari termin Ready to Bill → termin jadi `invoiced` + tahap `completed` terkunci (`editable = false`)
- [ ] Feature test: void invoice → termin kembali `ready_to_bill`, tahap unlock bila tidak ada invoice aktif lain
- [ ] Feature test: edit invoice ditolak setelah ada payment, termasuk Pending (BR-11)
- [ ] Unit test race condition: 2 user bikin invoice dari termin yang sama → request kedua ditolak oleh `lockForUpdate`

> Catatan: `TransactionSchemaTest` yang ada hanya memverifikasi schema, relasi, dan seeder. Flow invoice end-to-end (pembuatan oleh service, kunci tahap, void) belum diuji.

Resource Invoice menyediakan List/View, edit data administratif sebelum ada payment, Void invoice DP, item dan payment relation manager, filter, export, serta unduh PDF. Notifikasi dan Void invoice progress yang menyentuh tahap Project masih ditunda.

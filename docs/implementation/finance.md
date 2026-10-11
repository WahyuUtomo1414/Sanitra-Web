# Implementation Checklist — Finance

**Scope:** Full pekerjaan untuk Invoice + Payment — migration, model, service, Filament resource, relation manager, custom action, PDF, notification, dashboard.

**Acuan:** [bisnis-flow/finance.md](../bisnis-flow/finance.md), [filament-resource.md](../filament-resource.md), [filament-lifecycle-hook.md](../filament-lifecycle-hook.md)

**Status audit 11 Oktober 2026:** Schema dan model Invoice serta Payment sudah tersedia; migration terkait sudah `Ran`. Phase Marketing belum selesai, sehingga service, UI transaksi, PDF, dashboard Finance, dan notifikasi masih menunggu implementasi. Test yang ada baru menguji schema/relasi dan seeder contoh, belum flow Finance.

> **Reminder:** Tiap selesai item, update `docs/work/YYYY-MM-DD.md` (ganti tanggal tiap hari, buat file baru per hari).

## Prasyarat

- [ ] Phase 1 Marketing selesai (SO + termin sudah ada)
- [x] Phase 2 Project schema selesai (`trans_project_hd` ada supaya FK invoice valid)

## 1. Migration

- [x] `trans_sales_invoice_hd` (FK `order_hd_id` wajib, `project_hd_id` nullable, index `due_date`)
- [x] `trans_sales_invoice_dt` (FK `sales_term_id`)
- [x] `trans_sales_payment` (FK `invoice_hd_id`)
- [x] Jalankan `php artisan migrate` (terverifikasi `Ran` di MySQL lokal)

## 2. Model Eloquent

- [x] `TransSalesInvoiceHd` + relasi `order`, `project`, `quotation`, `client`, `company`, `bankAccount`, `status`, `items`, `payments`
- [x] `TransSalesInvoiceDt` + relasi `invoice`, `salesTerm`
- [x] `TransSalesPayment` + relasi `invoice`, `status`
- [x] Cast `vat_type` (VatType), nominal `decimal:6`, tanggal `date`
- [x] Cast `payment_method` → `PaymentMethod::class`
- [ ] Accessor opsional: `remainingAmount()`, `isOverdue()` di invoice

## 3. Service (`app/Services/`)

- [ ] `Invoice/CreateInvoiceFromTermsService`
  - [ ] Guard semua termin milik SO yang sama
  - [ ] Guard termin status `ready_to_bill` dan belum masuk invoice aktif (`lockForUpdate`)
  - [ ] Buat invoice HD + DT, update termin → `invoiced`
  - [ ] Kunci tahap Completed (`editable = false`) di project terkait
  - [ ] Semua dalam satu `DB::transaction` (NFR-04)
- [ ] `Invoice/VoidInvoiceService`
  - [ ] Guard: tidak ada payment Confirmed
  - [ ] Invoice → `void`, termin kembali `ready_to_bill`
  - [ ] Unlock tahap jika tidak ada invoice aktif lain
  - [ ] Append log monitoring (BR-10)
- [ ] `Payment/RecordPaymentService` — simpan payment default `pending`
- [ ] `Payment/ConfirmPaymentService`
  - [ ] Payment → `confirmed`
  - [ ] Hitung ulang status invoice (unpaid/partial/paid)
  - [ ] Hitung ulang `payment_status` SO (unpaid/partial/paid)
  - [ ] Jika invoice ini mengandung termin DP dan sekarang Paid → trigger `CreateProjectFromSalesOrderService` (stub kalau Project resource belum ada)
  - [ ] Jika progress 100% + semua termin Paid → SO status `closed`
- [ ] `Payment/RejectPaymentService` — payment → `rejected`, tidak mengubah invoice/SO

## 4. Filament Resource

### 4.1 TransSalesInvoiceHdResource (Group: Keuangan)

- [ ] Properti resource: `$navigationLabel = 'Invoice'`, `$slug = 'invoices'`, icon currency
- [ ] Form (tanpa CreateAction standar — pakai custom page §5)
- [ ] Table: `code`, `transaction_date`, `due_date` (highlight overdue), `order.code`, `client.name`, `grand_total`, `status.name` (badge), audit
- [ ] Infolist: Informasi Utama + Nominal + Termin yang ditagih + Audit
- [ ] Page: List, View, Edit (edit disabled kalau sudah ada payment Confirmed)
- [ ] Filter table: `status_id`, `due_date` range, `client_id`, `company_id`
- [ ] Export Excel

### 4.2 Custom Page: Create Invoice dari Termin Ready to Bill

- [ ] Trigger action "Buat Invoice" di `TransSalesOrderHdResource` view page
- [ ] Page memuat daftar termin Ready to Bill untuk SO terpilih
- [ ] Checkbox pemilihan termin + preview total
- [ ] Submit → panggil `CreateInvoiceFromTermsService`
- [ ] Redirect ke invoice yang baru dibuat

### 4.3 Relation Manager di Invoice

- [ ] `ItemsRelationManager` → `trans_sales_invoice_dt`. Read-only (snapshot termin).
- [ ] `PaymentsRelationManager` → `trans_sales_payment`. Create payment dari sini + Confirm/Reject action.

### 4.4 Custom Action di Invoice

- [ ] `VoidAction` — visible saat status `unpaid`/`partial` dan belum ada payment Confirmed
- [ ] `PrintPdfAction` — selalu visible, stream PDF

### 4.5 TransSalesPaymentResource (Group: Keuangan)

- [ ] Properti resource: `$navigationLabel = 'Pembayaran'`, `$slug = 'payments'`
- [ ] Form: `invoice_hd_id` (searchable), `transaction_date`, `amount`, `payment_method`, `reference_no` (muncul kalau method butuh), `payment_proof` (upload disk public)
- [ ] Table: `code`, `transaction_date`, `invoice.code`, `amount`, `payment_method`, `status.name`, `reference_no`
- [ ] Infolist menampilkan bukti transfer (preview gambar/PDF)
- [ ] Filter: `status_id`, `payment_method`, tanggal

### 4.6 Custom Action di Payment

- [ ] `ConfirmAction` — visible saat `pending`, requiresConfirmation
- [ ] `RejectAction` — visible saat `pending`, modal wajib isi alasan ke `notes`

## 5. PDF Invoice

- [ ] Blade template `resources/views/pdf/invoice.blade.php`:
  - [ ] Header: logo + nama company penerbit
  - [ ] Info client, nomor invoice, tanggal, jatuh tempo
  - [ ] Tabel termin yang ditagih (sequence, nama, persentase, nilai)
  - [ ] Subtotal, VAT, grand total
  - [ ] Rekening tujuan (bank name, account number, account name)
  - [ ] Catatan + tanda tangan
- [ ] Trigger via `PrintPdfAction`: `Pdf::loadView('pdf.invoice', ['invoice' => $record])->stream(...)`

## 6. Dashboard Finance (widget)

- [ ] `FinanceStatsWidget`:
  - [ ] Termin Ready to Bill (count)
  - [ ] Invoice Outstanding (count)
  - [ ] Total Outstanding (Rp)
  - [ ] Overdue (count, warna danger)
  - [ ] Revenue bulan ini (Σ payment confirmed)
- [ ] `PaymentChartWidget` — bar chart 12 bulan terakhir
- [ ] `InvoiceStatusChartWidget` — doughnut distribusi status
- [ ] Widget hanya visible untuk role Owner & Finance

## 7. Notification

- [ ] `FirstTermPaidNotification` — ke Teknis + PIC saat invoice DP Paid (BR-15); dikirim sekali
- [ ] `InvoiceCreatedNotification` (opsional) — ke Finance sebagai log
- [ ] (Scheduler, opsional) `InvoiceOverdueReminder` — reminder H-3 jatuh tempo (via Laravel Scheduler + cron, bukan queue worker)

## 8. Test (opsional tapi direkomendasikan)

- [ ] Feature test: create invoice dari termin Ready to Bill → termin jadi Invoiced + tahap terkunci
- [ ] Feature test: payment Confirmed memicu invoice Paid, termin Paid, SO payment_status Partial/Paid
- [ ] Feature test: payment Confirmed di invoice DP memicu pembuatan project
- [ ] Feature test: void invoice mengembalikan termin dan membuka kunci tahap
- [ ] Unit test race condition: 2 user bikin invoice dari termin yang sama → request kedua reject

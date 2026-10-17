# Implementation Checklist — Payment

**Scope:** Entitas Payment (`trans_sales_payment`) — pencatatan pembayaran penuh, konfirmasi/penolakan, dan pembaruan status invoice/SO. DP Paid membuka akses pembuatan Project manual. Dashboard Finance ditunda ke fase berikutnya.

**Acuan:** [bisnis-flow/payment.md](../bisnis-flow/payment.md), [filament-resource.md](../filament-resource.md), [filament-lifecycle-hook.md](../filament-lifecycle-hook.md), [database.md](../database.md) §4.23

> **Reminder:** Tiap selesai item, update `docs/work/YYYY-MM-DD.md`.

## 1. Schema & Model

- [x] Migration `trans_sales_payment` (Ran) — FK `invoice_hd_id`, `payment_method` string, `payment_proof` nullable, `reference_no` nullable
- [x] Model `TransSalesPayment` dengan relasi `invoice`, `status`; cast `payment_method` (`PaymentMethod`), `amount` `decimal:6`, `transaction_date` `date`
- [x] Factory `TransSalesPaymentFactory`
- [x] Trait `AuditedBySoftDelete` + `SoftDeletes`
- [x] Enum case `AutoCodePrefix::TransSalesPayment = 'PAY'`
- [x] Enum `PaymentMethod` (`cash`, `transfer`, `giro`) + helper `label()`
- [x] Enum `PaymentStatus` (`pending`, `confirmed`, `rejected`) + 3 row aktif di `ms_status` tipe `payment`

## 2. Service

### 2.1 `app/Services/Payment/RecordPaymentService`

- [x] Guard invoice status = `unpaid`
- [x] Guard `amount = grand_total` invoice dan tidak ada payment Pending/Confirmed lain untuk invoice yang sama
- [x] Generate `code` via `GenerateCodeService::next(AutoCodePrefix::TransSalesPayment)`
- [x] Simpan payment default status `pending`
- [x] `FileUpload` menyimpan bukti ke disk `public` di folder `payments/proof`; service memvalidasi path file sebelum menyimpan payment

### 2.2 `app/Services/Payment/ConfirmPaymentService`

Semua dalam satu `DB::transaction` (NFR-04).

- [x] Guard payment status = `pending`, invoice masih `unpaid`, dan belum ada payment Confirmed lain
- [x] Update payment status → `confirmed`
- [x] Validasi ulang `amount = grand_total`; invoice → `paid` setelah payment penuh Confirmed
- [x] Jika invoice `paid`: semua termin yang terkait → `paid`
- [x] Hitung ulang `payment_status` SO via `RecalculateOrderPaymentStatusService` (lihat [sales-order-term.md](sales-order-term.md) §2.3)
- [x] Jika invoice DP Paid, tombol Buat Project pada SO menjadi tersedia; konfirmasi payment tidak membuat Project atau mengubah SO ke `on_progress`
- [ ] Trigger penutupan SO (BR-13): jika SO sudah ada Project, `progress_percentage = 100`, dan semua termin `paid` → SO `closed` + Project `completed`
- [ ] Setelah commit: SO dengan DP Paid dapat dipilih untuk pembuatan Project manual; email ke Teknis + PIC dikirim setelah Project dibuat dan PIC tersedia

> Penutupan SO/Project pada progress 100% serta notifikasi sesudah Project dibuat berada di luar batas pekerjaan ini. Status `on_progress` baru dipasang oleh alur pembuatan Project, bukan oleh payment.

### 2.3 `app/Services/Payment/RejectPaymentService`

- [x] Guard payment status = `pending`
- [x] Update payment status → `rejected`
- [x] Tidak mengubah invoice/SO (payment Rejected tidak dihitung)

## 3. Filament Resource `TransSalesPaymentResource`

Group: **Keuangan**.

- [x] Properti: `$navigationLabel = 'Pembayaran'`, `$slug = 'payments'`
- [x] Form (`TransSalesPaymentForm`): `invoice_hd_id` (searchable, live); `transaction_date` DatePicker `native(false)` dengan `minDate` = `transaction_date` SO (via `Get` pada form standalone, via parameter `?string $minDate` saat dipanggil dari `PaymentsRelationManager`); `amount` read-only + format Rupiah (`Rp 12.210.000`) — diformat di `afterStateHydrated` dan `afterStateUpdated` invoice select, di-strip titik via `dehydrateStateUsing` sebelum dikirim ke service; `payment_method` (select enum `PaymentMethod`); `reference_no` (muncul kalau `payment_method` butuh); `payment_proof` (upload disk `public`); `notes`
- [x] Field `code` `disabled()->dehydrated(false)`; nomor dibuat oleh `RecordPaymentService` melalui `handleRecordCreation`
- [x] Table: `code`, `transaction_date`, `invoice.code`, `amount`, `payment_method`, `status.name` badge; warna dari `PaymentStatus::from($record->status->code)->color()`, `reference_no`, audit
- [x] Filter: `status_id`, `payment_method`, tanggal
- [x] Infolist menampilkan gambar bukti atau tautan pembuka PDF + Audit Data
- [x] Export Excel

### 3.1 Custom Action

- [x] `ConfirmAction` — visible saat `pending`; `requiresConfirmation`; panggil `ConfirmPaymentService`
- [x] `RejectAction` — visible saat `pending`; modal wajib isi alasan ke `notes`

### 3.2 Relation Manager di Invoice

Pembuatan payment lebih natural dari halaman invoice. Lacak di [invoice.md](invoice.md) §3.2 — `PaymentsRelationManager` memakai service pada bagian ini.

Policy Shield untuk resource Payment sudah dibuat. Permission dan role database belum diubah; akses Finance memerlukan permission resource terkait.

## 4. Dashboard Finance (FR-41 — fase berikutnya)

Bagian ini bukan syarat penyelesaian flow Payment saat ini. Saat dikerjakan, filter kode status harus lewat relasi `status` atau FK `status_id`.

- [ ] `FinanceStatsWidget` berisi:
  - [ ] Termin Ready to Bill (`TransSalesTerm::whereRelation('status', 'code', 'ready_to_bill')->count()`)
  - [ ] Invoice Outstanding (`TransSalesInvoiceHd::whereRelation('status', 'code', 'unpaid')->count()`)
  - [ ] Total Outstanding (`Σ grand_total` dari invoice Unpaid)
  - [ ] Overdue (invoice Unpaid dengan `due_date < today()`), warna `danger`
  - [ ] Revenue bulan ini (`Σ amount payment confirmed` di bulan berjalan)
- [ ] `PaymentChartWidget` — bar chart 12 bulan terakhir
- [ ] `InvoiceStatusChartWidget` — doughnut distribusi status invoice
- [ ] Widget visible hanya untuk role **Owner** dan **Finance**

## 5. Notification

- [ ] Notifikasi ke Teknis + PIC setelah Project manual tersimpan (BR-15); dikirim satu kali dari alur pembuatan Project
- [ ] (Opsional) `PaymentRejectedNotification` — ke Finance sebagai log penolakan

## 6. Test

- [x] `TransactionSchemaTest::test_transaction_tables_and_relationships_support_the_sales_flow` — Invoice ↔ Payment
- [x] `TransactionSchemaTest::test_transaction_sample_seeder_creates_an_idempotent_dp_flow` — payment sampel default `pending`
- [ ] Feature test: payment penuh Confirmed memicu invoice `paid`, termin `paid`, SO `payment_status` → `partial` / `paid` sesuai jumlah termin yang sudah lunas
- [ ] Feature test: payment dengan nominal tidak penuh atau duplikat Pending/Confirmed ditolak
- [ ] Feature test: payment Confirmed pada invoice DP membuat SO layak dipilih di form Create Project, tetapi belum membuat Project atau mengubah SO ke `on_progress`
- [ ] Feature test: form Create Project menolak SO tanpa DP Paid atau yang sudah punya Project; setelah tersimpan SO menjadi `on_progress` dan tahap terbentuk
- [ ] Feature test: payment Rejected tidak mengubah status invoice/SO
- [ ] Feature test: SO `Closed` saat progress 100% + semua termin `paid` (BR-13)
- [ ] Unit test `RecordPaymentService` menolak invoice `void` / `paid`

> Catatan: `TransactionSchemaTest` yang ada hanya memverifikasi schema, relasi, dan seeder. Flow Confirm/Reject payment dan dampak kaskade ke SO/Project belum diuji.

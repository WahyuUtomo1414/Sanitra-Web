# Implementation Checklist — Payment

**Scope:** Entitas Payment (`trans_sales_payment`) — pencatatan, konfirmasi/penolakan, dampak otomatis ke invoice/SO/Project, serta dashboard Finance.

**Acuan:** [bisnis-flow/finance.md](../bisnis-flow/finance.md) §3–4, [filament-resource.md](../filament-resource.md), [filament-lifecycle-hook.md](../filament-lifecycle-hook.md), [database.md](../database.md) §4.23

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

- [ ] Guard invoice status ∈ {`unpaid`, `partial`}
- [ ] Guard `amount > 0` dan `amount ≤ sisa_tagihan` (aturan kelebihan bayar menunggu konfirmasi)
- [ ] Generate `code` via `GenerateCodeService::next(AutoCodePrefix::TransSalesPayment)`
- [ ] Simpan payment default status `pending`
- [ ] Simpan file bukti ke disk `public` di folder `payments/proof`

### 2.2 `app/Services/Payment/ConfirmPaymentService`

Semua dalam satu `DB::transaction` (NFR-04).

- [ ] Guard payment status = `pending`
- [ ] Update payment status → `confirmed`
- [ ] Hitung ulang invoice: `total_confirmed = Σ amount payment confirmed`; invoice → `paid` kalau `total_confirmed ≥ grand_total`, `partial` kalau 0 < confirmed < grand_total
- [ ] Jika invoice `paid`: semua termin yang terkait → `paid`
- [ ] Hitung ulang `payment_status` SO via `RecalculateOrderPaymentStatusService` (lihat [sales-order-term.md](sales-order-term.md) §2.3)
- [ ] Trigger `CreateProjectFromSalesOrderService` jika SO status = `waiting_dp` dan invoice ini mengandung termin DP yang sekarang Paid (BR-15); SO status → `on_progress`
- [ ] Trigger penutupan SO (BR-13): jika SO sudah ada Project, `progress_percentage = 100`, dan semua termin `paid` → SO `closed` + Project `completed`
- [ ] Setelah commit: kirim `FirstTermPaidNotification` ke Teknis + PIC (satu kali per project, hanya saat Project baru terbentuk)

### 2.3 `app/Services/Payment/RejectPaymentService`

- [ ] Guard payment status = `pending`
- [ ] Update payment status → `rejected`
- [ ] Tidak mengubah invoice/SO (payment Rejected tidak dihitung)

## 3. Filament Resource `TransSalesPaymentResource`

Group: **Keuangan**.

- [ ] Properti: `$navigationLabel = 'Pembayaran'`, `$slug = 'payments'`
- [ ] Form: `invoice_hd_id` (searchable), `transaction_date`, `amount`, `payment_method` (select enum `PaymentMethod`), `reference_no` (muncul kalau `payment_method` butuh), `payment_proof` (upload disk `public`), `notes`
- [ ] Field `code` `disabled()->dehydrated(false)`; di-generate di `mutateFormDataBeforeCreate`
- [ ] Table: `code`, `transaction_date`, `invoice.code`, `amount`, `payment_method`, `status.name` badge (via `PaymentStatus::from($state)->label()/color()`), `reference_no`, audit
- [ ] Filter: `status_id`, `payment_method`, tanggal
- [ ] Infolist menampilkan bukti transfer (preview gambar/PDF) + Audit Data
- [ ] Export Excel

### 3.1 Custom Action

- [ ] `ConfirmAction` — visible saat `pending`; `requiresConfirmation`; panggil `ConfirmPaymentService`
- [ ] `RejectAction` — visible saat `pending`; modal wajib isi alasan ke `notes`

### 3.2 Relation Manager di Invoice

Pembuatan payment lebih natural dari halaman invoice. Lacak di [invoice.md](invoice.md) §3.2 — `PaymentsRelationManager` memakai service pada bagian ini.

## 4. Dashboard Finance (FR-41)

- [ ] `FinanceStatsWidget` berisi:
  - [ ] Termin Ready to Bill (`TransSalesTerm::where('status', 'ready_to_bill')->count()`)
  - [ ] Invoice Outstanding (`TransSalesInvoiceHd::whereIn('status', ['unpaid', 'partial'])->count()`)
  - [ ] Total Outstanding (`Σ (grand_total − Σ payment confirmed)` dari invoice outstanding)
  - [ ] Overdue (invoice outstanding dengan `due_date < now()`), warna `danger`
  - [ ] Revenue bulan ini (`Σ amount payment confirmed` di bulan berjalan)
- [ ] `PaymentChartWidget` — bar chart 12 bulan terakhir
- [ ] `InvoiceStatusChartWidget` — doughnut distribusi status invoice
- [ ] Widget visible hanya untuk role **Owner** dan **Finance**

## 5. Notification

- [ ] `FirstTermPaidNotification` — ke Teknis + PIC saat invoice DP Paid dan Project baru dibentuk (BR-15); dikirim satu kali
- [ ] (Opsional) `PaymentRejectedNotification` — ke Finance sebagai log penolakan

## 6. Test

- [x] `TransactionSchemaTest::test_transaction_tables_and_relationships_support_the_sales_flow` — Invoice ↔ Payment
- [x] `TransactionSchemaTest::test_transaction_sample_seeder_creates_an_idempotent_dp_flow` — payment sampel default `pending`
- [ ] Feature test: payment Confirmed memicu invoice `paid`, termin `paid`, SO `payment_status` → `partial` / `paid`
- [ ] Feature test: payment Confirmed pada invoice DP memicu `CreateProjectFromSalesOrderService` (stub kalau project resource belum ada)
- [ ] Feature test: payment Rejected tidak mengubah status invoice/SO
- [ ] Feature test: SO `Closed` saat progress 100% + semua termin `paid` (BR-13)
- [ ] Unit test `RecordPaymentService` menolak invoice `void` / `paid`

> Catatan: `TransactionSchemaTest` yang ada hanya memverifikasi schema, relasi, dan seeder. Flow Confirm/Reject payment dan dampak kaskade ke SO/Project belum diuji.

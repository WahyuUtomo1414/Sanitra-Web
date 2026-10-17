# Implementation Checklist — Sales Order & Term

**Scope:** Entitas Sales Order (`trans_sales_order_hd`, `trans_sales_order_dt`) dan Term (`trans_sales_term`), termasuk pembentukan SO otomatis saat Quotation Approved (BR-17) dan transisi status termin (BR-04).

**Acuan:** [bisnis-flow/sales-order-term.md](../bisnis-flow/sales-order-term.md), [filament-resource.md](../filament-resource.md), [filament-lifecycle-hook.md](../filament-lifecycle-hook.md), [database.md](../database.md) §4.15–4.17

> **Reminder:** Tiap selesai item, update `docs/work/YYYY-MM-DD.md`.

## 1. Schema & Model

- [x] Migration `trans_sales_order_hd` (Ran) dengan unique `quotation_hd_id`; kolom `close_reason` + `close_notes` ditambah via migration terpisah; `payment_status_id` sudah dihapus
- [x] Migration `trans_sales_order_dt` (Ran) dengan FK `quotation_dt_id` sebagai pointer ke sumber salinan
- [x] Migration `trans_sales_term` (Ran) — FK `quotation_hd_id` wajib, `order_hd_id` nullable; unique (`quotation_hd_id`, `sequence`)
- [x] Model `TransSalesOrderHd` dengan relasi `quotation`, `items`, `terms`, `project`, `invoices`, `status`, `client`, `projectType`, `company`; cast `vat_type`, `close_reason` (`OrderCloseReason`), nominal `decimal:6`; method `paymentSummary(): string`
- [x] Model `TransSalesOrderDt` dengan relasi `order`, `quotationDetail`, `item`, `unit`
- [x] Model `TransSalesTerm` dengan relasi `quotation`, `order`, `status`, `invoiceDetails`; cast `amount` `decimal:6`
- [x] Factory `TransSalesOrderHdFactory`, `TransSalesOrderDtFactory`, `TransSalesTermFactory`
- [x] Trait `AuditedBySoftDelete` + `SoftDeletes` pada ketiga model
- [x] Enum case `AutoCodePrefix::TransSalesOrderHd = 'SO'`
- [x] Enum `StatusType::Order` (tipe `order_payment` dihapus)
- [x] Enum `OrderStatus` (`draft`, `open`, `closed`) + 3 row aktif di `ms_status` tipe `order`
- [x] Enum `OrderCloseReason` (`completed`, `manual`) — bukan tipe ms_status, disimpan di kolom `close_reason`
- [x] Enum `TermStatus` (`draft`, `pending`, `ready_to_bill`, `invoiced`, `paid`) + 5 row aktif di `ms_status` tipe `term`

## 2. Service

### 2.1 `app/Services/SalesOrder/CreateSalesOrderFromQuotationService`

Dipanggil dari `ApproveQuotationService` dalam satu `DB::transaction` (BR-17).

- [x] Guard: quotation status = `sent`; FK unique → lempar `DomainException` kalau SO sudah ada
- [x] Copy field finansial dari HD quotation → HD SO; generate `code` via `GenerateCodeService::next(AutoCodePrefix::TransSalesOrderHd)`
- [x] Copy semua `trans_sales_quotation_dt` → `trans_sales_order_dt` (dengan `quotation_dt_id` pointer)
- [x] Update `trans_sales_term`: set `order_hd_id` dan pertahankan `amount` yang sudah dihitung serta disimpan selama quotation; termin `progress_threshold = 0` → `ready_to_bill`, selain itu → `pending`
- [x] Set `status_id` SO = `draft`; Project belum dibuat sampai SO berstatus `open`

### 2.2 `app/Services/SalesOrder/CheckTermReadyToBillService`

Dipanggil saat progress project berubah (BR-04). Fase project belum diimplementasikan; service tetap didesain supaya siap dipanggil.

- [x] Loop termin SO status `pending` → kalau `progress_percentage ≥ progress_threshold` dan termin belum berada di invoice aktif, update ke `ready_to_bill`
- [ ] Trigger `TermReadyToBillNotification` ke Finance (di luar transaction)

### 2.3 ~~`app/Services/SalesOrder/RecalculateOrderPaymentStatusService`~~ (DIHAPUS)

Service ini sudah dihapus. Status pembayaran SO tidak lagi disimpan sebagai kolom; dihitung via `paymentSummary()` pada model. `ConfirmPaymentService` sekarang menangani transisi SO `draft` → `open` secara langsung setelah termin DP lunas.

### 2.4 `app/Services/SalesOrder/CloseOrderService`

- [x] Guard: SO status `draft` atau `open`
- [x] Guard: tidak ada invoice dengan status `unpaid`
- [x] Jika SO punya project berstatus `on_progress` → project diubah ke `cancelled`
- [x] Set SO `status_id = closed`, `close_reason`, `close_notes`
- [x] Dipanggil dari action **Tutup SO** di `ViewTransSalesOrderHd`

## 3. Filament Resource `TransSalesOrderHdResource`

Group: **Penjualan**. Read-mostly — SO dibuat otomatis, tidak ada `CreateAction`.

- [x] Properti: `$navigationLabel = 'Sales Order'`, `$slug = 'sales-orders'`, icon
- [x] Page: List, View, Edit terbatas (hanya `notes`)
- [x] Table: `code`, `transaction_date`, `quotation.code`, `client.name`, `grand_total`, `status.name` badge (label bergantung `close_reason` saat closed), kolom `Status Bayar` computed via `paymentSummary()`, audit
- [x] Filter table: `status_id`, `client_id`, `company_id` (filter `payment_status_id` dihapus)
- [x] Infolist: Informasi Utama + Nominal + `close_reason`/`close_notes` (visible saat closed) + Audit
- [x] Export Excel

### 3.1 Relation Manager

- [x] `ItemsRelationManager` → `trans_sales_order_dt` (read-only); tombol **Detail** membuka modal infolist (item, unit, qty, harga, diskon, total, catatan)
- [x] `TermsRelationManager` → `trans_sales_term` filter `order_hd_id`; status badge; edit hanya `notes`; tombol **Detail** membuka modal infolist (nama termin, urutan, persentase, threshold, nilai, status, catatan); kolom catatan dihapus dari tabel
- [x] `InvoicesRelationManager` → `trans_sales_invoice_hd` (read-only; link ke resource invoice)

### 3.2 Custom Action di Sales Order

- [x] `BuatInvoiceAction` → arahkan ke custom page pembuatan invoice (lihat [invoice.md](invoice.md) §3.1); visible saat ada termin `ready_to_bill`
- [x] `BuatProjectAction` → arahkan ke form Create Project; visible hanya jika SO berstatus `open` dan belum punya Project; SO tidak berubah status saat Project dibuat (tetap `open`)
- [x] `TutupSOAction` → modal select alasan (`completed`/`manual`) + textarea catatan; visible saat SO `draft`/`open` dan user punya izin update; panggil `CloseOrderService`

> SO menjadi `open` otomatis setelah DP Paid (via `ConfirmPaymentService`). Project berstatus `on_progress` langsung setelah dibuat. SO status tidak berubah saat Project dibuat.

Policy Shield untuk Sales Order dan Invoice sudah dibuat. Akses menu/aksi memerlukan permission resource terkait pada role pengguna; permission dan role belum diubah pada database.

## 4. Notification

- [ ] `TermReadyToBillNotification` — ke Finance tiap kali termin berpindah ke `ready_to_bill` (termasuk termin DP yang otomatis Ready to Bill saat SO dibuat)
- [ ] (Opsional) `SalesOrderCreatedNotification` — ke Marketing sebagai konfirmasi

## 5. Test

- [x] `TransactionSchemaTest::test_transaction_tables_and_relationships_support_the_sales_flow` — relasi SO ↔ Items ↔ Terms ↔ Project ↔ Invoice
- [x] `TransactionSchemaTest::test_transaction_sample_seeder_creates_an_idempotent_dp_flow` — seeder sampel sudah menghasilkan 1 SO + termin terikat `order_hd_id` dengan status `invoiced`
- [ ] Feature test **Approve → SO otomatis**: item disalin, termin bind ke `order_hd_id`, `amount` terhitung (BR-02), status termin set, SO status = `draft`
- [ ] Feature test **Quotation tanpa DP ditolak saat Send**; SO selalu mempunyai satu termin DP threshold 0%
- [ ] Feature test **DP Paid → SO otomatis open**: `ConfirmPaymentService` mengubah SO `draft` → `open`
- [ ] Feature test **CloseOrderService**: ditolak jika ada invoice unpaid; project `on_progress` ikut `cancelled`
- [ ] Unit test guard `CreateSalesOrderFromQuotationService` menolak duplikat SO (FK unique)
- [ ] Unit test `CheckTermReadyToBillService` menolak termin yang sedang ada di invoice aktif

> Catatan: `TransactionSchemaTest` yang ada hanya memverifikasi schema, relasi, dan seeder. Flow end-to-end Approve → SO terbentuk oleh service masih belum ada.

Pemeriksaan pada 15 Oktober 2026 dibatasi pada lint PHP, kompilasi Blade, dan pendaftaran route. Test pada §5 tidak dibuat atau dijalankan.

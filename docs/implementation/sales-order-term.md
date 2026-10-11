# Implementation Checklist — Sales Order & Term

**Scope:** Entitas Sales Order (`trans_sales_order_hd`, `trans_sales_order_dt`) dan Term (`trans_sales_term`), termasuk pembentukan SO otomatis saat Quotation Approved (BR-17) dan transisi status termin (BR-04).

**Acuan:** [bisnis-flow/sales-order-term.md](../bisnis-flow/sales-order-term.md), [filament-resource.md](../filament-resource.md), [filament-lifecycle-hook.md](../filament-lifecycle-hook.md), [database.md](../database.md) §4.15–4.17

> **Reminder:** Tiap selesai item, update `docs/work/YYYY-MM-DD.md`.

## 1. Schema & Model

- [x] Migration `trans_sales_order_hd` (Ran) dengan unique `quotation_hd_id` dan kolom `payment_status_id`
- [x] Migration `trans_sales_order_dt` (Ran) dengan FK `quotation_dt_id` sebagai pointer ke sumber salinan
- [x] Migration `trans_sales_term` (Ran) — FK `quotation_hd_id` wajib, `order_hd_id` nullable; unique (`quotation_hd_id`, `sequence`)
- [x] Model `TransSalesOrderHd` dengan relasi `quotation`, `items`, `terms`, `project`, `invoices`, `status`, `paymentStatus`, `client`, `projectType`, `company`; cast `vat_type`, nominal `decimal:6`
- [x] Model `TransSalesOrderDt` dengan relasi `order`, `quotationDetail`, `item`, `unit`
- [x] Model `TransSalesTerm` dengan relasi `quotation`, `order`, `status`, `invoiceDetails`; cast `amount` `decimal:6`
- [x] Factory `TransSalesOrderHdFactory`, `TransSalesOrderDtFactory`, `TransSalesTermFactory`
- [x] Trait `AuditedBySoftDelete` + `SoftDeletes` pada ketiga model
- [x] Enum case `AutoCodePrefix::TransSalesOrderHd = 'SO'`
- [x] Enum `StatusType::Order` dan `StatusType::OrderPayment`
- [x] Enum `OrderStatus` (`waiting_dp`, `on_progress`, `closed`, `cancelled`) + 4 row aktif di `ms_status` tipe `order`
- [x] Enum `OrderPaymentStatus` (`unpaid`, `partial`, `paid`) + 3 row aktif di `ms_status` tipe `order_payment`
- [x] Enum `TermStatus` (`draft`, `pending`, `ready_to_bill`, `invoiced`, `paid`) + 5 row aktif di `ms_status` tipe `term`

## 2. Service

### 2.1 `app/Services/SalesOrder/CreateSalesOrderFromQuotationService`

Dipanggil dari `ApproveQuotationService` dalam satu `DB::transaction` (BR-17).

- [x] Guard: quotation status = `sent`; FK unique → lempar `DomainException` kalau SO sudah ada
- [x] Copy field finansial dari HD quotation → HD SO; generate `code` via `GenerateCodeService::next(AutoCodePrefix::TransSalesOrderHd)`
- [x] Copy semua `trans_sales_quotation_dt` → `trans_sales_order_dt` (dengan `quotation_dt_id` pointer)
- [x] Update `trans_sales_term`: set `order_hd_id` dan pertahankan `amount` yang sudah dihitung serta disimpan selama quotation; termin `progress_threshold = 0` → `ready_to_bill`, selain itu → `pending`
- [x] Set `status_id` SO = `waiting_dp`; Project belum dibuat sampai invoice DP Paid
- [x] Set `payment_status_id` SO = `unpaid`

### 2.2 `app/Services/SalesOrder/CheckTermReadyToBillService`

Dipanggil saat progress project berubah (BR-04). Fase project belum diimplementasikan; service tetap didesain supaya siap dipanggil.

- [ ] Loop termin SO status `pending` → kalau `progress_percentage ≥ progress_threshold` dan termin belum berada di invoice aktif, update ke `ready_to_bill`
- [ ] Trigger `TermReadyToBillNotification` ke Finance (di luar transaction)

### 2.3 `app/Services/SalesOrder/RecalculateOrderPaymentStatusService`

Dipanggil dari `ConfirmPaymentService` dan `VoidInvoiceService`.

- [ ] Hitung ulang `payment_status_id` SO dari jumlah termin aktif vs termin `paid` (unpaid / partial / paid)

## 3. Filament Resource `TransSalesOrderHdResource`

Group: **Penjualan**. Read-mostly — SO dibuat otomatis, tidak ada `CreateAction`.

- [ ] Properti: `$navigationLabel = 'Sales Order'`, `$slug = 'sales-orders'`, icon
- [ ] Page: List, View, Edit terbatas (hanya `notes`)
- [ ] Table: `code`, `transaction_date`, `quotation.code`, `client.name`, `grand_total`, `status.name` badge, `paymentStatus.name` badge, audit
- [ ] Filter table: `status_id`, `payment_status_id`, `client_id`, `company_id`
- [ ] Infolist: Informasi Utama + Nominal + status pembayaran + Audit
- [ ] Export Excel

### 3.1 Relation Manager

- [ ] `ItemsRelationManager` → `trans_sales_order_dt` (read-only)
- [ ] `TermsRelationManager` → `trans_sales_term` filter `order_hd_id`; status badge; edit hanya `notes`
- [ ] `InvoicesRelationManager` → `trans_sales_invoice_hd` (read-only; link ke resource invoice)

### 3.2 Custom Action di Sales Order

- [ ] `BuatInvoiceAction` → arahkan ke custom page pembuatan invoice (lihat [invoice.md](invoice.md) §3.1); visible saat ada termin `ready_to_bill`
- [ ] `BuatProjectAction` → arahkan ke form Create Project; visible hanya jika invoice DP Paid dan SO belum punya Project; setelah Project tersimpan SO → `on_progress`

## 4. Notification

- [ ] `TermReadyToBillNotification` — ke Finance tiap kali termin berpindah ke `ready_to_bill` (termasuk termin DP yang otomatis Ready to Bill saat SO dibuat)
- [ ] (Opsional) `SalesOrderCreatedNotification` — ke Marketing sebagai konfirmasi

## 5. Test

- [x] `TransactionSchemaTest::test_transaction_tables_and_relationships_support_the_sales_flow` — relasi SO ↔ Items ↔ Terms ↔ Project ↔ Invoice
- [x] `TransactionSchemaTest::test_transaction_sample_seeder_creates_an_idempotent_dp_flow` — seeder sampel sudah menghasilkan 1 SO + termin terikat `order_hd_id` dengan status `invoiced`
- [ ] Feature test **Approve → SO otomatis**: item disalin, termin bind ke `order_hd_id`, `amount` terhitung (BR-02), status termin set
- [ ] Feature test **Quotation tanpa DP ditolak saat Send**; SO selalu mempunyai satu termin DP threshold 0%
- [ ] Feature test **SO dengan DP → Waiting DP**: `payment_status = unpaid`
- [ ] Unit test guard `CreateSalesOrderFromQuotationService` menolak duplikat SO (FK unique)
- [ ] Unit test `CheckTermReadyToBillService` menolak termin yang sedang ada di invoice aktif

> Catatan: `TransactionSchemaTest` yang ada hanya memverifikasi schema, relasi, dan seeder. Flow end-to-end Approve → SO terbentuk oleh service masih belum ada.

# Implementation Checklist — Full Flow

**Scope:** Full work Marketing + Finance (migration, model, enum, service, Filament resource, relation manager, custom action, notification, PDF). Project schema sebagai scaffolding.

**Acuan:** [bisnis-flow/full-flow.md](../bisnis-flow/full-flow.md), [database.md](../database.md), [filament-lifecycle-hook.md](../filament-lifecycle-hook.md)

> **Reminder:** Tiap selesai item, update work log harian di `docs/work/YYYY-MM-DD.md` (ganti tanggal tiap hari, buat file baru untuk hari baru).

## Phase 0 — Prasyarat (enum + status)

- [ ] Update `AutoCodePrefix` (rename ke `TransSales*`, tambah `TransSalesOrderHd = 'SO'`)
- [ ] Update `StatusType` (tambah `Order`, `OrderPayment`)
- [ ] Buat `OrderStatus`, `OrderPaymentStatus` di `app/Enums/Status/`
- [ ] Update `ProjectStatus`, `ProjectStageStatus`, `TermStatus`, `InvoiceStatus`
- [ ] Buat `PaymentMethod` (cash/transfer/giro) di `app/Enums/`
- [ ] Update `SanitraMasterSeeder` loop 8 enum → 31 status
- [ ] Install `barryvdh/laravel-dompdf` untuk PDF
- [ ] Jalankan seeder dan verifikasi 31 status

## Phase 1 — Marketing (Quotation + Sales Order + Term)

Detail: [marketing.md](marketing.md)

- [ ] Migration + Model: `trans_sales_quotation_hd/dt`, `trans_sales_order_hd/dt`, `trans_sales_term`
- [ ] Service: `SendQuotationService`, `ApproveQuotationService` (trigger `CreateSalesOrderFromQuotationService`), `RejectQuotationService`, `ReviseQuotationService`
- [ ] Filament Resource `TransSalesQuotationHdResource` + Items/Terms Relation Manager
- [ ] Custom Action quotation: Send, Approve, Reject, Revisi, **Print PDF**
- [ ] Filament Resource `TransSalesOrderHdResource` (read-mostly) + Items/Terms/Invoices Relation Manager (read-only)
- [ ] PDF template Blade `resources/views/pdf/quotation.blade.php`
- [ ] Notification: `TermReadyToBillNotification` (ke Finance saat DP langsung ready to bill)
- [ ] Test flow: Draft → Sent → Approved → SO terbentuk otomatis

## Phase 2 — Project schema (scaffolding, tanpa service/resource)

- [ ] Migration + Model `trans_project_hd` (FK `order_hd_id` unique)
- [ ] Migration + Model `trans_project_dt`
- [ ] Migration + Model `trans_project_monitoring` (append-only)

Project resource dan service **ditunda** ke phase berikutnya di luar scope ini.

## Phase 3 — Finance (Invoice + Payment)

Detail: [finance.md](finance.md)

- [ ] Migration + Model: `trans_sales_invoice_hd/dt`, `trans_sales_payment`
- [ ] Service: `CreateInvoiceFromTermsService`, `VoidInvoiceService`, `RecordPaymentService`, `ConfirmPaymentService`, `RejectPaymentService`
- [ ] Custom Page: pemilihan termin Ready to Bill untuk pembuatan invoice
- [ ] Filament Resource `TransSalesInvoiceHdResource` + Items/Payments Relation Manager
- [ ] Custom Action invoice: Void, **Print PDF**
- [ ] Filament Resource `TransSalesPaymentResource`
- [ ] Custom Action payment: Confirm, Reject
- [ ] PDF template Blade `resources/views/pdf/invoice.blade.php`
- [ ] Dashboard Finance: widget outstanding, overdue, revenue bulan ini
- [ ] Notification: `FirstTermPaidNotification` (ke Teknis + PIC saat DP Paid)
- [ ] Test flow: Create Invoice DP → Payment Confirmed → trigger pembuatan project (stub)

## Phase 4 — Seeder data contoh (opsional, UAT)

- [ ] `SanitraTransactionSampleSeeder`: 1 quotation Draft + 1 Sent + 1 Approved (dengan SO + DP invoice + payment Pending)
- [ ] Jangan dipanggil dari `DatabaseSeeder` production

## Phase 5 — Verifikasi akhir

- [ ] Semua migration up (`php artisan migrate:status`)
- [ ] Semua resource bisa diakses dari panel Filament
- [ ] Flow end-to-end: buat quotation → approve → SO otomatis → bikin invoice DP → confirm payment → termin Paid, SO payment_status update
- [ ] Export Excel aktif di seluruh resource transaksi
- [ ] Activity log mencatat perubahan domain transaksi
- [ ] PDF quotation dan invoice bisa diunduh

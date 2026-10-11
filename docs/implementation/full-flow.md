# Implementation Map — Full Flow

**Scope:** Peta fase, dependensi antar modul, dan ringkasan progres implementasi modul Sales (+ scaffolding Project). Detail checklist per modul ada di file terpisah.

**Acuan:** [bisnis-flow/full-flow.md](../bisnis-flow/full-flow.md), [database.md](../database.md), [architecture.md](../architecture.md), [filament-lifecycle-hook.md](../filament-lifecycle-hook.md)

**Status audit 11 Oktober 2026:** Enum dan **31 status aktif** sudah tersedia (`active = true` pada `ms_status`). Sebelas migration transaksi sudah `Ran`; model, factory, relasi, trait audit, dan seeder data contoh sudah lengkap. Service bisnis, Filament resource transaksi, PDF, notifikasi, dan feature test flow end-to-end belum dibuat. `tests/Feature/TransactionSchemaTest.php` memverifikasi schema/relasi/seeder — **bukan** flow bisnis end-to-end.

> **Reminder:** Tiap selesai item di salah satu checklist modul, update `docs/work/YYYY-MM-DD.md` (file baru per hari).

## 1. Peta Modul

| Modul | File checklist | Entitas utama |
| --- | --- | --- |
| Quotation | [quotation.md](quotation.md) | `trans_sales_quotation_hd/dt` |
| Sales Order & Term | [sales-order-term.md](sales-order-term.md) | `trans_sales_order_hd/dt`, `trans_sales_term` |
| Invoice | [invoice.md](invoice.md) | `trans_sales_invoice_hd/dt` |
| Payment | [payment.md](payment.md) | `trans_sales_payment` |

Schema **Project** sudah ada. Form Create Project manual serta service yang menyimpan Project dan tahapnya wajib tersedia sebelum flow setelah DP dinyatakan selesai; fitur Project lainnya (override dan monitoring lengkap) tetap fase berikutnya (lihat §4).

## 2. Dependensi antar Modul

```
Phase 0 (prasyarat) ──► Quotation ──Approved──► Sales Order & Term
                                                      │
                                                      └─► Invoice DP ──► Payment penuh Confirmed
                                                                                 │
                                                                                 ▼
                                                                      Project dibuat manual
                                                                                 │
                                                                                 ▼
                                                                      Termin progress ──► Invoice
```

Urutan implementasi yang disarankan:

1. **Phase 0** — enum + status + dependency tambahan (dompdf). Lihat §3.
2. **Quotation** — form input + revisi + PDF. Jadi blocker Sales Order karena `ApproveQuotationService` memicu pembuatan SO.
3. **Sales Order & Term** — service pembentukan SO otomatis + relation manager; wajib ada sebelum Invoice karena Invoice selalu dari SO.
4. **Invoice** — custom page pemilihan termin DP + kunci tahap Completed untuk invoice berikutnya.
5. **Project minimal** — form Create Project dari SO yang DP-nya Paid; service menyimpan Project dan menyalin tahap dari workflow.
6. **Payment** — Confirm payment penuh memicu invoice Paid dan SO payment status; DP Paid membuka akses Create Project manual. Penutupan SO mengikuti progress dan seluruh termin Paid (BR-13).

## 3. Phase 0 — Prasyarat (enum + status + dependency)

| Item | Status | Catatan |
| --- | --- | --- |
| Enum `AutoCodePrefix` — rename ke `TransSales*` + tambah `TransSalesOrderHd = 'SO'` | [x] | 7 master + 5 transaksi (QUO/SO/PRO/INV/PAY) |
| Enum `StatusType` — tambah `Order`, `OrderPayment` | [x] | 8 case |
| Enum `OrderStatus`, `OrderPaymentStatus` di `app/Enums/Status/` | [x] | — |
| Update `ProjectStatus`, `ProjectStageStatus` (`blocked`), `TermStatus`, `InvoiceStatus` | [x] | 8 enum status total |
| Enum `PaymentMethod` di `app/Enums/` (`cash`, `transfer`, `giro`) | [x] | Terpasang pada cast model `TransSalesPayment` |
| `StatusSeeder` loop 8 enum → `ms_status` (dipanggil `SanitraMasterSeeder`) | [x] | **31 status aktif terverifikasi** via `database-query` |
| `SanitraTransactionSampleSeeder` sampel UAT (3 quotation, 1 SO, 1 invoice DP, 1 payment pending) | [x] | Idempoten; tidak dipanggil dari `DatabaseSeeder` production |
| Install `barryvdh/laravel-dompdf` | [ ] | Belum ada di `composer show --direct` |

## 4. Project (schema selesai; service minimal diperlukan)

| Item | Status |
| --- | --- |
| Migration + Model `trans_project_hd` (unique `order_hd_id`) | [x] |
| Migration + Model `trans_project_dt` (`editable` boolean; status `blocked` wajib catatan via `TransProjectDtObserver`) | [x] |
| Migration + Model `trans_project_monitoring` (append-only via `booted()` throw `LogicException` saat update/delete) | [x] |
| Factory `TransProjectHdFactory`, `TransProjectDtFactory`, `TransProjectMonitoringFactory` | [x] |
| Form Create Project dari SO dengan invoice DP Paid; isi PIC, nama, tanggal mulai/selesai | [ ] |
| Service `CreateProjectFromSalesOrderService` dan `CreateProjectStagesService` dipanggil dari form; cegah Project ganda per SO | [ ] |
| `UpdateProjectStageService` untuk progress dan termin berikutnya | [ ] |
| Halaman/form Create Project dari SO yang DP-nya Paid | [ ] |

> Alur yang membuat Project harus memakai service Project yang benar-benar menyimpan Project dan tahapnya. Checklist modul boleh dikerjakan bertahap, tetapi flow end-to-end belum selesai sebelum integrasi Project nyata tersedia.

## 5. Ringkasan Progres per Modul

| Modul | Schema & Model | Service | Filament | PDF | Notifikasi | Test flow |
| --- | --- | --- | --- | --- | --- | --- |
| Quotation | [x] | [ ] | [ ] | [ ] | [ ] | [ ] |
| Sales Order & Term | [x] | [ ] | [ ] | — | [ ] | [ ] |
| Invoice | [x] | [ ] | [ ] | [ ] | [ ] | [ ] |
| Payment | [x] | [ ] | [ ] | — | [ ] | [ ] |

**Catatan status schema/model:** 11 migration transaksi (`trans_sales_quotation_hd/dt`, `trans_sales_order_hd/dt`, `trans_sales_term`, `trans_project_hd/dt/monitoring`, `trans_sales_invoice_hd/dt`, `trans_sales_payment`) sudah `Ran`; model, factory, cast, dan relasi lengkap. Lihat setiap checklist modul untuk breakdown yang spesifik.

**Catatan test:** `tests/Feature/TransactionSchemaTest.php` memverifikasi schema, relasi, dan seeder (4 test, 22 assertion) — bukan flow end-to-end (Send → Approve → SO → Invoice → Payment). Flow bisnis tetap perlu feature test per transisi service.

## 6. Verifikasi Akhir (setelah semua modul selesai)

- [x] Semua migration up (`php artisan migrate:status`) — status saat ini: migration master + transaksi semuanya `Ran`
- [ ] Semua resource transaksi bisa diakses dari panel Filament (group **Penjualan** + **Keuangan**)
- [ ] Flow end-to-end: Quotation Draft → Sent → Approved → SO otomatis → Invoice DP → Payment penuh Confirmed → user buat Project → termin progress Ready to Bill → Invoice progress → … → SO Closed
- [ ] Export Excel aktif di seluruh resource transaksi
- [ ] Activity log mencatat perubahan domain transaksi
- [ ] PDF quotation dan invoice bisa diunduh

## 7. Fase Berikutnya (dashboard, UAT & reminder)

- [x] `SanitraTransactionSampleSeeder` — tidak dipanggil dari `DatabaseSeeder` production
- [ ] Dashboard Finance (FR-41): statistik outstanding, overdue, revenue, dan chart
- [ ] Scheduler: notifikasi invoice H-3 jatuh tempo via Laravel Scheduler + cron (shared hosting, bukan queue worker)

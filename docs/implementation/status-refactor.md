# Implementation Checklist — Status Refactor (Sales Module)

**Scope:** Sederhanakan status transaksi modul Sales dari 8 tipe/28 status menjadi 7 tipe/25 status. DB di-reset dari nol — tidak ada mapping data lama.

**Keputusan final status (7 tipe, 25 status):**

| type | code → label |
|---|---|
| quotation | draft · sent · approved · rejected (TETAP) |
| term | draft · pending · ready_to_bill · invoiced · paid (TETAP) |
| order | **draft** Menunggu DP · **open** Berjalan · **closed** Selesai/Ditutup |
| invoice | unpaid · paid · void (HAPUS partial) |
| payment | pending · confirmed · rejected (TETAP) |
| project | on_progress · completed · cancelled (HAPUS open) |
| project_dt | draft · on_progress · completed · blocked (TETAP) |
| ~~order_payment~~ | **HAPUS seluruh tipe** |

> **Reminder:** Tiap selesai item, update `docs/work/YYYY-MM-DD.md`.

---

## 1. Enum

- [x] `app/Enums/Status/OrderStatus.php` — ubah case: `Draft = 'draft'`, `Open = 'open'`, `Closed = 'closed'`; hapus `WaitingDp`, `OnProgress`, `Cancelled`; update `label()` + `color()`; badge `closed` label & warna bergantung `close_reason` (handle di Filament, bukan di sini)
- [x] `app/Enums/Status/OrderPaymentStatus.php` — **HAPUS file**
- [x] `app/Enums/Status/InvoiceStatus.php` — hapus case `Partial`
- [x] `app/Enums/Status/ProjectStatus.php` — hapus case `Open`
- [x] `app/Enums/StatusType.php` — hapus case `OrderPayment = 'order_payment'`
- [x] `app/Enums/Status/OrderCloseReason.php` — **BUAT BARU**: `Completed = 'completed'`, `Manual = 'manual'`; tambah `label()` dan `color()`

---

## 2. Migration

Buat **satu** migration baru (`php artisan make:migration`), jangan edit migration lama.

- [x] `trans_sales_order_hd`: tambah kolom `close_reason` (string, nullable) + `close_notes` (text, nullable)
- [x] `trans_sales_order_hd`: drop FK `payment_status_id` lalu drop kolom
- [x] `down()`: kembalikan kolom (tanpa data mapping)

---

## 3. Model

- [x] `app/Models/TransSalesOrderHd.php`
  - Hapus relasi `paymentStatus()`
  - Tambah cast `'close_reason' => OrderCloseReason::class` di `casts()`
  - Tambah method `paymentSummary(): string` — hitung dari termin: semua `paid` → "Lunas"; ada yang paid → "Dibayar Sebagian"; tidak ada → "Belum Dibayar"

---

## 4. Service

- [x] **HAPUS** `app/Services/SalesOrder/RecalculateOrderPaymentStatusService.php`
- [x] `app/Services/SalesOrder/CreateSalesOrderFromQuotationService.php`
  - Ganti `OrderStatus::WaitingDp` → `OrderStatus::Draft`
  - Hapus import + set `payment_status_id`
- [x] `app/Services/Payment/ConfirmPaymentService.php`
  - Hapus dependency `RecalculateOrderPaymentStatusService`
  - Tambah transisi SO: setelah invoice DP jadi Paid → SO `draft` → `open` (dalam transaction yang sama); DP = termin dengan `progress_threshold = 0`
- [x] `app/Services/Invoice/VoidInvoiceService.php`
  - Hapus dependency `RecalculateOrderPaymentStatusService`
- [x] `app/Services/Project/CreateProjectFromSalesOrderService.php`
  - Guard: ganti `OrderStatus::WaitingDp` → `OrderStatus::Open`
  - Project status awal: ganti `ProjectStatus::Open` → `ProjectStatus::OnProgress`
  - Hapus update SO ke `OnProgress` (membuat project tidak lagi mengubah status SO)
- [x] **BUAT** `app/Services/SalesOrder/CloseOrderService.php`
  - Guard: SO status `draft` atau `open`; tidak ada invoice status `unpaid`; role Owner/Super Admin (via policy/Gate)
  - Jika SO punya project berjalan → project jadi `cancelled`
  - Set `status_id = closed`, `close_reason`, `close_notes`
  - Pola: `DB::transaction` + guard `DomainException`

---

## 5. Seeder & Factory

- [x] `database/seeders/StatusSeeder.php`
  - Hapus `OrderPaymentStatus::class` dari loop
  - Tambah blok khusus: `MsStatus::where('type', 'order_payment')->update(['active' => false])` agar baris lama dinonaktifkan saat seeder jalan ulang
- [x] `database/seeders/SanitraTransactionSampleSeeder.php`
  - Ganti `'waiting_dp'` → `'draft'`
  - Hapus `'payment_status_id'` dari semua `create()`
- [x] `database/factories/TransSalesOrderHdFactory.php`
  - Ganti `'on_progress'` → `'open'`; hapus `payment_status_id`
- [x] `database/factories/TransProjectHdFactory.php`
  - Ganti `'open'` → `'on_progress'`

---

## 6. Filament Resource

### Sales Order

- [x] `TransSalesOrderHdsTable.php`
  - Hapus kolom `paymentStatus.name` dan import `OrderPaymentStatus`
  - Hapus filter `payment_status_id`
  - Tambah kolom komputasi status bayar via `paymentSummary()` accessor (optional, tanpa filter)
- [x] `TransSalesOrderHdInfolist.php`
  - Hapus entry `paymentStatus.name` dan import `OrderPaymentStatus`
  - Tambah entry `close_reason` + `close_notes` (hanya tampil jika `close_reason` terisi)
  - Badge SO `closed`: label bergantung `close_reason` — `completed` → "Selesai" / `manual` → "Ditutup"
- [x] `Pages/ViewTransSalesOrderHd.php`
  - Ganti semua cek `OrderStatus::WaitingDp` → `OrderStatus::Open` pada visibility tombol Buat Project
  - Tambah action **Tutup SO**: visible saat SO `draft`/`open` + permission Owner/Super Admin; modal wajib isi alasan; panggil `CloseOrderService`
- [x] `Pages/CreateOrderProject.php`
  - Ganti guard `OrderStatus::WaitingDp` → `OrderStatus::Open`

### Invoice (sisa referensi `partial`)

- [x] Cek `app/Filament/Resources/TransSalesInvoiceHds/` untuk sisa referensi `InvoiceStatus::Partial` — hapus bila ada (tidak ditemukan)

---

## 7. Dokumentasi

- [x] `docs/bisnis-flow/sales-order-term.md` §2.2–2.3 dan §2.6–2.7
- [x] `docs/bisnis-flow/full-flow.md` §4
- [x] `docs/bisnis-flow/payment.md`
- [x] `docs/bisnis-flow/invoice.md` (hapus partial)
- [x] `docs/database.md` §4.12 (25 status, 7 tipe) dan §4.15 (kolom SO: tambah close_reason/close_notes, hapus payment_status_id)
- [x] `docs/architecture.md` §5.3 (daftar enum)
- [x] `docs/implementation/sales-order-term.md` (checklist SO & project)
- [x] `docs/implementation/payment.md` (hapus RecalculateOrderPaymentStatusService)
- [x] `docs/implementation/invoice.md` (hapus partial)

---

## Checklist Uji Manual (setelah selesai)

1. Approve quotation → SO baru status "Menunggu DP" (`draft`)
2. Buat invoice DP → catat payment → konfirmasi → invoice Lunas, termin DP Lunas, SO jadi "Berjalan" (`open`)
3. Payment ditolak → SO tetap "Menunggu DP", bisa catat payment baru
4. Tombol Buat Project hanya muncul saat SO `open` dan belum punya project; project baru status "Berjalan"
5. Tutup SO manual: alasan wajib, ditolak jika ada invoice `unpaid`, project ikut `cancelled`, badge SO "Ditutup"
6. List & detail SO tidak tampilkan kolom/filter status bayar lama

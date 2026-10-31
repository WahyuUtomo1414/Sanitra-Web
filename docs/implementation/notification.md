# Implementation Checklist — Notifikasi Email

**Scope:** Semua notifikasi email aplikasi Sanitra — trigger, penerima, penempatan file, dan pola implementasi.

**Acuan:** [bisnis-flow/full-flow.md](../bisnis-flow/full-flow.md), [bisnis-flow/payment.md](../bisnis-flow/payment.md), [bisnis-flow/sales-order-term.md](../bisnis-flow/sales-order-term.md)

> **Reminder:** Tiap selesai item, update `docs/work/YYYY-MM-DD.md`.

---

## 1. Mekanisme Umum

**Penerima berdasarkan role Spatie Permission, alamat dari employee:**

```php
// Pilih user ber-role Finance, lalu kirim ke email employee yang terhubung.
$financeUsers = User::role('Finance')->with('employee')->get();
$financeEmployeeEmails = $financeUsers->pluck('employee.email')->filter()->unique();
```

**Dua jenis pengiriman:**

| Jenis | Kapan dikirim | Contoh |
|---|---|---|
| **Dipicu service** | Langsung saat service dieksekusi (oleh aksi user), setelah `DB::transaction` commit — masih dalam request yang sama | `TermReadyToBillNotification`, `ProjectCreatedNotification` |
| **Scheduler** | Setiap hari jam 08:00 via Laravel Scheduler + cron — bukan dipicu aksi user | `InvoiceOverdueNotification` |

**Aturan penempatan dalam kode:**
- Notifikasi selalu dipanggil **setelah** `DB::transaction` commit — tidak pernah di dalam transaction.
- Karena shared hosting (sync, tanpa queue worker), semua notifikasi dikirim **synchronous** langsung via `Mail` channel.
- Disable queue di `Notification` class: tidak perlu `implements ShouldQueue`.

**Config SMTP** (`.env`):
```
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=465
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=alamat-yang-sama-dengan-MAIL_USERNAME
MAIL_FROM_NAME="Sanitra"
```

---

## 2. Daftar Notifikasi

### Prioritas: Wajib (business-critical)

| # | Class | Trigger | Penerima | Dipanggil dari |
|---|---|---|---|---|
| 1 | `TermReadyToBillNotification` | Termin berubah ke `ready_to_bill`, termasuk DP saat SO dibuat | Email `ms_employee.email` milik user ber-role **Finance** | `CreateSalesOrderFromQuotationService::execute()` dan `CheckTermReadyToBillService::execute()` setelah commit |
| 2 | `ProjectCreatedNotification` | Project baru dibuat dari SO `open` | Email `ms_employee.email` milik user ber-role **Teknis** + PIC employee | `CreateProjectFromSalesOrderService::execute()` setelah commit |

### Prioritas: Opsional (log / informasional)

| # | Class | Trigger | Penerima | Dipanggil dari |
|---|---|---|---|---|
| 3 | `InvoiceCreatedNotification` | Invoice baru dibuat | Role **Finance** | `CreateInvoiceFromTermsService::execute()` setelah commit |
| 4 | `PaymentRejectedNotification` | Payment ditolak | Role **Finance** | `RejectPaymentService::execute()` setelah commit |
| 5 | `QuotationApprovedNotification` | Quotation disetujui | Role **Marketing** + **super_admin** | `ApproveQuotationService::execute()` setelah commit |
| 6 | `SalesOrderCreatedNotification` | SO dibuat otomatis dari Quotation | Role **Marketing** | `CreateSalesOrderFromQuotationService::execute()` setelah commit |

### Prioritas: Opsional (Scheduler)

| # | Class | Trigger | Penerima | Mekanisme |
|---|---|---|---|---|
| 7 | `InvoiceOverdueNotification` | H-3 sebelum `due_date` invoice `unpaid` | Role **Finance** | Laravel Scheduler + cron shared hosting |

---

## 3. Penempatan File

```
app/
├── Notifications/
│   ├── TermReadyToBillNotification.php       ← #1
│   ├── ProjectCreatedNotification.php        ← #2
│   ├── InvoiceCreatedNotification.php        ← #3
│   ├── PaymentRejectedNotification.php       ← #4
│   ├── QuotationApprovedNotification.php     ← #5
│   ├── SalesOrderCreatedNotification.php     ← #6
│   └── InvoiceOverdueNotification.php        ← #7
└── Console/
    └── Commands/
        └── SendInvoiceOverdueReminder.php    ← command untuk #7
```

Scheduler didaftarkan di `routes/console.php`:
```php
Schedule::command('sanitra:invoice-overdue-reminder')->dailyAt('08:00');
```

---

## 4. Pola Implementasi

### Dua notifikasi wajib yang sudah terpasang

- [`TermReadyToBillNotification`](../../app/Notifications/TermReadyToBillNotification.php) dan [`ProjectCreatedNotification`](../../app/Notifications/ProjectCreatedNotification.php) memakai channel `mail` sinkron dengan `MailMessage`. Template HTML Sanitra diterapkan oleh view mail bersama.
- Pembuatan SO mendaftarkan pengiriman untuk termin DP, sedangkan `CheckTermReadyToBillService` mendaftarkannya untuk termin progress yang baru siap ditagih. Keduanya memakai [`NotifyTermReadyToBillService`](../../app/Services/SalesOrder/NotifyTermReadyToBillService.php) untuk memilih user ber-role Finance dan mengirim ke alamat employee terkait, bukan `users.email`.
- Pembuatan Project mendaftarkan pengiriman ke alamat employee milik user ber-role Teknis dan ke email `picEmployee` melalui on-demand notification. Bila alamatnya sama, email hanya dikirim sekali.
- Jika tidak ada alamat employee yang valid untuk role penerima, aplikasi mencatat warning di log; tidak ada fallback ke `users.email`.
- Semua pemicu menggunakan `DB::afterCommit()`, sehingga pengiriman berlangsung setelah transaksi terluar berhasil dan dibatalkan bila transaksi di-rollback. Kegagalan pengiriman dilaporkan melalui `report()` tanpa membatalkan data bisnis yang sudah tersimpan.

### Scheduler command (#7) — rancangan, belum diimplementasikan

```php
// app/Console/Commands/SendInvoiceOverdueReminder.php
public function handle(): void
{
    $invoices = TransSalesInvoiceHd::whereRelation('status', 'code', InvoiceStatus::Unpaid->value)
        ->whereBetween('due_date', [today()->addDays(1), today()->addDays(3)])
        ->get();

    $recipients = User::role('Finance')->with('employee')->get()
        ->pluck('employee')->filter(fn ($employee) => $employee?->email)
        ->unique('email');

    foreach ($invoices as $invoice) {
        foreach ($recipients as $employee) {
            Notification::route('mail', [$employee->email => $employee->name])
                ->notify(new InvoiceOverdueNotification($invoice));
        }
    }
}
```

---

## 5. Checklist Implementasi

### Wajib

- [x] `TermReadyToBillNotification` — class + pengiriman ke role Finance saat DP siap pada pembuatan SO atau termin lain berubah status lewat `CheckTermReadyToBillService`; memakai `MailMessage` dan `DB::afterCommit`
- [x] `ProjectCreatedNotification` — class + pengiriman ke role Teknis dan email PIC melalui `CreateProjectFromSalesOrderService`; alamat yang sama tidak dikirimi dua kali; memakai `MailMessage` dan `DB::afterCommit`

### Opsional

- [ ] `InvoiceCreatedNotification`
- [ ] `PaymentRejectedNotification`
- [ ] `QuotationApprovedNotification`
- [ ] `SalesOrderCreatedNotification`

### Scheduler

- [ ] `InvoiceOverdueNotification` + `SendInvoiceOverdueReminder` command
- [ ] Daftarkan schedule di `routes/console.php`
- [ ] Setup cron di shared hosting: `* * * * * php /path/to/artisan schedule:run`

---

## 6. Catatan review code (31 Oktober 2026)

Hasil review implementasi dua notifikasi wajib. Alamat penerima telah diperbaiki agar memakai `ms_employee.email`. Catatan berikut menjelaskan perilaku pengiriman yang masih berlaku.

1. **SMTP sinkron di request HTTP.** Semua notifikasi dikirim dalam HTTP request yang sama (shared hosting constraint). Kalau SMTP server lambat atau timeout (default Symfony Mailer ~30 detik), user akan menunggu lama di aksi (Setujui Quotation, Buat Project, dsb.). Skala Sanitra saat ini (1–2 user per role) aman. Mitigasi kalau role tumbuh ≥10 user: tetap sinkron, tapi pakai provider SMTP latency rendah seperti Mailgun atau SES (biasanya <2 detik) dan monitor timeout di `sys_error_log`.

2. **Loop email satu per satu.** `NotifyTermReadyToBillService::execute()` dan `CreateProjectFromSalesOrderService::notifyProjectCreated()` iterasi alamat employee yang valid dan unik, lalu memakai on-demand notification per alamat → N alamat = N kali pengiriman SMTP.

3. **Idempotency implicit via query, bukan guard eksplisit.** Kalau `CheckTermReadyToBillService` kebetulan dipanggil dua kali dengan progress yang sama, teoritis bisa trigger notifikasi dobel. Praktiknya aman karena query filter `where code = 'pending'` → termin yang sudah `ready_to_bill` di call pertama tidak masuk hasil query call kedua. Keamanan ini **bergantung pada state DB**, bukan pada guard di service. Kalau nanti ada caller yang bypass update status (misal batch job), guard eksplisit mungkin dibutuhkan.

4. **Semua alamat employee via on-demand notification.** Finance, Teknis, dan PIC menerima email melalui `Notification::route('mail', [...])->notify(...)` → `AnonymousNotifiable`. Class notifikasi tidak mengakses properti `$notifiable`, sehingga penerima tidak harus berupa model User.

---

## 7. Status

**31 Oktober 2026:** Dua notifikasi wajib sudah terpasang dan memakai template email Sanitra yang telah dipublish. Pengiriman SMTP nyata belum diverifikasi dan test belum dijalankan. Empat notifikasi opsional serta pengingat invoice terjadwal ditunda sesuai keputusan saat ini; tidak ada scheduler atau worker yang ditambahkan.

Prasyarat uji manual: user harus memiliki role Finance/Teknis, relasi `employee_id` yang valid, serta `ms_employee.email` yang valid. `users.email` hanya dipakai untuk login, bukan alamat tujuan dua notifikasi wajib.

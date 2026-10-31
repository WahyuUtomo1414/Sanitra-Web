# Implementation Checklist — Notifikasi Email

**Scope:** Semua notifikasi email aplikasi Sanitra — trigger, penerima, penempatan file, dan pola implementasi.

**Acuan:** [bisnis-flow/full-flow.md](../bisnis-flow/full-flow.md), [bisnis-flow/payment.md](../bisnis-flow/payment.md), [bisnis-flow/sales-order-term.md](../bisnis-flow/sales-order-term.md)

> **Reminder:** Tiap selesai item, update `docs/work/YYYY-MM-DD.md`.

---

## 1. Mekanisme Umum

**Penerima berdasarkan role Spatie Permission:**

```php
// Contoh: kirim ke semua user ber-role Finance
$recipients = User::role('Finance')->get();
Notification::send($recipients, new TermReadyToBillNotification($term));
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
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@sanitra.co.id
MAIL_FROM_NAME="Sanitra"
```

---

## 2. Daftar Notifikasi

### Prioritas: Wajib (business-critical)

| # | Class | Trigger | Penerima | Dipanggil dari |
|---|---|---|---|---|
| 1 | `TermReadyToBillNotification` | Termin berubah ke `ready_to_bill`, termasuk DP saat SO dibuat | Role **Finance** | `CreateSalesOrderFromQuotationService::execute()` dan `CheckTermReadyToBillService::execute()` setelah commit |
| 2 | `ProjectCreatedNotification` | Project baru dibuat dari SO `open` | Role **Teknis** + PIC employee (email langsung) | `CreateProjectFromSalesOrderService::execute()` setelah commit |

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
- Pembuatan SO mendaftarkan pengiriman untuk termin DP, sedangkan `CheckTermReadyToBillService` mendaftarkannya untuk termin progress yang baru siap ditagih. Keduanya memakai [`NotifyTermReadyToBillService`](../../app/Services/SalesOrder/NotifyTermReadyToBillService.php) untuk memilih pengguna Finance.
- Pembuatan Project mendaftarkan pengiriman ke pengguna Teknis dan ke email `picEmployee` melalui on-demand notification. Bila alamat PIC sama dengan alamat salah satu pengguna Teknis, email hanya dikirim sekali.
- Semua pemicu menggunakan `DB::afterCommit()`, sehingga pengiriman berlangsung setelah transaksi terluar berhasil dan dibatalkan bila transaksi di-rollback. Kegagalan pengiriman dilaporkan melalui `report()` tanpa membatalkan data bisnis yang sudah tersimpan.

### Scheduler command (#7) — rancangan, belum diimplementasikan

```php
// app/Console/Commands/SendInvoiceOverdueReminder.php
public function handle(): void
{
    $invoices = TransSalesInvoiceHd::whereRelation('status', 'code', InvoiceStatus::Unpaid->value)
        ->whereBetween('due_date', [today()->addDays(1), today()->addDays(3)])
        ->get();

    $recipients = User::role('Finance')->get();

    foreach ($invoices as $invoice) {
        Notification::send($recipients, new InvoiceOverdueNotification($invoice));
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

Hasil review implementasi dua notifikasi wajib. Tidak ada bug yang ditemukan; empat catatan berikut adalah observasi arsitektural untuk referensi, bukan blocker.

1. **SMTP sinkron di request HTTP.** Semua notifikasi dikirim dalam HTTP request yang sama (shared hosting constraint). Kalau SMTP server lambat atau timeout (default Symfony Mailer ~30 detik), user akan menunggu lama di aksi (Setujui Quotation, Buat Project, dsb.). Skala Sanitra saat ini (1–2 user per role) aman. Mitigasi kalau role tumbuh ≥10 user: tetap sinkron, tapi pakai provider SMTP latency rendah seperti Mailgun atau SES (biasanya <2 detik) dan monitor timeout di `sys_error_log`.

2. **Loop email satu per satu.** `NotifyTermReadyToBillService::execute()` dan `CreateProjectFromSalesOrderService::notifyProjectCreated()` iterasi recipient dan panggil `$recipient->notify(...)` per user → N user = N kali koneksi SMTP. Untuk skala sekarang tidak urgent. Bisa dioptimasi dengan `Notification::sendNow($users, $notification)` yang batch kirim di internal Laravel; perubahan API minimal.

3. **Idempotency implicit via query, bukan guard eksplisit.** Kalau `CheckTermReadyToBillService` kebetulan dipanggil dua kali dengan progress yang sama, teoritis bisa trigger notifikasi dobel. Praktiknya aman karena query filter `where code = 'pending'` → termin yang sudah `ready_to_bill` di call pertama tidak masuk hasil query call kedua. Keamanan ini **bergantung pada state DB**, bukan pada guard di service. Kalau nanti ada caller yang bypass update status (misal batch job), guard eksplisit mungkin dibutuhkan.

4. **PIC via on-demand notification tanpa akun user.** `CreateProjectFromSalesOrderService::notifyProjectCreated()` kirim ke `picEmployee->email` lewat `Notification::route('mail', [...])->notify(...)` → `AnonymousNotifiable`. `ProjectCreatedNotification::toMail()` tidak mengakses properti `$notifiable` (tidak ada `$notifiable->name` atau `$notifiable->email`), jadi aman untuk PIC tanpa akun user. Kalau kelak notifikasi butuh info notifiable (misal personal greeting), harus guard `instanceof User` dulu.

---

## 7. Status

**31 Oktober 2026:** Dua notifikasi wajib sudah terpasang dan memakai template email Sanitra yang telah dipublish. Pengiriman SMTP nyata belum diverifikasi dan test belum dijalankan. Empat notifikasi opsional serta pengingat invoice terjadwal ditunda sesuai keputusan saat ini; tidak ada scheduler atau worker yang ditambahkan.

Prasyarat uji manual: `RoleSeeder` dan `UserSeeder` wajib dijalankan sebelum trigger notifikasi — tanpa itu, `User::role('Finance'|'Teknis')->get()` return kosong dan notifikasi tidak sampai ke siapa pun.

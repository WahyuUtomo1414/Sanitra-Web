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
| 1 | `TermReadyToBillNotification` | Termin berubah ke `ready_to_bill` | Role **Finance** | `CheckTermReadyToBillService::execute()` setelah commit |
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

### Notification class

```php
namespace App\Notifications;

use App\Models\TransSalesTerm;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TermReadyToBillNotification extends Notification
{
    public function __construct(public readonly TransSalesTerm $term) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Termin Siap Ditagih — ' . $this->term->name)
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Termin **' . $this->term->name . '** pada SO ' . $this->term->order?->code . ' sudah siap ditagih.')
            ->action('Lihat Sales Order', url('/dashboard/sales-orders/' . $this->term->order_hd_id));
    }
}
```

### Pemanggilan dari service (setelah commit)

```php
// Di dalam service, setelah DB::transaction selesai
DB::transaction(function () use (...): void {
    // ... logic ...
});

// Di luar transaction:
$recipients = User::role('Finance')->get();
Notification::send($recipients, new TermReadyToBillNotification($term));
```

### ProjectCreatedNotification — penerima ganda

```php
// Teknis by role
$teknisUsers = User::role('Teknis')->get();
Notification::send($teknisUsers, new ProjectCreatedNotification($project));

// PIC by email langsung (jika PIC punya email employee)
$picEmail = $project->pic?->email;
if ($picEmail && filter_var($picEmail, FILTER_VALIDATE_EMAIL)) {
    Mail::to($picEmail)->send(new ProjectCreatedMail($project));
}
```

### Scheduler command (#7)

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

- [ ] `TermReadyToBillNotification` — class + panggilan di `CheckTermReadyToBillService`
- [ ] `ProjectCreatedNotification` — class + panggilan di `CreateProjectFromSalesOrderService`

### Opsional

- [ ] `InvoiceCreatedNotification`
- [ ] `PaymentRejectedNotification`
- [ ] `QuotationApprovedNotification`
- [ ] `SalesOrderCreatedNotification`

### Scheduler

- [ ] `InvoiceOverdueNotification` + `SendInvoiceOverdueReminder` command
- [ ] Daftarkan schedule di `routes/console.php`
- [ ] Setup cron di shared hosting: `* * * * * php /path/to/artisan schedule:run`

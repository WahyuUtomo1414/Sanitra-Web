# Implementasi Custom Email Template — Sanitra

**Scope:** Panduan implementasi custom HTML email template; checklist mencerminkan status kode saat ini.

**Acuan desain:** [`email-template.md`](email-template.md), [`notification.md`](notification.md)

**Urutan kerja:** Layout → Partial → Mailable wajib (#1 #2) → Update Notification class → Cleanup vendor

---

## Prasyarat

- Model `MsCompany` sudah ada, row utama `id = 1` berisi `legal_name`, `address`, `phone`, `email`, `website`, `logo`
- `TransSalesOrderHd` → relasi `client()` (BelongsTo `MsClient`) dan `terms()` (HasMany `TransSalesTerm`)
- `TransSalesTerm` → kolom: `term_name`, `percentage`, `amount` (decimal:6), `status_id`, `updated_at`
- dompdf (`barryvdh/laravel-dompdf`) sudah terpasang
- Logo company tersimpan di disk `public` path `companies/logos/...`

---

## Langkah 1 — Layout Bersama

**File aktual:** `resources/views/components/emails/layout.blade.php`

Layout bersama sudah dibuat sebagai Blade component `<x-emails.layout>`. Header dua tingkat menampilkan logo di tengah dan nama perusahaan tepat di bawahnya, lalu panel judul serta deskripsi email; teks "Sistem Notifikasi" dihapus. Footer abu muda menampilkan alamat, telepon, email, dan tautan `MsCompany.website` tanpa ikon. `SanitraMail` hanya memakai path logo dari `MsCompany.logo` pada disk `public`; tanpa file yang valid, nama perusahaan tetap tampil. Semua gaya tetap inline dan struktur utama tetap berbasis tabel untuk email client. Lihat [spesifikasi desain](email-template.md#3-desain-layout-componentsemailslayoutbladephp) dan file Blade sebagai sumber implementasi.

**Status:** selesai untuk dua notifikasi wajib; tampilan kiriman nyata belum diverifikasi.

---

## Langkah 2 — Partial Tabel Transaksi

**File:** `resources/views/emails/partials/transaction-table.blade.php`

Komponen generik yang menerima array `$rows` berisi pasangan `[label, value]`.

```blade
@props(['rows'])

<table width="100%" cellpadding="0" cellspacing="0"
       style="border-collapse:collapse;margin:20px 0;font-size:13px;">
  @foreach($rows as [$label, $value])
  <tr>
    <td style="padding:9px 14px;background:#f0f4ff;border:1px solid #bfdbfe;
                width:38%;color:#6b7280;font-weight:600;vertical-align:top;">
      {{ $label }}
    </td>
    <td style="padding:9px 14px;border:1px solid #bfdbfe;
                color:#111827;font-weight:700;vertical-align:top;">
      {{ $value ?? '—' }}
    </td>
  </tr>
  @endforeach
</table>
```

---

## Langkah 3 — Partial Tombol CTA

**File:** `resources/views/emails/partials/cta-button.blade.php`

```blade
@props(['url', 'label' => 'Lihat Detail'])

<table cellpadding="0" cellspacing="0" style="margin:24px 0;">
  <tr>
    <td style="border-radius:6px;background:#1e3a8a;">
      <a href="{{ $url }}"
         style="display:inline-block;padding:11px 28px;color:#ffffff;font-size:14px;
                font-weight:600;text-decoration:none;border-radius:6px;letter-spacing:0.2px;">
        {{ $label }}
      </a>
    </td>
  </tr>
</table>
```

---

## Langkah 4 — Base Mailable

**File:** `app/Mail/SanitraMail.php`

Abstract base yang meng-inject `MsCompany` sekali — semua Mailable turun dari sini.

```php
<?php

namespace App\Mail;

use App\Models\MsCompany;
use Illuminate\Mail\Mailable;

abstract class SanitraMail extends Mailable
{
    protected MsCompany $company;

    public function __construct()
    {
        $this->company = MsCompany::query()->oldest()->firstOrFail();
    }
}
```

> **Catatan:** `firstOrFail()` sengaja dibiarkan throw exception — kalau `ms_company` kosong, itu bug data, bukan kondisi yang perlu di-handle.

---

## Langkah 5 — Mailable Wajib

### 5.1 `TermReadyToBillMail`

**File:** `app/Mail/TermReadyToBillMail.php`

```php
<?php

namespace App\Mail;

use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use App\Models\TransSalesTerm;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TermReadyToBillMail extends SanitraMail
{
    public function __construct(public readonly TransSalesTerm $term)
    {
        parent::__construct();
        $this->term->loadMissing('order.client');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Sanitra] Termin Siap Ditagih — '.$this->term->term_name.' / '.$this->term->order?->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.term-ready-to-bill',
            with: [
                'company'  => $this->company,
                'term'     => $this->term,
                'order'    => $this->term->order,
                'actionUrl' => TransSalesOrderHdResource::getUrl('view', [
                    'record' => $this->term->order_hd_id,
                ]),
            ],
        );
    }
}
```

**View:** `resources/views/emails/term-ready-to-bill.blade.php`

```blade
<x-emails.layout :company="$company" :logoData="$logoData ?? null" docLabel="Termin Siap Ditagih" docDescription="Sales Order siap diproses oleh Finance.">

  <p style="color:#374151;margin:0 0 4px;font-size:14px;">
    Termin berikut sudah siap untuk dibuatkan invoice. Harap segera proses di sistem.
  </p>

  <x-emails.partials.transaction-table :rows="[
    ['Nomor SO',        $order?->code],
    ['Klien',           $order?->client?->name],
    ['Nama Termin',     $term->term_name],
    ['Persentase',      $term->percentage . '%'],
    ['Nilai Termin',    'Rp ' . number_format($term->amount, 0, ',', '.')],
    ['Tanggal',         $term->updated_at?->translatedFormat('d M Y')],
  ]"/>

  <x-emails.partials.cta-button :url="$actionUrl" label="Buat Invoice"/>

</x-emails.layout>
```

---

### 5.2 `ProjectCreatedMail`

**File:** `app/Mail/ProjectCreatedMail.php`

```php
<?php

namespace App\Mail;

use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use App\Models\TransProjectHd;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ProjectCreatedMail extends SanitraMail
{
    public function __construct(public readonly TransProjectHd $project)
    {
        parent::__construct();
        $this->project->loadMissing('order.client', 'order.quotation', 'picEmployee');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Sanitra] Project Baru Dibuat — '.$this->project->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.project-created',
            with: [
                'company'   => $this->company,
                'project'   => $this->project,
                'order'     => $this->project->order,
                'actionUrl' => TransSalesOrderHdResource::getUrl('view', [
                    'record' => $this->project->order_hd_id,
                ]),
            ],
        );
    }

    /** @return array<Attachment> */
    public function attachments(): array
    {
        $quotation = $this->project->order?->quotation;

        if (! $quotation) {
            return [];
        }

        $pdf = Pdf::loadView('pdf.quotation', ['quotation' => $quotation->loadMissing('items.item', 'client')]);

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                'Penawaran-'.$quotation->code.'.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
```

**View:** `resources/views/emails/project-created.blade.php`

```blade
<x-emails.layout :company="$company" :logoData="$logoData ?? null" docLabel="Project Baru Dibuat" docDescription="Informasi pekerjaan untuk tim teknis.">

  <p style="color:#374151;margin:0 0 4px;font-size:14px;">
    Project berikut telah dibuat dan siap dikerjakan. Harap cek detail dan mulai persiapan teknis.
  </p>

  <x-emails.partials.transaction-table :rows="[
    ['Kode Project',    $project->code],
    ['Nama Project',    $project->project_name],
    ['Nomor SO',        $order?->code],
    ['Klien',           $order?->client?->name],
    ['Tanggal Dibuat',  $project->created_at?->translatedFormat('d M Y')],
    ['PIC',             $project->picEmployee?->name],
  ]"/>

  <x-emails.partials.cta-button :url="$actionUrl" label="Lihat Sales Order"/>

</x-emails.layout>
```

---

## Langkah 6 — Update Notification Classes

Ubah `toMail()` di kedua Notification class untuk return Mailable, bukan `MailMessage`.

**`app/Notifications/TermReadyToBillNotification.php`** — ganti method `toMail()`:

```php
use App\Mail\TermReadyToBillMail;
use Illuminate\Mail\Mailable;

public function toMail(object $notifiable): Mailable
{
    return new TermReadyToBillMail($this->term);
}
```

Hapus import `MailMessage` jika sudah tidak dipakai.

**`app/Notifications/ProjectCreatedNotification.php`** — sama:

```php
use App\Mail\ProjectCreatedMail;
use Illuminate\Mail\Mailable;

public function toMail(object $notifiable): Mailable
{
    return new ProjectCreatedMail($this->project);
}
```

---

## Langkah 7 — Registrasi Blade Component Alias

Blade component `<x-emails.layout>` dan `<x-emails.partials.transaction-table>` otomatis ter-resolve oleh Laravel dari path view — tidak perlu registrasi manual selama folder `resources/views/emails/` dan penamaan file sesuai konvensi.

Verifikasi: jalankan `php artisan view:clear` setelah membuat file baru.

---

## Langkah 8 — Cleanup Vendor Template

Setelah kedua notifikasi wajib diuji dan email terkirim dengan template baru:

```bash
rm -rf resources/views/vendor/mail
php artisan view:clear
```

> Jangan hapus sebelum pengujian selesai — rollback mudah kalau masih ada.

---

## Langkah 9 — Uji Manual

1. Pastikan ada user ber-role Finance dengan `employee_id` valid dan `ms_employee.email` valid
2. Buat SO dari Quotation yang sudah disetujui → cek inbox Finance
3. Setelah form Project tersedia, buat Project → cek inbox Teknis + PIC. Lampiran PDF Quotation masih rencana terpisah.
4. Cek tampilan di Gmail, Outlook (web), mobile

---

## Checklist

- [x] `resources/views/components/emails/layout.blade.php` — header dan footer terbaru
- [x] `resources/views/components/emails/partials/transaction-table.blade.php`
- [x] `resources/views/components/emails/partials/cta-button.blade.php`
- [x] `app/Mail/SanitraMail.php`
- [x] `app/Mail/TermReadyToBillMail.php` + `resources/views/emails/term-ready-to-bill.blade.php`
- [x] `app/Mail/ProjectCreatedMail.php` + `resources/views/emails/project-created.blade.php`
- [ ] Lampiran PDF Quotation di `ProjectCreatedMail`
- [x] Update `TermReadyToBillNotification::toMail()`
- [x] Update `ProjectCreatedNotification::toMail()`
- [ ] `php artisan view:clear`
- [ ] Uji manual kirim email kedua notifikasi
- [ ] `rm -rf resources/views/vendor/mail` + `php artisan view:clear`

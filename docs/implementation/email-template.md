# Desain Template Email Notifikasi — Sanitra

**Scope:** Semua email notifikasi Laravel yang dikirim aplikasi Sanitra — layout HTML kustom penuh, header/footer dari company profile, body tabel per notifikasi, dan lampiran PDF opsional.

**Acuan:** [`notification.md`](notification.md), [`resources/views/pdf/quotation.blade.php`](../../resources/views/pdf/quotation.blade.php)

---

## 1. Kondisi Saat Ini

Template email sekarang menggunakan **Laravel Markdown Mail** dengan vendor Blade yang sudah di-publish dan dikustomisasi. Masalah utama:

- Body hanya teks paragraf polos — tidak ada data transaksi (nomor SO, klien, nominal, dsb.)
- Layout dikendalikan oleh vendor framework, kurang fleksibel untuk HTML tabel

**Keputusan:** Ganti seluruh template dengan **HTML kustom penuh** (mirip PDF print), tidak lagi bergantung pada vendor mail template. Setelah migrasi selesai, `resources/views/vendor/mail/` bisa dihapus.

---

## 2. Arsitektur Baru

### 2.1 Pendekatan: `Mailable` Class + Blade View HTML Penuh

Setiap notifikasi memiliki satu `Mailable` class (`app/Mail/`) dan satu Blade view (`resources/views/emails/`). Notification class tetap ada sebagai router penerima — `toMail()` mendelegasikan ke Mailable.

```php
// Di Notification class
public function toMail(object $notifiable): Mailable
{
    return new TermReadyToBillMail($this->term);
}
```

### 2.2 Struktur File

```
app/Mail/
├── TermReadyToBillMail.php          ← #1
├── ProjectCreatedMail.php           ← #2
├── InvoiceCreatedMail.php           ← #3
├── PaymentRejectedMail.php          ← #4
├── QuotationApprovedMail.php        ← #5
├── SalesOrderCreatedMail.php        ← #6
└── InvoiceOverdueMail.php           ← #7

resources/views/emails/
├── layout.blade.php                 ← layout HTML bersama (header + footer)
├── partials/
│   └── transaction-table.blade.php ← tabel generik (label → nilai)
├── term-ready-to-bill.blade.php     ← #1
├── project-created.blade.php       ← #2
├── invoice-created.blade.php       ← #3
├── payment-rejected.blade.php      ← #4
├── quotation-approved.blade.php    ← #5
├── sales-order-created.blade.php   ← #6
└── invoice-overdue.blade.php       ← #7
```

---

## 3. Desain Layout (`emails/layout.blade.php`)

Layout HTML kustom penuh — konsep sama dengan PDF (`resources/views/pdf/quotation.blade.php`), disesuaikan untuk email (inline CSS, table-based, lebar 600px, kompatibel email client).

### 3.1 Data dari Company Profile

Header dan footer mengambil data dari `MsCompany` record utama (row pertama / `id = 1`). Data tersedia:

| Field | Nilai di DB |
|---|---|
| `legal_name` | PT Sanitra Sapta Utama |
| `address` | Ruko Multiguna Blok B3A No. 8H, Jl. Bintaro Utama 3A, … |
| `postal_code` | 15225 |
| `phone` | 02139701224 |
| `email` | info@sanitra.co.id |
| `website` | https://sanitra.co.id |
| `logo` | `companies/logos/01M54FZSP4NKTSQ90GH00YY9F2.png` (storage disk `public`) |

Layout memanggil `MsCompany::first()` atau menerima `$company` sebagai variable yang di-pass dari Mailable.

### 3.2 Struktur Header Email

```
┌─────────────────────────────────────────────┐
│  [LOGO]  PT Sanitra Sapta Utama             │
│          Alamat perusahaan                  │
│          Telp | Email | Website             │
│ ─────────────────────────────────────────── │  ← garis navy #1e3a8a
│  SISTEM NOTIFIKASI         [nama dokumen]   │
└─────────────────────────────────────────────┘
```

- Background putih (berbeda dari PDF yang putih juga — lebih ringan untuk email)
- Logo dari `Storage::url($company->logo)`, fallback teks jika logo null
- Nama perusahaan navy `#1e3a8a`, bold
- Detail perusahaan abu-abu kecil
- Garis divider navy tebal di bawah header
- Label dokumen (misal: "TERMIN SIAP DITAGIH") di kanan atas bagian info

### 3.3 Struktur Footer Email

```
│ ─────────────────────────────────────────── │  ← garis navy
│  PT Sanitra Sapta Utama                     │
│  Alamat | Telp | Website                    │
│  © 2026 Sanitra. All rights reserved.       │
│  Email ini dikirim otomatis oleh sistem.    │
│  Mohon tidak membalas email ini.            │
└─────────────────────────────────────────────┘
```

### 3.4 Area Body (Slot/`@yield`)

Antara header dan footer ada area konten yang di-fill oleh masing-masing view notifikasi:
- Intro text singkat (1–2 kalimat konteks)
- Tabel data transaksi
- Tombol CTA navy

---

## 4. Partial Tabel Transaksi

```blade
{{-- resources/views/emails/partials/transaction-table.blade.php --}}
{{-- $rows: array of [label, value] --}}
<table width="100%" cellpadding="0" cellspacing="0"
       style="border-collapse:collapse; margin:20px 0; font-size:13px;">
  @foreach($rows as [$label, $value])
  <tr>
    <td style="padding:9px 14px; background:#f0f4ff; border:1px solid #bfdbfe;
                width:38%; color:#6b7280; font-weight:600;">
      {{ $label }}
    </td>
    <td style="padding:9px 14px; border:1px solid #bfdbfe;
                color:#111827; font-weight:700;">
      {{ $value }}
    </td>
  </tr>
  @endforeach
</table>
```

---

## 5. Desain Body per Notifikasi

### 5.1 `TermReadyToBillNotification` (#1)

**Subjek:** `[Sanitra] Termin Siap Ditagih — {term_name} / {so_code}`
**Label dokumen di header:** TERMIN SIAP DITAGIH

**Intro:**
> Termin berikut sudah siap untuk dibuatkan invoice. Harap segera proses di sistem.

**Tabel:**

| Label | Sumber data |
|---|---|
| Nomor SO | `order.code` |
| Klien | `order.customer.name` |
| Nama Termin | `term.term_name` |
| Persentase | `term.percentage`% |
| Nilai Termin | Rp `term.amount` (format ribuan) |
| Tanggal Siap Tagih | `term.updated_at` (format d M Y) |

**Tombol:** `Buat Invoice` → `CreateOrderInvoice` URL untuk SO ini
**Lampiran PDF:** Tidak (invoice belum ada)

---

### 5.2 `ProjectCreatedNotification` (#2)

**Subjek:** `[Sanitra] Project Baru Dibuat — {project_code}`
**Label dokumen di header:** PROJECT BARU

**Intro:**
> Project berikut telah dibuat dan siap dikerjakan. Harap cek detail dan mulai persiapan teknis.

**Tabel:**

| Label | Sumber data |
|---|---|
| Kode Project | `project.code` |
| Nama Project | `project.project_name` |
| Nomor SO | `project.order.code` |
| Klien | `project.order.customer.name` |
| Tanggal Dibuat | `project.created_at` (format d M Y) |
| PIC | `project.picEmployee.name` (jika ada, else `—`) |

**Tombol:** `Lihat Sales Order` → view SO
**Lampiran PDF:** Ya — PDF Quotation terkait SO (`pdf.quotation`) agar Teknis langsung tahu scope pekerjaan

---

### 5.3 `InvoiceCreatedNotification` (#3)

**Subjek:** `[Sanitra] Invoice Baru Dibuat — {invoice_code}`
**Label dokumen di header:** INVOICE BARU

**Tabel:**

| Label | Sumber data |
|---|---|
| Nomor Invoice | `invoice.code` |
| Nomor SO | `invoice.order.code` |
| Klien | `invoice.order.customer.name` |
| Tanggal Invoice | `invoice.invoice_date` |
| Jatuh Tempo | `invoice.due_date` |
| Total | Rp `invoice.total` |

**Tombol:** `Lihat Invoice`
**Lampiran PDF:** Ya — PDF invoice (`pdf.invoice`)

---

### 5.4 `PaymentRejectedNotification` (#4)

**Subjek:** `[Sanitra] Pembayaran Ditolak — {payment_code}`
**Label dokumen di header:** PEMBAYARAN DITOLAK

**Intro (peringatan):**
> Pembayaran berikut telah ditolak. Harap tindak lanjuti dengan klien.

**Tabel:**

| Label | Sumber data |
|---|---|
| Nomor Pembayaran | `payment.code` |
| Nomor Invoice | `payment.invoice.code` |
| Klien | `payment.invoice.order.customer.name` |
| Nominal Bayar | Rp `payment.amount` |
| Tanggal Bayar | `payment.payment_date` |
| Alasan Tolak | `payment.rejection_note` (jika null → `—`) |

**Tombol:** `Lihat Invoice`
**Lampiran PDF:** Tidak

---

### 5.5 `QuotationApprovedNotification` (#5)

**Subjek:** `[Sanitra] Penawaran Disetujui — {quotation_code}`
**Label dokumen di header:** PENAWARAN DISETUJUI

**Tabel:**

| Label | Sumber data |
|---|---|
| Nomor Penawaran | `quotation.code` |
| Klien | `quotation.customer.name` |
| Total Penawaran | Rp `quotation.grand_total` |
| Tanggal Disetujui | `quotation.approved_at` |
| Disetujui Oleh | `quotation.approvedBy.name` |

**Tombol:** `Lihat Penawaran`
**Lampiran PDF:** Ya — PDF quotation (`pdf.quotation`)

---

### 5.6 `SalesOrderCreatedNotification` (#6)

**Subjek:** `[Sanitra] Sales Order Dibuat — {so_code}`
**Label dokumen di header:** SALES ORDER BARU

**Tabel:**

| Label | Sumber data |
|---|---|
| Nomor SO | `order.code` |
| Nomor Penawaran | `order.quotation.code` |
| Klien | `order.customer.name` |
| Total SO | Rp `order.grand_total` |
| Tanggal Dibuat | `order.created_at` |

**Tombol:** `Lihat Sales Order`
**Lampiran PDF:** Tidak

---

### 5.7 `InvoiceOverdueNotification` (#7 — Scheduler)

**Subjek:** `[Sanitra] Pengingat Jatuh Tempo — {invoice_code}`
**Label dokumen di header:** PENGINGAT JATUH TEMPO

**Intro (urgensi):**
> Invoice berikut akan jatuh tempo dalam {N} hari. Harap segera koordinasi dengan klien.

**Tabel:**

| Label | Sumber data |
|---|---|
| Nomor Invoice | `invoice.code` |
| Klien | `invoice.order.customer.name` |
| Total | Rp `invoice.total` |
| Jatuh Tempo | `invoice.due_date` |
| Sisa Hari | hitung dari `today()` |

**Tombol:** `Lihat Invoice`
**Lampiran PDF:** Ya — PDF invoice

---

## 6. Lampiran PDF

### 6.1 Cara Melampirkan

```php
// Contoh: app/Mail/InvoiceCreatedMail.php
public function content(): Content
{
    return new Content(view: 'emails.invoice-created');
}

public function attachments(): array
{
    $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $this->invoice]);

    return [
        Attachment::fromData(
            fn () => $pdf->output(),
            $this->invoice->code.'.pdf'
        )->withMime('application/pdf'),
    ];
}
```

### 6.2 Catatan Performa

- PDF digenerate synchronous (shared hosting, no queue) — aman untuk skala saat ini.
- dompdf (`barryvdh/laravel-dompdf`) sudah terpasang dan dipakai untuk Quotation + Invoice.
- Estimasi ukuran PDF Sanitra < 200 KB — aman untuk SMTP attachment.

---

## 7. Migrasi dari Vendor Template

Setelah semua Mailable class selesai dan teruji:

1. Hapus `resources/views/vendor/mail/` — tidak ada kode lain yang bergantung setelah migrasi
2. Hapus `resources/views/vendor/` sepenuhnya jika tidak ada published vendor view lain

---

## 8. Checklist Implementasi

### Setup Layout

- [ ] Buat `resources/views/emails/layout.blade.php` — HTML penuh, header dari `MsCompany`, footer, slot konten
- [ ] Buat `resources/views/emails/partials/transaction-table.blade.php`

### Wajib (2 notifikasi aktif)

- [ ] `TermReadyToBillMail` + `term-ready-to-bill.blade.php`
- [ ] `ProjectCreatedMail` + `project-created.blade.php` (+ attach PDF quotation)
- [ ] Update `toMail()` di `TermReadyToBillNotification` dan `ProjectCreatedNotification`

### Opsional

- [ ] `InvoiceCreatedMail` + view (+ attach PDF invoice)
- [ ] `PaymentRejectedMail` + view
- [ ] `QuotationApprovedMail` + view (+ attach PDF quotation)
- [ ] `SalesOrderCreatedMail` + view
- [ ] `InvoiceOverdueMail` + view (+ attach PDF invoice)

### Cleanup

- [ ] Hapus `resources/views/vendor/mail/` setelah semua notifikasi aktif dimigrasikan

---

## 9. Status

**31 Oktober 2026:** Spec selesai. Implementasi belum dimulai. Dua notifikasi wajib (#1, #2) menjadi prioritas pertama.

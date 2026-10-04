# Filament Features Sanitra

**Status:** Dashboard Owner, Activity Log, dan Export Excel selesai (2026-10-04); fitur lain masih planning · **Acuan:** [filament-resource.md](filament-resource.md), [architecture.md](architecture.md) · **Panel:** Filament 5.9.0

Doc ini mencatat fitur tambahan di luar resource CRUD standar — export data, activity log, dsb. Setiap fitur punya section tersendiri dengan rekomendasi package, cara integrasi, dan hal yang perlu diverifikasi sebelum implementasi.

### Implementasi saat ini

- **Dashboard Owner:** empat statistik master dihitung langsung dari database. Empat statistik transaksi dan dua grafik memakai data contoh dengan label yang jelas karena model transaksi belum tersedia.
- **Activity Log:** `spatie/laravel-activitylog` v5.1.1 terpasang dan tabel `activity_log` sudah dimigrasikan. Semua model master (termasuk Project Type, Project Workflow, dan Item Unit) mencatat perubahan domain, kecuali `User` dan `CodeSequence`. Logging dikonfigurasi lewat custom trait `LogsMasterActivity` di `app/Traits/` — bukan `LogsActivity` langsung di model. Halaman Activity Log global bersifat read-only, dengan filter tanggal, event, tipe entitas, dan pelaku. Spatie v5 menyimpan diff di `attribute_changes`.
- **Export Excel:** `pxlrbt/filament-excel` v4.1.0 terpasang pada seluruh 10 halaman daftar resource master. Tombol unduh berada di header halaman, sejajar dengan tombol Tambah; export mengikuti tabel/filternya dan menghasilkan XLSX. `MsItemUnit` tercatat di audit log, tetapi tidak memiliki resource tabel mandiri untuk tombol export.

---

## 1. Export to Excel (Data Master)

### 1.1 Tujuan

User perlu mengekspor daftar data master (Perusahaan, Karyawan, Item, dsb.) ke file Excel untuk kebutuhan laporan atau data transfer.

### 1.2 Package yang Direkomendasikan

**`pxlrbt/filament-excel`** — wrapper Filament-native di atas `maatwebsite/excel`.

Install:

```bash
composer require pxlrbt/filament-excel
```

Filament Excel secara internal mengandalkan `maatwebsite/excel` (Laravel Excel), yang akan ikut ter-install sebagai dependency.

**Status kompatibilitas:** v4.1.0 terpasang dan tombol export berfungsi di Filament 5.9.0.

```bash
composer show pxlrbt/filament-excel  # cek constraint filament/filament di bagian requires
```

Jika belum support v5, gunakan `maatwebsite/excel` langsung + Filament `Action` custom (lihat §1.5).

### 1.3 Cara Integrasi (via `pxlrbt/filament-excel`)

Tambahkan `ExportAction` ke `getHeaderActions()` di halaman List resource, berdampingan dengan `CreateAction`:

```php
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Actions\CreateAction;

protected function getHeaderActions(): array
{
    return [
        CreateAction::make(),
        ExportAction::make()
            ->label('Unduh Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->button()
            ->outlined()
            ->color('success')
            ->exports([
                ExcelExport::make()
                    ->fromTable()          // pakai kolom yang sudah didefinisikan di table
                    ->withFilename(fn ($resource) => $resource::getModelLabel() . '-' . now()->format('Ymd'))
                    ->withWriterType(\Maatwebsite\Excel\Excel::XLSX),
            ]),
    ];
}
```

`->fromTable()` auto-memetakan kolom dari definisi Table Filament — tidak perlu listing kolom ulang.

Jika ingin kolom spesifik:

```php
ExcelExport::make()
    ->withColumns([
        Column::make('code')->heading('Kode'),
        Column::make('name')->heading('Nama'),
        Column::make('active')->heading('Aktif'),
        Column::make('created_at')->heading('Dibuat Pada'),
    ])
```

### 1.4 Penempatan Export

- Export global (semua record yang terfilter) → `getHeaderActions()` di halaman List.
- Export per-baris (satu record) → `recordActions` atau custom `Action` di infolist.
- Untuk data master, cukup export global di header halaman.

### 1.5 Fallback: Manual dengan `maatwebsite/excel`

Jika `pxlrbt/filament-excel` belum support v5, integrasikan langsung:

```bash
composer require maatwebsite/excel
```

Buat `App\Exports\MsCompanyExport` extends `FromCollection` + `WithHeadings`, lalu buat Filament `Action` yang memanggil `Excel::download()`.

```php
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MsCompanyExport;

Action::make('export')
    ->label('Export Excel')
    ->icon('heroicon-o-arrow-down-tray')
    ->action(fn () => Excel::download(new MsCompanyExport, 'perusahaan.xlsx'))
```

Pendekatan ini lebih verbose tapi tidak bergantung pada plugin third-party.

### 1.6 Hal yang Perlu Diverifikasi

- [x] `pxlrbt/filament-excel` v4.1.0 berfungsi dengan Filament 5.9.x.
- [x] `maatwebsite/excel` v4.0.3 terpasang pada PHP 8.4.
- [x] Export tersedia di seluruh 10 resource master.
- [x] Format export XLSX.

---

## 2. Activity Log / Monitoring Log

### 2.1 Tujuan

Rekam siapa melakukan apa dan kapan di seluruh data domain — create, update, delete (termasuk soft delete). Log ini menjadi audit trail untuk keperluan internal dan troubleshooting.

Ini **berbeda** dari kolom `created_by`/`updated_by`/`deleted_by` di `AuditedBySoftDelete`:
- `AuditedBySoftDelete` → hanya catat siapa yang buat/ubah/hapus di tabel itu sendiri, tidak ada detail field apa yang berubah.
- Activity log → catat full diff field (sebelum/sesudah), per event, dengan timestamp, bisa di-query/filter.

### 2.2 Package: `spatie/laravel-activitylog`

```bash
composer require spatie/laravel-activitylog
```

**Kompatibilitas:**
- Laravel 13 ✓ (package v5.1.1)
- PHP 8.4 ✓

**Apa yang dilakukan:**
- Menyediakan trait `LogsActivity` untuk Eloquent model.
- Secara default merekam event `created`, `updated`, `deleted` ke tabel `activity_log`.
- Setiap entry menyimpan: `log_name`, `description`, `subject_type`, `subject_id`, `causer_type`, `causer_id` (user yang melakukan), `attribute_changes` (JSON berisi `attributes` dan `old` — nilai sebelum dan sesudah).

**Publish dan migrate:**

```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
php artisan migrate
```

### 2.3 Setup di Model

Implementasi memakai custom trait `LogsMasterActivity` di `app/Traits/LogsMasterActivity.php` — bukan `LogsActivity` langsung di model. Trait ini membungkus konfigurasi standar sekali agar semua model master konsisten:

```php
// app/Traits/LogsMasterActivity.php
namespace App\Traits;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait LogsMasterActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['id', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
```

Pemakaian di model — cukup tambahkan satu trait:

```php
use App\Traits\LogsMasterActivity;

class MsCompany extends Model
{
    use AuditedBySoftDelete, LogsMasterActivity, SoftDeletes;
}
```

`->logAll()` + `->logExcept([...])` — log semua kolom kecuali kolom audit internal dan timestamp. Lebih aman daripada `->logOnly([...])` karena tidak perlu update whitelist saat kolom baru ditambah.

`->logOnlyDirty()` + `->dontLogEmptyChanges()` — hanya buat log entry kalau ada field yang benar-benar berubah.

**Model yang memakai `LogsMasterActivity`:** MsCompany, MsEmployee, MsClient, MsItem, MsItemCategory, MsItemUnit, MsUnit, MsBankAccount, MsProjectType, MsProjectWorkflow, MsStatus.

### 2.4 Membaca Log

```php
use Spatie\Activitylog\Models\Activity;

// Semua log untuk satu record
$company = MsCompany::find(1);
$company->activitiesAsSubject;  // MorphMany ke Activity

// Log terakhir
$company->activitiesAsSubject()->latest()->first();

// Log global (semua model)
Activity::all();

// Log dengan filter
Activity::causedBy(auth()->user())
    ->forSubject($company)
    ->latest()
    ->get();

// Akses diff field
$log->attribute_changes->get('attributes');  // nilai setelah
$log->attribute_changes->get('old');         // nilai sebelum
```

### 2.5 Integrasi di Filament

Ada beberapa opsi:

#### Opsi A — Plugin `noxoua/filament-activity-log`

Plugin Filament yang sudah jadi untuk menampilkan activity log di infolist halaman View.

```bash
composer require noxoua/filament-activity-log
```

**Perlu verifikasi:** cek support Filament v5 sebelum install. Kalau belum support, pakai opsi B.

#### Opsi B — Custom RelationManager di halaman View

Buat RelationManager `ActivitiesRelationManager` di resource yang butuh audit trail, memanfaatkan relasi `activities()` dari trait `LogsActivity`:

```php
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Riwayat Aktivitas';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')->label('Event'),
                TextColumn::make('causer.name')->label('Dilakukan Oleh')->badge(),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25]);
    }
}
```

Tambahkan ke resource `getRelations()`:

```php
public static function getRelations(): array
{
    return [
        ActivitiesRelationManager::class,
    ];
}
```

#### Opsi C — Resource Activity Log Global ✓ **DIPILIH**

Buat Filament Resource tersendiri (`ActivityLogResource`) dengan model `Spatie\Activitylog\Models\Activity`. Resource ini **read-only** — hanya punya halaman List dan View, tidak ada Create/Edit/Delete.

Struktur file:

```
app/Filament/Resources/ActivityLogs/
├── ActivityLogResource.php
├── Pages/
│   ├── ListActivityLogs.php
│   └── ViewActivityLog.php
├── Tables/
│   └── ActivityLogsTable.php
└── Schemas/
    └── ActivityLogInfolist.php
```

Navigation group: `Master Data` (bareng Status) atau group baru `Sistem` sesuai kebutuhan.

**Table columns:**

| Kolom | Source | Keterangan |
| --- | --- | --- |
| Event | `description` | created / updated / deleted |
| Tipe Entitas | `subject_type` | Class model (di-strip namespace) |
| Record | `subject_id` | ID record yang diubah |
| Dilakukan Oleh | `causer.name` | User yang melakukan aksi |
| Waktu | `created_at` | Timestamp log |

**Filter table:** tanggal (range), event type, subject_type (tipe entitas), causer (user).

**View page (infolist):** tampilkan `attribute_changes` JSON — pisahkan antara `attributes` (nilai sesudah) dan `old` (nilai sebelum) agar diff mudah dibaca.

Alasan pilih Opsi C dibanding A/B:
- Opsi A (plugin) belum tentu support Filament v5.
- Opsi B (RelationManager per resource) tersebar — tidak ada satu tempat untuk lihat semua log lintas entity.
- Opsi C memberi satu halaman terpusat yang bisa difilter, cocok untuk keperluan audit dan troubleshooting.

### 2.6 Scope Implementasi yang Disarankan

Tahap ini hanya master model. Transaksi ditambahkan belakangan setelah model transaksi selesai dibuat.

| Model | Log? | Catatan |
| --- | --- | --- |
| `MsCompany` | ✓ | |
| `MsEmployee` | ✓ | |
| `MsClient` | ✓ | |
| `MsItem` | ✓ | |
| `MsItemCategory` | ✓ | |
| `MsUnit` | ✓ | |
| `MsBankAccount` | ✓ | |
| `MsStatus` | ✓ | Perubahan status config penting di-log |
| `User` | Opsional | Sensitif; kalau di-log, exclude kolom `password` |
| `CodeSequence` | ✗ | Table internal, bukan domain |
| `TransQuotationHd` | — | Belum — tambahkan setelah model transaksi selesai |
| `TransProjectHd` | — | Belum — tambahkan setelah model transaksi selesai |
| `TransInvoiceHd` | — | Belum — tambahkan setelah model transaksi selesai |
| `TransPayment` | — | Belum — tambahkan setelah model transaksi selesai |

### 2.7 Konfigurasi `config/activitylog.php`

Yang perlu disesuaikan setelah publish config:

```php
// Berapa lama log disimpan (null = selamanya)
'clean_after_days' => 365,  // berlaku saat perintah clean dijalankan

// Model activity (bisa di-extend kalau butuh custom field)
'activity_model' => \Spatie\Activitylog\Models\Activity::class,

// Auth guard
'default_auth_driver' => null,  // gunakan auth driver aktif
```

### 2.8 Hal yang Perlu Diverifikasi

- [x] `spatie/laravel-activitylog` v5.1.1 berfungsi dengan Laravel 13.
- [ ] Cek apakah plugin `noxoua/filament-activity-log` support Filament 5 — kalau tidak, gunakan opsi B (RelationManager custom).
- [ ] Tentukan apakah log perlu retention policy (hapus log > N hari) atau disimpan selamanya. Untuk satu tahun, tabel `activity_log` bisa punya ratusan ribu baris — pertimbangkan index dan cleanup scheduler.
- [x] Kolom yang di-log sudah dikonfigurasi: `logAll()` + `logExcept(['id', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'])`. Kolom sensitif (password, token) tidak masuk karena `User` tidak di-log.
- [x] `LogsMasterActivity` (membungkus `LogsActivity`) tidak konflik dengan `AuditedBySoftDelete` — keduanya aktif bersamaan karena bekerja di event yang berbeda (spatie via observer, auditedby via model events `saving`/`deleting`).

---

## 3. Dashboard

### 3.1 Scope Dashboard

Ada 4 dashboard yang direncanakan berdasarkan role pengguna:

| Dashboard | Target User | Status |
| --- | --- | --- |
| **Owner / All** | Owner / Super Admin | **Selesai (master live, transaksi dummy)** |
| Marketing | Tim penjualan & quotation | Tunggu model transaksi |
| Project | Tim pelaksana project | Tunggu model transaksi |
| Finance | Tim keuangan & invoice | Tunggu model transaksi |

Fokus saat ini: **Owner dashboard**. Widget yang membutuhkan data transaksi pakai **dummy/static data** dulu — akan di-update saat model transaksi selesai.

### 3.2 Widget Owner Dashboard

#### Stats Overview (angka ringkasan)

| Widget | Data sumber | Dummy? |
| --- | --- | --- |
| Total Perusahaan | `MsCompany::count()` | Tidak |
| Total Karyawan | `MsEmployee::count()` | Tidak |
| Total Klien | `MsClient::count()` | Tidak |
| Total Item | `MsItem::count()` | Tidak |
| Quotation Bulan Ini | `TransQuotationHd` | **Dummy** |
| Project Aktif | `TransProjectHd` | **Dummy** |
| Invoice Outstanding | `TransInvoiceHd` | **Dummy** |
| Revenue Bulan Ini | `TransInvoiceHd` | **Dummy** |

#### Chart Widget

| Widget | Tipe | Data sumber | Dummy? |
| --- | --- | --- | --- |
| Quotation per Bulan (12 bulan terakhir) | Bar/Line | `TransQuotationHd` | **Dummy** |
| Status Project | Doughnut | `TransProjectHd` | **Dummy** |

### 3.3 Implementasi di Filament

Filament menyediakan dua widget bawaan yang langsung bisa dipakai:

**`StatsOverviewWidget`** — untuk stats angka:

```php
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\MsCompany;

class OwnerStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Perusahaan', MsCompany::count())
                ->icon('heroicon-o-building-office-2')
                ->color('success'),

            Stat::make('Total Karyawan', MsEmployee::count())
                ->icon('heroicon-o-identification')
                ->color('info'),

            Stat::make('Total Klien', MsClient::count())
                ->icon('heroicon-o-user-circle')
                ->color('warning'),

            // Dummy — ganti saat model transaksi ada
            Stat::make('Project Aktif', 12)
                ->icon('heroicon-o-briefcase')
                ->color('primary'),

            Stat::make('Invoice Outstanding', 'Rp 245.000.000')
                ->icon('heroicon-o-document-currency-dollar')
                ->color('danger'),
        ];
    }
}
```

**`ChartWidget`** — untuk grafik:

```php
use Filament\Widgets\ChartWidget;

class QuotationChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Quotation per Bulan';

    protected function getData(): array
    {
        // Dummy data — ganti saat TransQuotationHd ada
        return [
            'datasets' => [
                [
                    'label' => 'Quotation',
                    'data' => [3, 7, 5, 9, 4, 6, 8, 11, 7, 5, 9, 12],
                ],
            ],
            'labels' => ['Nov', 'Des', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
```

### 3.4 Registrasi Widget di Panel

Widget didaftarkan di `DashboardPanelProvider` atau auto-discover via `discoverWidgets()` (sudah aktif). Urutan tampil dikontrol via `$sort`:

```php
protected static ?int $sort = 1;  // di class widget
```

### 3.5 Hal yang Perlu Diputuskan

- [ ] Layout widget owner: berapa kolom? (default Filament 2 kolom)
- [ ] Widget mana yang tampil first — stats dulu atau chart dulu?
- [ ] Dummy data chart: pakai angka statis atau generate random yang masuk akal?
- [ ] Apakah widget owner bisa dilihat semua role, atau dibatasi hanya owner/admin?

---

## 4. PDF Export

**Status: Dicatat, belum dikerjakan.**

Digunakan untuk mencetak dokumen final yang tidak boleh diedit — invoice resmi, surat penawaran, receipt pembayaran.

**Library yang direkomendasikan:** `barryvdh/laravel-dompdf` atau `spatie/laravel-pdf`

- `barryvdh/laravel-dompdf` — render HTML/CSS ke PDF, ringan, tidak butuh dependency eksternal.
- `spatie/laravel-pdf` — wrapper atas headless Chromium, hasil lebih akurat untuk layout kompleks, tapi butuh Chromium ter-install di server.

**Pendekatan:** buat Blade view sebagai template PDF, query data dari model, render via `PDF::loadView('pdf.invoice', $data)->download()`.

**Trigger implementasi:** setelah model transaksi (`TransInvoiceHd`, `TransQuotationHd`) selesai dibuat.

---

## 5. Notifikasi Email

**Status: Dicatat, belum dikerjakan.**

Notifikasi email dikirim otomatis pada event bisnis tertentu. Implementasi memakai **Laravel Notification** (bukan Mail langsung) agar mudah diperluas ke channel lain (database, Slack) di masa depan.

### 5.1 Daftar Trigger yang Direncanakan

| Event | Penerima | Keterangan |
| --- | --- | --- |
| Quotation disetujui | Owner / PIC Project | Notif bahwa QUO siap dikonversi ke project |
| Quotation ditolak | Sales / Pembuat QUO | Notif agar bisa direvisi |
| Invoice diterbitkan | Klien / Finance | Invoice resmi siap dibayar |
| Invoice hampir jatuh tempo | Finance | Reminder H-3 atau H-7 |
| Payment dikonfirmasi | Finance / Owner | Notif pembayaran masuk |
| Term ready to bill | Finance | Notif bahwa termin siap diinvoice |

### 5.2 Struktur Notification

```php
// app/Notifications/QuotationApprovedNotification.php
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class QuotationApprovedNotification extends Notification
{
    public function __construct(public TransQuotationHd $quotation) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];  // email + in-app
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Quotation ' . $this->quotation->code . ' Disetujui')
            ->line('Quotation untuk ' . $this->quotation->client->name . ' telah disetujui.')
            ->action('Lihat Quotation', route('filament.dashboard.resources.quotations.view', $this->quotation));
    }
}
```

### 5.3 Hal yang Perlu Diputuskan

- [ ] SMTP provider: Mailtrap (dev), Mailgun/SES (prod)?
- [ ] Apakah notifikasi juga masuk in-app (database channel) atau email saja?
- [ ] Siapa penerima tiap event — berdasarkan role Spatie atau field tertentu di model?
- [ ] Apakah ada notifikasi untuk master data (misal karyawan baru dibuat)? Kemungkinan tidak perlu.

**Trigger implementasi:** setelah model transaksi dan Spatie Permission selesai di-setup.

---

## 6. Urutan Implementasi

| Urutan | Fitur | Prasyarat |
| --- | --- | --- |
| 1 | **Dashboard (Owner)** | Tidak ada — master model sudah ada, dummy untuk transaksi |
| 2 | **Activity Log** | Install `spatie/laravel-activitylog`, migrate |
| 3 | **Export Excel** | Install `pxlrbt/filament-excel` |
| 4 | **PDF Export** | Model transaksi selesai |
| 5 | **Notifikasi Email** | Model transaksi + Spatie Permission selesai |

Fitur 1–3 bisa dikerjakan sekarang tanpa menunggu transaksi. Fitur 4–5 tunggu setelah layer transaksi selesai.

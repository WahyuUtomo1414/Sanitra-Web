# Error Log Aplikasi Sanitra

**Status:** Rancangan · **Acuan:** [architecture.md](architecture.md), [filament-features.md](filament-features.md) · **Panel:** Filament 5.9.0

Dokumen ini merancang sistem pencatatan error aplikasi ke database agar dapat dipantau langsung dari panel Filament. Berbeda dari `activity_log` yang mencatat perubahan data domain, error log mencatat exception tak tertangani yang terjadi di sisi server.

## 1. Tujuan

- Menampilkan error production di panel tanpa harus akses server atau baca file log.
- Memudahkan debugging: siapa yang login, URL apa yang dipanggil, dan stack trace-nya.
- Menyediakan riwayat error yang bisa difilter per level, tanggal, atau user.

## 2. Schema Tabel `sys_error_log`

Tabel ini bersifat **append-only** — tidak ada update atau delete dari aplikasi. Prefix `sys_` karena ini infrastruktur sistem, bukan domain bisnis (sama seperti `sys_code_sequence` dan `sys_import_log`).

| Kolom | Tipe | Null | Keterangan |
| --- | --- | --- | --- |
| `id` | bigint unsigned | tidak | Primary key. |
| `level` | varchar(20) | tidak | Tingkat keparahan: `error`, `critical`, `warning`. |
| `message` | text | tidak | Pesan exception. |
| `url` | varchar(500) | ya | URL request yang memicu error; null untuk error dari console/scheduler. |
| `method` | varchar(10) | ya | HTTP method: GET, POST, PUT, DELETE, dll. |
| `user_id` | bigint unsigned | ya | FK ke `users.id`; null jika belum login atau error dari console. |
| `ip_address` | varchar(45) | ya | IP address pemanggil; null untuk error dari console. |
| `trace` | text | ya | Stack trace; dipotong di 5.000 karakter. |
| `context` | json | ya | Data tambahan: class exception, kode HTTP, dsb. |
| `created_at` | timestamp | tidak | Waktu error terjadi. |

Tidak ada `updated_at`, `deleted_at`, `active`, atau kolom audit `created_by`/`updated_by`/`deleted_by` — tabel ini tidak memakai trait `MasterTableColumns` maupun `AuditedBySoftDelete`.

### 2.1 Migration

```php
Schema::create('sys_error_log', function (Blueprint $table) {
    $table->id();
    $table->string('level', 20);
    $table->text('message');
    $table->string('url', 500)->nullable();
    $table->string('method', 10)->nullable();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('ip_address', 45)->nullable();
    $table->text('trace')->nullable();
    $table->json('context')->nullable();
    $table->timestamp('created_at');

    $table->index(['level', 'created_at']);
    $table->index('created_at');
    $table->index('user_id');
});
```

Index gabungan `(level, created_at)` mempercepat filter yang paling sering dipakai di panel.

### 2.2 Model `SysErrorLog`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SysErrorLog extends Model
{
    protected $table = 'sys_error_log';

    protected $guarded = ['id'];

    public $timestamps = false; // hanya pakai created_at, bukan updated_at

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Model tidak memakai `SoftDeletes` dan tidak memakai `AuditedBySoftDelete` — log tidak boleh diedit atau dihapus lewat aplikasi.

## 3. Exception Handler

Exception dicatat di `app/Exceptions/Handler.php` lewat method `register()`. Penulisan ke database dibungkus `try-catch` tersendiri agar kegagalan menulis log (misalnya DB sedang down) tidak menyebabkan error baru.

### 3.1 Exception yang Di-skip

Exception berikut **tidak** dicatat karena bukan error aplikasi:

| Exception | Alasan |
| --- | --- |
| `ValidationException` | Error validasi form — diharapkan terjadi. |
| `AuthenticationException` | User belum login — bukan error. |
| `AuthorizationException` | User tidak punya izin — bukan error. |
| `ModelNotFoundException` | Record tidak ditemukan (404) — bukan error. |
| `NotFoundHttpException` | URL tidak ditemukan (404) — bukan error. |

### 3.2 Implementasi Handler

```php
use App\Models\SysErrorLog;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

public function register(): void
{
    $this->reportable(function (Throwable $e) {
        $skipExceptions = [
            ValidationException::class,
            AuthenticationException::class,
            AuthorizationException::class,
            ModelNotFoundException::class,
            NotFoundHttpException::class,
        ];

        foreach ($skipExceptions as $skipped) {
            if ($e instanceof $skipped) {
                return false;
            }
        }

        try {
            $trace = $e->getTraceAsString();

            SysErrorLog::create([
                'level' => 'error',
                'message' => $e->getMessage(),
                'url' => request()->fullUrl() ?: null,
                'method' => request()->method() ?: null,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip() ?: null,
                'trace' => mb_substr($trace, 0, 5000),
                'context' => [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'http_code' => method_exists($e, 'getStatusCode')
                        ? $e->getStatusCode()
                        : null,
                ],
            ]);
        } catch (Throwable) {
            // Jangan throw lagi — logging gagal tidak boleh menutupi error asli.
        }
    });
}
```

Catatan:
- `mb_substr($trace, 0, 5000)` — stack trace bisa sangat panjang; dipotong agar tabel tidak bengkak.
- `auth()->id()` mengembalikan null jika user belum login; aman.
- `request()->fullUrl()` bisa kosong saat error terjadi di console/scheduler; `?: null` mengatasinya.
- `return false` di dalam `reportable` mencegah exception tersebut diteruskan ke logger default Laravel (opsional — hapus baris ini jika ingin tetap masuk `laravel.log` juga).

## 4. Filament Resource

Resource ini **read-only** — tidak ada halaman Create, Edit, atau Delete. Hanya List dan View. Polanya sama dengan `ActivityLogResource` yang sudah ada.

### 4.1 Identitas Resource

| Properti | Nilai |
| --- | --- |
| Navigation label | `Log Error` |
| Model label | `Log Error` |
| Plural model label | `Log Error` |
| Slug | `error-logs` |
| Navigation group | `Master Data` |
| Navigation icon | `heroicon-o-bug-ant` |

### 4.2 Struktur File

```
app/Filament/Resources/SysErrorLog/
├── SysErrorLogResource.php
├── Pages/
│   ├── ListSysErrorLogs.php
│   └── ViewSysErrorLog.php
├── Tables/
│   └── SysErrorLogsTable.php
└── Schemas/
    └── SysErrorLogInfolist.php
```

### 4.3 Kolom Tabel

| Kolom | Source | Keterangan |
| --- | --- | --- |
| Level | `level` | Badge berwarna: `error` merah, `critical` merah gelap, `warning` kuning. |
| Pesan | `message` | Dipotong di 80 karakter; teks penuh di halaman View. |
| URL | `url` | Tampilkan path saja (bukan full URL) agar kolom tidak terlalu lebar. |
| User | `user.name` | Badge; null ditampilkan sebagai "Sistem/Console". |
| Waktu | `created_at` | Format `d M Y H:i`; sortable descending by default. |

### 4.4 Filter Tabel

- **Level** — select: `error`, `critical`, `warning`.
- **Tanggal** — date range picker pada kolom `created_at`.
- **User** — select relasi ke `User`.

### 4.5 Halaman View (Infolist)

Section `Detail Error`:

```
Level          | Waktu
Pesan (full)   [columnSpanFull]
URL            | Method
User           | IP Address
Exception Class| File : Baris
Stack Trace    [columnSpanFull — monospace, pre-wrap]
Context JSON   [columnSpanFull]
```

Stack trace ditampilkan dengan font monospace dan `white-space: pre-wrap` agar mudah dibaca.

## 5. Cleanup Log Lama

Log error dibersihkan otomatis agar tabel tidak terus membesar. Jalankan via Laravel Scheduler yang sudah dikonfigurasi lewat cron di server.

### 5.1 Artisan Command

```php
// app/Console/Commands/PruneErrorLogs.php

use App\Models\SysErrorLog;
use Illuminate\Console\Command;

class PruneErrorLogs extends Command
{
    protected $signature = 'error-log:prune {--days=60 : Hapus log lebih tua dari N hari}';

    protected $description = 'Hapus error log yang lebih tua dari batas hari yang ditentukan';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $deleted = SysErrorLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Deleted {$deleted} error log entries older than {$days} days.");

        return self::SUCCESS;
    }
}
```

### 5.2 Registrasi di Scheduler

Di `routes/console.php`:

```php
Schedule::command('error-log:prune --days=60')->weekly();
```

Jalankan mingguan cukup untuk volume aplikasi internal. Ubah `--days=60` sesuai kebutuhan retensi.

## 6. Hal yang Perlu Diputuskan Sebelum Implement

- [ ] Berapa lama retensi log? Default rancangan ini 60 hari — sesuaikan jika perlu lebih panjang.
- [ ] Apakah error dari console/scheduler juga perlu dicatat? Saat ini handler menangkapnya (url dan user_id akan null).
- [ ] Apakah `return false` di handler dibiarkan (log tidak masuk `laravel.log`) atau dihapus (log masuk ke dua tempat)?

## 7. Urutan Implementasi

1. Buat migration `sys_error_log`.
2. Buat model `SysErrorLog`.
3. Update `app/Exceptions/Handler.php`.
4. Buat `PruneErrorLogs` command dan daftarkan di scheduler.
5. Buat `SysErrorLogResource` (read-only: List + View).
6. Jalankan migration.
7. Verifikasi: trigger error sengaja, pastikan row masuk ke tabel dan tampil di panel.

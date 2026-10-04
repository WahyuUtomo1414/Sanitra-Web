# Arsitektur Aplikasi Sanitra

**Status:** Implemented (core) · **Acuan:** [database.md](database.md), [filament-resource.md](filament-resource.md), [filament-lifecycle-hook.md](filament-lifecycle-hook.md), [filament-features.md](filament-features.md) · **Framework:** Laravel 13 + Filament 5.9.0

## 1. Tujuan

Dokumen ini menetapkan arsitektur level aplikasi untuk Sanitra:

- Pemisahan tanggung jawab antar layer (Model, Service, Filament, Enum, Support).
- Konvensi enum: kapan dibuat, cara cast di model, daftar enum di proyek.
- Konvensi auto-generate kode (`AutoCode`): format, enum prefix, service, sequence table.
- Peta direktori dan penunjuk ke doc lain untuk detail per layer.

Doc ini **tidak** menggantikan:

- `database.md` — detail schema per tabel.
- `filament-resource.md` — konvensi resource/form/table.
- `filament-lifecycle-hook.md` — service pattern + lifecycle hook.
- `filament-custom.md` — tema panel Filament.
- `filament-features.md` — fitur tambahan: export Excel, activity log.

## 2. Overview Arsitektur

### 2.1 Layer

```
┌──────────────────────────────────────────────────────────────────┐
│                      Filament Panel (Admin UI)                   │
│  Resource · Form · Table · Infolist · Page · Action · Hook       │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                           Service                                │
│  Business logic + DB::transaction + Notification trigger         │
│  Group per domain: Quotation, Project, Invoice, Payment, Shared  │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                      Model (Eloquent)                            │
│  Trait: AuditedBySoftDelete, SoftDeletes                         │
│  Relation · Cast · Scope                                         │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                         Database                                 │
└──────────────────────────────────────────────────────────────────┘

┌────────────┐    ┌────────────┐    ┌──────────────────┐
│    Enum    │    │   Support  │    │   Notification   │
│ (nilai     │    │ (helper,   │    │ (Mail, Database) │
│  tetap +   │    │  formatter)│    │                  │
│  metadata) │    │            │    │                  │
└────────────┘    └────────────┘    └──────────────────┘
       ↑                 ↑                   ↑
       └── dipakai lintas layer ─────────────┘
```

### 2.2 Alur create transaksi (contoh: Create Project)

```
┌─────────────┐
│  User klik  │
│  "Simpan"   │
└──────┬──────┘
       ▼
┌────────────────────────────────────────┐
│ CreateTransProjectHd (Filament Page)   │
│ ├─ mutateFormDataBeforeCreate()        │
│ │     └─ GenerateCodeService::next()   │  ← ambil kode dari AutoCode
│ └─ handleRecordCreation(array $data)   │
│       └─ DB::transaction(fn () => ...  │
│           ├─ TransProjectHd::create()  │
│           └─ CreateProjectStagesService│  ← bikin DT dari workflow
└────────────────────────────────────────┘
```

Detail service pattern & hook di [`filament-lifecycle-hook.md`](filament-lifecycle-hook.md).

## 3. Struktur Direktori

```
app/
├── Enums/                          # Enum nilai tetap + metadata (§5)
│   ├── AutoCodePrefix.php
│   ├── CodeFormat.php
│   ├── StatusType.php
│   └── VatType.php
│
├── Filament/                       # Panel admin (lihat filament-resource.md)
│   └── Resources/
│       ├── MsCompany/
│       ├── TransQuotationHd/
│       └── ...
│
├── Models/                         # Eloquent model (lihat database.md §8)
│   ├── User.php
│   ├── MsCompany.php
│   ├── TransQuotationHd.php
│   └── ...
│
├── Notifications/                  # Email/notif ke user
│   ├── QuotationApprovedNotification.php
│   ├── TermReadyToBillNotification.php
│   └── ...
│
├── Observers/                      # HANYA invariant guard (bukan business logic)
│   └── TransProjectMonitoringObserver.php
│
├── Services/                       # Business logic (§2.1 layer Service)
│   ├── Shared/
│   │   └── GenerateCodeService.php  # AutoCode generator (§6)
│   ├── Quotation/
│   ├── Project/
│   ├── Invoice/
│   └── Payment/
│
├── Support/                        # Helper umum, formatter, dll.
│   └── (TBD saat kebutuhan muncul)
│
└── Traits/                         # Trait reusable (model + migration)
    ├── AuditedBySoftDelete.php
    ├── MasterTableColumns.php
    └── TransactionTableColumns.php

database/
├── migrations/
└── seeders/
```

## 4. Pembagian Tanggung Jawab per Layer

| Layer | Tugas | Boleh | Tidak boleh |
| --- | --- | --- | --- |
| **Filament Resource** | UI: form, table, infolist, action | Panggil service di hook | Business logic di dalam form/table schema |
| **Service** | Orkestrasi + DB transaction | Panggil service lain, model, notification | Tahu tentang Filament (request, response, UI) |
| **Model** | Relation, cast, scope sederhana | Trait reusable, invariant via observer | Business logic multi-step |
| **Enum** | Nilai tetap + metadata | Helper method sederhana (misal `format()`) | Side effect (query DB, panggil service) |
| **Support** | Pure helper (formatter angka, tanggal, dll.) | Static method, stateless | Simpan state, akses DB |
| **Notification** | Format email/notif | Lookup data via relation | Business logic (hanya format pesan) |
| **Observer** | Guard invariant (reject update/delete log) | Throw exception | Business logic multi-step |

Detail service pattern lihat [`filament-lifecycle-hook.md`](filament-lifecycle-hook.md).

## 5. Konvensi Enum

### 5.1 Prinsip

- Pakai **PHP 8.1+ backed enum** (`string` atau `int` backed), bukan class dengan const.
- Enum di namespace `App\Enums`.
- **Satu enum = satu konsep.** Jangan jadiin "big enum" yang campur-campur domain.
- Enum boleh punya helper method ringan (misal `label()`, `format()`), tapi **tidak boleh** akses DB atau panggil service.
- Nama case pakai `PascalCase`, nilai (`value`) pakai `snake_case` atau abbreviation jelas.

### 5.2 Cast di Model

```php
protected function casts(): array
{
    return [
        'active' => 'boolean',
        'vat_type' => VatType::class,  // auto-cast ke enum
    ];
}
```

Setelah di-cast, `$quotation->vat_type` return instance `VatType`, bukan string. Comparison pakai `===`:

```php
if ($quotation->vat_type === VatType::Include) {
    // ...
}
```

### 5.3 Daftar Enum di Proyek

#### 5.3.1 `StatusType`

Mapping `ms_status.type`. Dipakai untuk filter dropdown status per entity.

```php
namespace App\Enums;

enum StatusType: string
{
    case Quotation = 'quotation';
    case Project = 'project';
    case ProjectDt = 'project_dt';
    case Term = 'term';
    case Invoice = 'invoice';
    case Payment = 'payment';
}
```

Pemakaian di Filament select status untuk quotation:

```php
Select::make('status_id')
    ->relationship(
        name: 'status',
        titleAttribute: 'name',
        modifyQueryUsing: fn ($query) => $query->where('type', StatusType::Quotation->value),
    );
```

**Catatan:** kolom `ms_status.type` tetap `varchar(128)` di database (lihat `database.md` §4.12). Enum ini di PHP side saja — mencegah typo di kode aplikasi, bukan constraint database.

#### 5.3.2 `VatType`

Jenis PPN di `trans_quotation_hd.vat_type` dan `trans_invoice_hd.vat_type`.

```php
namespace App\Enums;

enum VatType: string
{
    case None = 'none';
    case Include = 'include';
    case Exclude = 'exclude';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Tanpa PPN',
            self::Include => 'PPN Include',
            self::Exclude => 'PPN Exclude',
        };
    }
}
```

Pemakaian di Filament select:

```php
Select::make('vat_type')
    ->options(collect(VatType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]));
```

Cast di model `TransQuotationHd` dan `TransInvoiceHd`:

```php
protected function casts(): array
{
    return [
        'vat_type' => VatType::class,
    ];
}
```

#### 5.3.3 `CodeFormat`

Metadata untuk AutoCode — menentukan format kode (master vs transaksi). Dipakai internal oleh `AutoCodePrefix` dan `GenerateCodeService`.

```php
namespace App\Enums;

enum CodeFormat
{
    case Master;       // PREFIX + 4 digit running, no reset
    case Transaction;  // PREFIX/YY/MM/4 digit running, reset per bulan
}
```

#### 5.3.4 `AutoCodePrefix`

Mapping entity → prefix string + format type. Dipakai `GenerateCodeService` untuk tahu prefix dan format kode per entity. Detail di §6.

```php
namespace App\Enums;

enum AutoCodePrefix: string
{
    // Master (format: PREFIX + 4 digit, no reset)
    case MsCompany = 'COM';
    case MsEmployee = 'EMP';
    case MsClient = 'CLI';
    case MsProjectType = 'PTY';
    case MsItemCategory = 'IC';
    case MsItem = 'ITM';
    case MsBankAccount = 'BNK';

    // Transaksi (format: PREFIX/YY/MM/4 digit, reset per bulan)
    case TransQuotationHd = 'QUO';
    case TransProjectHd = 'PRO';
    case TransInvoiceHd = 'INV';
    case TransPayment = 'PAY';

    public function format(): CodeFormat
    {
        return match (true) {
            str_starts_with($this->name, 'Trans') => CodeFormat::Transaction,
            default => CodeFormat::Master,
        };
    }
}
```

**Entity yang TIDAK pakai AutoCode:**

| Entity | Alasan |
| --- | --- |
| `MsUnit` | Kode diisi manual dengan singkatan bermakna (`KG`, `PCS`, `LTR`, `M2`, `DUS`). Enum tidak boleh ada case untuk ini. |
| `MsProjectWorkflow` | Tidak punya kolom `code` di schema (lihat `database.md` §4.6). |
| `MsStatus` | Kolom `code` berisi status code stabil (`draft`, `approved`, `invoiced`) — manual, dipakai di logika aplikasi. |

#### 5.3.5 Status Enums (per entity type)

Semua kode status di `ms_status.code` punya enum sendiri di `App\Enums\Status`. Dipakai untuk comparison & query yang type-safe (hindari magic string).

Pattern setiap enum:

- Case value = snake_case string (sama persis dengan `ms_status.code`).
- Static method `type(): StatusType` → return `StatusType` yang matching (dipakai saat query `MsStatus`).
- Instance method `label(): string` → label bahasa Indonesia untuk UI.
- Instance method `color(): string` → warna semantik Filament (`gray`, `info`, `warning`, `success`, `danger`) untuk badge.

**Daftar enum (6 file di `app/Enums/Status/`, total 25 case — sesuai SRS §6.1):**

| Enum | Case | Jumlah |
| --- | --- | --- |
| `QuotationStatus` | `draft`, `sent`, `approved`, `rejected`, `revised` | 5 |
| `ProjectStatus` | `ongoing`, `on_hold`, `closed`, `cancelled` | 4 |
| `ProjectDtStatus` | `pending`, `in_progress`, `completed` | 3 |
| `TermStatus` | `not_ready`, `ready_to_bill`, `invoiced`, `paid` | 4 |
| `InvoiceStatus` | `draft`, `issued`, `partially_paid`, `paid`, `void`, `overdue` | 6 |
| `PaymentStatus` | `pending`, `confirmed`, `rejected` | 3 |

**Catatan verifikasi:** daftar case di atas berdasarkan interpretasi alur bisnis di PRD. Verifikasi final terhadap SRS §6.1 saat PDF tersedia — kalau ada beda nama atau tambahan status, update enum + seeder `MsStatus` sekaligus.

**Pemakaian di service:**

```php
use App\Enums\Status\QuotationStatus;
use App\Models\MsStatus;

$approvedStatus = MsStatus::where('type', QuotationStatus::type()->value)
    ->where('code', QuotationStatus::Approved->value)
    ->firstOrFail();

if ($quotation->status->code === QuotationStatus::Approved->value) {
    // ...
}
```

**Pemakaian di Filament table/infolist (badge):**

```php
Tables\Columns\TextColumn::make('status.code')
    ->badge()
    ->formatStateUsing(fn (string $state) => QuotationStatus::from($state)->label())
    ->color(fn (string $state) => QuotationStatus::from($state)->color());
```

**Seeder `MsStatus`** sudah dibuat di `SanitraMasterSeeder` — loop seluruh case dari 6 enum ini menghasilkan 25 row. Enum = sumber kebenaran untuk seed, tidak ada drift antara code dan DB.

#### 5.3.6 `UnitType`

Jenis satuan di `ms_unit.type`. Dipakai untuk membedakan unit waktu (jam, hari, bulan) dari unit barang (kg, liter, unit, paket).

```php
namespace App\Enums;

enum UnitType: string
{
    case Waktu = 'waktu';
    case Barang = 'barang';

    public function label(): string
    {
        return match ($this) {
            self::Waktu => 'Waktu',
            self::Barang => 'Barang',
        };
    }

    public static function options(): array
    {
        return [
            self::Waktu->value => self::Waktu->label(),
            self::Barang->value => self::Barang->label(),
        ];
    }
}
```

Cast di model `MsUnit`:

```php
protected function casts(): array
{
    return ['type' => UnitType::class];
}
```

Pemakaian di Filament select:

```php
Select::make('type')
    ->options(UnitType::options());
```

### 5.4 Enum untuk Ditambahkan Nanti

Ditangguhkan sampai implementasi transaksi:

- `PaymentMethod` — metode pembayaran di `trans_payment` (misal `cash`, `transfer`, `giro`).
- `LogType` — jenis log di `trans_project_monitoring` (misal `status_change`, `override`, `rework`).

Konsep sama dengan `VatType`: backed enum, label method, cast di model.

## 6. AutoCode — Generator Kode Otomatis

### 6.1 Format

| Jenis | Pattern | Contoh | Reset |
| --- | --- | --- | --- |
| Master | `PREFIX` + 4 digit | `IC0001`, `PTY0002`, `COM0015` | Tidak — running terus |
| Transaksi | `PREFIX/YY/MM/` + 4 digit | `QUO/26/10/0001`, `PRO/26/10/0002`, `INV/26/11/0001` | Per bulan |

Digit padding:

- Master: `0001` sampai `9999` (max 9999 record per entity sebelum perlu nambah digit — cukup untuk horizon menengah; kalau mendekati, naikin digit lewat migrasi + update service).
- Transaksi: `0001` sampai `9999` per (prefix, tahun, bulan). Reset tiap awal bulan.

Tahun pakai 2 digit terakhir (`2026` → `26`), bulan pakai 2 digit (`10`, `01`).

### 6.2 Rules & Scope

- Kode **tidak boleh diedit user** setelah dibuat. Field `code` di form Filament selalu `disabled`.
- Kode **tidak di-generate di model event** (observer/boot). Dibuat di Filament hook `mutateFormDataBeforeCreate` lewat service.
- Running number dibaca & di-increment **secara atomic** via sequence table (§6.5), bukan `MAX(code) + 1` — mencegah race condition.
- Jika transaksi dibuat di awal bulan baru, service otomatis create row sequence baru untuk bulan tersebut dengan `last_number = 1`.

### 6.3 Service `GenerateCodeService`

Lokasi: `app/Services/Shared/GenerateCodeService.php`.

Entry method: `next(AutoCodePrefix $prefix): string`.

Pseudocode:

```php
class GenerateCodeService
{
    public function next(AutoCodePrefix $prefix): string
    {
        return DB::transaction(function () use ($prefix) {
            $format = $prefix->format();

            [$year, $month] = $format === CodeFormat::Transaction
                ? [(int) now()->format('Y'), (int) now()->format('n')]
                : [null, null];

            // Lock row untuk atomic increment
            $sequence = CodeSequence::where('prefix', $prefix->value)
                ->where('year', $year)
                ->where('month', $month)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = CodeSequence::create([
                    'prefix' => $prefix->value,
                    'year' => $year,
                    'month' => $month,
                    'last_number' => 0,
                ]);
            }

            $sequence->increment('last_number');

            return match ($format) {
                CodeFormat::Master => sprintf(
                    '%s%04d',
                    $prefix->value,
                    $sequence->last_number,
                ),
                CodeFormat::Transaction => sprintf(
                    '%s/%s/%02d/%04d',
                    $prefix->value,
                    substr((string) $year, -2),
                    $month,
                    $sequence->last_number,
                ),
            };
        });
    }
}
```

Catatan:

- `DB::transaction()` wajib — lock dan increment harus dalam transaction yang sama.
- `lockForUpdate()` pakai `SELECT ... FOR UPDATE` → request lain yang baca row sama akan antre sampai transaction commit.
- `firstOrCreate` style di atas dipecah manual karena combo dengan `lockForUpdate` lebih reliable; `firstOrCreate` + `lockForUpdate` kadang gak kombinasi mulus di beberapa DB driver.

### 6.4 Sequence Table (`sys_code_sequence`)

Table dedikasi untuk running number per (prefix, year, month).

**Schema (migration terpisah):**

```php
Schema::create('sys_code_sequence', function (Blueprint $table) {
    $table->id();
    $table->string('prefix', 10);
    $table->unsignedSmallInteger('year')->nullable();   // NULL untuk master
    $table->unsignedTinyInteger('month')->nullable();   // NULL untuk master
    $table->unsignedInteger('last_number')->default(0);
    $table->timestamps();

    $table->unique(['prefix', 'year', 'month'], 'uniq_prefix_period');
});
```

**Model `App\Models\CodeSequence`:**

```php
class CodeSequence extends Model
{
    protected $table = 'sys_code_sequence';
    protected $guarded = ['id'];

    // Tidak pakai AuditedBySoftDelete — table internal, bukan domain.
    // Tidak pakai SoftDeletes — row tidak pernah dihapus.
}
```

**Contoh isi tabel:**

```
id | prefix | year | month | last_number | updated_at
---+--------+------+-------+-------------+-----------
1  | IC     | NULL | NULL  |  2          | 2026-10-03 14:00
2  | COM    | NULL | NULL  |  5          | 2026-10-03 14:05
3  | QUO    | 2026 | 10    | 15          | 2026-10-03 14:10
4  | PRO    | 2026 | 10    |  3          | 2026-10-03 14:12
5  | QUO    | 2026 | 11    |  1          | 2026-11-01 09:00
```

Row 5 dibuat otomatis saat quotation pertama di bulan November 2026.

### 6.5 Integrasi di Filament

**Create page (contoh `CreateMsItemCategory`):**

```php
use App\Enums\AutoCodePrefix;
use App\Services\Shared\GenerateCodeService;

class CreateMsItemCategory extends CreateRecord
{
    protected static string $resource = MsItemCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = app(GenerateCodeService::class)
            ->next(AutoCodePrefix::MsItemCategory);

        return $data;
    }
}
```

**Form field `code`:**

```php
TextInput::make('code')
    ->label('Kode')
    ->disabled()
    ->dehydrated(false)  // tidak submit — di-set oleh mutateFormDataBeforeCreate
    ->placeholder('Akan di-generate otomatis saat disimpan');
```

**Catatan `dehydrated(false)`:** field disabled yang `dehydrated(false)` tidak masuk ke `$data` di hook — pastikan di `mutateFormDataBeforeCreate` field `code` di-set dari service, bukan dari form.

**Edit page:** field `code` tetap `disabled` + tanpa `dehydrated(false)` → nilai lama tetap terkirim tapi tidak bisa diedit. Service **tidak** dipanggil di edit.

### 6.6 Concurrency & Edge Cases

| Skenario | Handling |
| --- | --- |
| 2 user create bareng (race) | `lockForUpdate()` → request kedua antre, dapat nomor berikutnya. Aman. |
| Create di awal bulan baru | Service cek sequence (year, month) tidak ketemu → create row baru dengan `last_number = 1`. |
| Transaction gagal/rollback setelah kode di-generate | Sequence `last_number` juga rollback (satu `DB::transaction`). Nomor tidak terlewat. |
| User hapus/soft-delete record | Running number **tidak** di-decrement. Nomor di sequence terus naik. Soft-deleted record tetap pakai kodenya. |
| Nomor >= 9999 (overflow 4 digit) | **Perlu attention manual.** Service akan tetap jalan tapi format `%04d` menghasilkan `10000` (5 digit) — bisa breaking kalau ada validasi length di tempat lain. Rencana mitigasi: migrate ke 5 digit. |
| Backdating transaksi (tanggal masa lalu) | Sequence tetap pakai `now()` (bulan saat ini), **bukan** `transaction_date`. Kalau user set `transaction_date` September tapi dibuat di Oktober, kode tetap `QUO/26/10/...`. Catat ini di user training. |

### 6.7 Kenapa Sequence Table, Bukan `MAX(code) + 1`?

| Pendekatan | Pros | Cons |
| --- | --- | --- |
| `MAX(code) + 1` | Simple, no new table | Race condition (2 user → duplikat nomor), gagal kalau code sempat diedit manual, parsing string tiap kali |
| **Sequence table** (dipakai) | Atomic via DB lock, resilient terhadap edit, no parsing | +1 migration, +1 model kecil |
| Redis counter | Super cepat | Butuh Redis, sinkronisasi dengan DB rumit kalau transaction rollback |

Sequence table adalah standar untuk ERP. Overhead-nya minim.

## 7. Catatan

- **Enum = sumber kebenaran untuk nilai tetap.** Hindari magic string di seluruh codebase. `MsStatus::where('type', StatusType::Quotation->value)` lebih aman dari `where('type', 'quotation')`.
- **AutoCode selalu via service, bukan model event.** Model yang pakai AutoCode (misal `MsItemCategory`, `TransQuotationHd`) **tidak** punya boot method yang auto-generate code. Pembuatan kode adalah tanggung jawab Filament hook.
- **Jangan pindah logic ke Model observer** meski terlihat lebih "ringkas". Observer bikin side effect yang gak kelihatan di call site; lebih sulit test dan skip.
- **Enum helper method harus pure.** Boleh `match` statement, boleh `return label string`, tapi **tidak** boleh akses DB atau panggil service.
- **File implementation (enum, service, migration sequence table) belum dibuat.** Doc ini adalah blueprint — buat setelah mindset dan tanda tangan setuju.

---

## Lampiran: File yang Perlu Dibuat untuk Implementasi AutoCode

Daftar file konkret saat implementasi (tidak di tahap ini):

1. ~~`app/Enums/CodeFormat.php`~~ ✓ dibuat.
2. ~~`app/Enums/AutoCodePrefix.php`~~ ✓ dibuat.
3. ~~`app/Enums/StatusType.php`~~ ✓ dibuat.
4. ~~`app/Enums/VatType.php`~~ ✓ dibuat.
5. ~~`app/Enums/Status/{Quotation,Project,ProjectDt,Term,Invoice,Payment}Status.php`~~ ✓ 6 file dibuat.
6. ~~`database/migrations/2026_10_03_140014_create_sys_code_sequence_table.php`~~ ✓ dibuat.
7. ~~`app/Models/CodeSequence.php`~~ ✓ dibuat.
8. ~~`app/Services/Shared/GenerateCodeService.php`~~ ✓ dibuat.
9. **TODO** Update model `TransQuotationHd` dan `TransInvoiceHd` untuk cast `vat_type` ke `VatType::class` dan `status_id` relation (tunggu transaksi model dibuat).
10. **TODO** Update Filament `CreateXxx` page untuk entity yang pakai AutoCode (`mutateFormDataBeforeCreate` → panggil service).
11. **TODO** Update Filament form schema untuk entity yang pakai AutoCode (`TextInput::make('code')->disabled()->dehydrated(false)`).
12. **TODO** Seeder `MsStatus` yang loop seluruh case dari 6 status enum → generate 25 row ke `ms_status`.

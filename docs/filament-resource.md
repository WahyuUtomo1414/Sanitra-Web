# Requirement Filament Resource Sanitra

**Status:** Implemented (master resources) · **Acuan:** [database.md](database.md), [prd.md](prd.md) · **Panel:** Filament 5.9.0

## 1. Tujuan

Dokumen ini menjadi acuan pembuatan resource admin menggunakan Filament untuk aplikasi internal Sanitra.

Scope tahap ini:

- Generate resource memakai command artisan.
- Fokus pada resource, form, table, infolist, dan relation manager.
- Resource disesuaikan dengan struktur database pada `docs/database.md`.
- Resource memakai model domain dengan `SoftDeletes`, `AuditedBySoftDelete`, dan `protected $guarded = ['id'];`.
- Role dan permission admin memakai Spatie Permission.
- Semua label UI wajib memakai bahasa Indonesia.

Catatan penting:

- Panel admin memakai model `User` bawaan Laravel (tabel `users`), bukan tabel `admin`.
- Nama tabel domain mengikuti ERD (`ms_*`, `trans_*`) dan model mengikuti pemetaan Laravel (`MsCompany`, `TransQuotationHd`, dst.) — lihat `database.md` §2.
- Nama **resource class dan folder** ikut nama model (`MsCompanyResource`, `TransQuotationHdResource`). Prefix `Ms`/`Trans` **tidak boleh bocor ke UI**: setiap resource wajib override `$navigationLabel`, `$modelLabel`, `$pluralModelLabel`, dan `$slug` ke bahasa Indonesia bersih (lihat §6 dan §9).
- Trait `AuditedBySoftDelete` di proyek ini **melempar `RuntimeException`** jika tidak ada user login. Seeder dan console command wajib memanggil `Auth::setUser($user)` sebelum menyimpan model domain.

## 2. Model Resource

Resource standalone (15):

- `User`
- `MsCompany`
- `MsEmployee`
- `MsClient`
- `MsProjectType`
- `MsProjectWorkflow`
- `MsItemCategory`
- `MsItem`
- `MsUnit`
- `MsBankAccount`
- `MsStatus`
- `TransQuotationHd`
- `TransProjectHd`
- `TransInvoiceHd`
- `TransPayment`

Model yang **tidak** jadi resource standalone (dikelola lewat relation manager):

| Model | Dikelola dari resource |
| --- | --- |
| `MsItemUnit` | `MsItemResource` |
| `TransQuotationDt` | `TransQuotationHdResource` |
| `TransQuotationTerm` | `TransQuotationHdResource` |
| `TransProjectDt` | `TransProjectHdResource` |
| `TransProjectMonitoring` | `TransProjectHdResource` (read-only, append-only log) |
| `TransInvoiceDt` | `TransInvoiceHdResource` |

Catatan:

- Semua resource domain memakai pola soft delete dan audit user.
- Model domain wajib menulis `protected $table` eksplisit karena nama tabel mengikuti ERD.
- Model domain memakai `protected $guarded = ['id'];`, bukan `$fillable`.

## 3. Dependency

Package yang dibutuhkan:

```bash
composer require filament/filament
composer require spatie/laravel-permission
```

Setelah dependency terpasang:

- Buat panel admin Filament.
- Publish migration dan config Spatie Permission sesuai dokumentasi package.
- Tambahkan trait `HasRoles` pada model `User`.
- Jangan menambah kolom `level`, `role`, atau `permission` pada tabel `users`.

## 4. Command Generate Resource

Command resource mengikuti preferensi:

- Pakai `php artisan make:filament-resource`.
- Pakai `--generate` untuk introspect model.
- Pakai `--soft-deletes` untuk resource domain yang memakai soft delete.

Daftar command:

```bash
php artisan make:filament-resource User --generate
php artisan make:filament-resource MsCompany --generate --soft-deletes
php artisan make:filament-resource MsEmployee --generate --soft-deletes
php artisan make:filament-resource MsClient --generate --soft-deletes
php artisan make:filament-resource MsProjectType --generate --soft-deletes
php artisan make:filament-resource MsProjectWorkflow --generate --soft-deletes
php artisan make:filament-resource MsItemCategory --generate --soft-deletes
php artisan make:filament-resource MsItem --generate --soft-deletes
php artisan make:filament-resource MsUnit --generate --soft-deletes
php artisan make:filament-resource MsBankAccount --generate --soft-deletes
php artisan make:filament-resource MsStatus --generate --soft-deletes
php artisan make:filament-resource TransQuotationHd --generate --soft-deletes
php artisan make:filament-resource TransProjectHd --generate --soft-deletes
php artisan make:filament-resource TransInvoiceHd --generate --soft-deletes
php artisan make:filament-resource TransPayment --generate --soft-deletes
```

Catatan:

- `UserResource` tidak memakai `--soft-deletes` karena tabel `users` bawaan Laravel belum memakai soft delete.
- Resource domain memakai `--soft-deletes` karena tabel domain memakai `$this->addAuditColumns($table);`.
- Folder resource yang dihasilkan tetap memakai prefix (`App\Filament\Resources\MsCompany\...`). **Tidak** di-rename manual — folder dengan prefix diterima apa adanya, hanya UI yang dibersihkan.

## 5. Standar Struktur Resource

Resource dirapikan dengan pola modular:

```php
<?php

namespace App\Filament\Resources\MsCompany;

use App\Filament\Resources\MsCompany\Pages\CreateMsCompany;
use App\Filament\Resources\MsCompany\Pages\EditMsCompany;
use App\Filament\Resources\MsCompany\Pages\ListMsCompanies;
use App\Filament\Resources\MsCompany\Pages\ViewMsCompany;
use App\Filament\Resources\MsCompany\Schemas\MsCompanyForm;
use App\Filament\Resources\MsCompany\Schemas\MsCompanyInfolist;
use App\Filament\Resources\MsCompany\Tables\MsCompaniesTable;
use App\Models\MsCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MsCompanyResource extends Resource
{
    protected static ?string $model = MsCompany::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Perusahaan';

    protected static ?string $navigationLabel = 'Perusahaan';

    protected static ?string $modelLabel = 'Perusahaan';

    protected static ?string $pluralModelLabel = 'Perusahaan';

    protected static ?string $slug = 'companies';

    public static function form(Schema $schema): Schema
    {
        return MsCompanyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MsCompanyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MsCompaniesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMsCompanies::route('/'),
            'create' => CreateMsCompany::route('/create'),
            'view' => ViewMsCompany::route('/{record}'),
            'edit' => EditMsCompany::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
```

## 6. Properti Resource

**WAJIB** override empat properti berikut di setiap resource — jangan andalkan auto-derive dari class name, karena akan menghasilkan label jelek seperti "Ms Companies" atau "Trans Quotation Hds":

```php
protected static ?string $model = ModelName::class;

protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

protected static string|UnitEnum|null $navigationGroup = 'Nama Group';

protected static ?string $navigationLabel = 'Label Bahasa Indonesia';

protected static ?string $modelLabel = 'Label Bahasa Indonesia';

protected static ?string $pluralModelLabel = 'Label Bahasa Indonesia';

protected static ?string $slug = 'url-slug-bersih';
```

Ketentuan:

- Label wajib bahasa Indonesia tanpa prefix `Ms`/`Trans`.
- `$slug` wajib di-set agar URL bersih (misal `/admin/companies`, bukan `/admin/ms-companies`).
- Nama model tetap PascalCase (`MsCompany`, `TransQuotationHd`).
- Nama tabel tetap mengikuti `docs/database.md` (`ms_company`, `trans_quotation_hd`).

## 7. Standar Page

Page default untuk resource domain:

- `List`
- `Create`
- `View`
- `Edit`

Pengecualian:

- `UserResource` memakai `List`, `Create`, dan `Edit` (tanpa `View` karena data user sederhana).
- Halaman detail memakai `Infolist`.
- Resource header transaksi (`TransQuotationHd`, `TransProjectHd`, `TransInvoiceHd`) wajib punya halaman `View` dengan relation manager detail.
- Form resource wajib memakai `Filament\Schemas\Components\Section` dan setiap section memakai `columnSpanFull()` agar input dikelompokkan per konteks (misal `Informasi Utama`, `Relasi`, `Nominal`, `Status`). Isi section mengikuti layout dua kolom di §7.1.
- Infolist resource wajib memakai `Filament\Schemas\Components\Section` dengan minimal section `Informasi Utama` dan `Audit Data`; setiap section memakai `columnSpanFull()` dan isi section mengikuti layout dua kolom di §7.1.
- Section `Audit Data` berisi `createdBy`, `created_at`, `updatedBy`, `updated_at`, `deletedBy`, dan `deleted_at` untuk resource domain yang memakai audit/soft delete.

### 7.1 Standar Layout Form dan Halaman Detail

Aturan ini berlaku untuk **semua resource**, baik form Create/Edit maupun infolist halaman View. `columnSpanFull()` pada `Section` mengatur lebar section terhadap halaman; `->columns(2)` pada section mengatur field **di dalamnya**. Keduanya harus dipakai bersama. Filament menampilkan dua kolom mulai breakpoint `lg` dan satu kolom pada layar yang lebih kecil.

- Setiap section yang berisi field memakai `->columnSpanFull()->columns(2)`. Ini mencakup `Informasi Utama`, `Relasi`, `Status`, `Audit Data`, dan section lain yang relevan.
- Field pendek memakai lebar **satu kolom** agar dua field yang berkaitan tampil berdampingan. Pasangkan secara logis dan jaga urutan yang sama di form serta infolist: misalnya **Nama | Nama Legal**, **Email | Telepon**, **Perusahaan Induk | PIC Karyawan**, **Dibuat Oleh | Dibuat Pada**, dan seterusnya.
- `Deskripsi` memakai `->columnSpanFull()`. Field teks panjang atau multiline lain, seperti alamat dan catatan, juga boleh selebar dua kolom agar mudah dibaca atau diisi.
- Jangan memakai `->columnSpanFull()` pada semua input atau entry. Jika satu section memiliki jumlah field pendek ganjil, field terakhir boleh menempati satu kolom; jangan dipasangkan dengan field yang tidak berkaitan hanya untuk mengisi baris.
- Pilihan relasi (`Select` pada form, nama relasi pada infolist) mengikuti grid yang sama. Bila ada dua relasi terkait, letakkan berdampingan di section `Relasi`.
- Field audit di halaman View ditata berpasangan per kejadian: `createdBy` dengan `created_at`, `updatedBy` dengan `updated_at`, dan `deletedBy` dengan `deleted_at` jika record dihapus.

Contoh pola `MsCompanyForm` (aturan yang sama diterapkan pada resource lain):

```php
Section::make('Informasi Utama')
    ->columnSpanFull()
    ->columns(2)
    ->schema([
        TextInput::make('name')->label('Nama'),
        TextInput::make('legal_name')->label('Nama Legal'),
        TextInput::make('email')->label('Email'),
        TextInput::make('phone')->label('Telepon'),
        Textarea::make('description')->label('Deskripsi')->columnSpanFull(),
    ]),

Section::make('Relasi')
    ->columnSpanFull()
    ->columns(2)
    ->schema([
        Select::make('parent_id')->label('Perusahaan Induk'),
        Select::make('pic_employee_id')->label('PIC Karyawan'),
    ]),
```

Contoh pola `MsCompanyInfolist`:

```php
Section::make('Informasi Utama')
    ->columnSpanFull()
    ->columns(2)
    ->schema([
        TextEntry::make('name')->label('Nama'),
        TextEntry::make('legal_name')->label('Nama Legal'),
        TextEntry::make('email')->label('Email'),
        TextEntry::make('phone')->label('Telepon'),
        TextEntry::make('description')->label('Deskripsi')->columnSpanFull(),
    ]),

Section::make('Relasi')
    ->columnSpanFull()
    ->columns(2)
    ->schema([
        TextEntry::make('parent.name')->label('Perusahaan Induk'),
        TextEntry::make('picEmployee.name')->label('PIC Karyawan'),
    ]),
```

Field lain seperti kode, status aktif, gambar, dan field khusus resource tetap ditampilkan dalam section yang sesuai; atur urutannya agar pasangan utama di atas tidak terpecah.

## 8. Navigation Group

Sidebar terdiri dari 5 navigation group. Group master data dipecah per domain — tidak semua disatukan di "Master Data" agar sidebar tidak terlalu panjang.

| Resource | Group |
| --- | --- |
| `User` | `Pengguna` |
| `MsCompany` | `Perusahaan` |
| `MsEmployee` | `Perusahaan` |
| `MsClient` | `Perusahaan` |
| `MsBankAccount` | `Perusahaan` |
| `MsItemCategory` | `Item` |
| `MsItem` | `Item` |
| `MsUnit` | `Item` |
| `MsProjectType` | `Project` |
| `MsProjectWorkflow` | `Project` |
| `MsStatus` | `Master Data` |
| `TransQuotationHd` | `Penjualan` |
| `TransProjectHd` | `Project` |
| `TransInvoiceHd` | `Keuangan` |
| `TransPayment` | `Keuangan` |

## 9. Label, Slug, dan Icon Resource

| Model | Navigation Label | Model Label | Plural Model Label | Slug | Group | Icon |
| --- | --- | --- | --- | --- | --- | --- |
| `User` | `Pengguna` | `Pengguna` | `Pengguna` | `users` | `Pengguna` | `heroicon-o-users` |
| `MsCompany` | `Perusahaan` | `Perusahaan` | `Perusahaan` | `companies` | `Perusahaan` | `heroicon-o-building-office-2` |
| `MsEmployee` | `Karyawan` | `Karyawan` | `Karyawan` | `employees` | `Perusahaan` | `heroicon-o-identification` |
| `MsClient` | `Klien` | `Klien` | `Klien` | `clients` | `Perusahaan` | `heroicon-o-user-circle` |
| `MsBankAccount` | `Rekening Bank` | `Rekening Bank` | `Rekening Bank` | `bank-accounts` | `Perusahaan` | `heroicon-o-building-library` |
| `MsItemCategory` | `Kategori Item` | `Kategori Item` | `Kategori Item` | `item-categories` | `Item` | `heroicon-o-tag` |
| `MsItem` | `Item` | `Item` | `Item` | `items` | `Item` | `heroicon-o-cube` |
| `MsUnit` | `Unit` | `Unit` | `Unit` | `units` | `Item` | `heroicon-o-scale` |
| `MsProjectType` | `Tipe Project` | `Tipe Project` | `Tipe Project` | `project-types` | `Project` | `heroicon-o-squares-2x2` |
| `MsProjectWorkflow` | `Workflow Project` | `Workflow Project` | `Workflow Project` | `project-workflows` | `Project` | `heroicon-o-queue-list` |
| `MsStatus` | `Status` | `Status` | `Status` | `statuses` | `Master Data` | `heroicon-o-check-badge` |
| `TransQuotationHd` | `Quotation` | `Quotation` | `Quotation` | `quotations` | `Penjualan` | `heroicon-o-document-text` |
| `TransProjectHd` | `Project` | `Project` | `Project` | `projects` | `Project` | `heroicon-o-briefcase` |
| `TransInvoiceHd` | `Invoice` | `Invoice` | `Invoice` | `invoices` | `Keuangan` | `heroicon-o-document-currency-dollar` |
| `TransPayment` | `Pembayaran` | `Pembayaran` | `Pembayaran` | `payments` | `Keuangan` | `heroicon-o-banknotes` |

## 10. Standar Implementasi

Setelah command resource dijalankan:

- Rapikan resource class agar sesuai pola modular Filament (§5).
- Sesuaikan `navigationGroup`, `navigationIcon`, `navigationLabel`, `modelLabel`, `pluralModelLabel`, dan `slug`.
- Rapikan form schema (bagi per `Section` dengan `columnSpanFull()` dan `columns(2)` untuk isinya; lihat §7.1).
- Rapikan table schema.
- Tambahkan infolist untuk halaman detail dengan aturan dua kolom yang sama (§7.1).
- Tambahkan kolom audit `createdBy`, `updatedBy`, dan `deletedBy` pada semua table resource domain.
- Aktifkan query tanpa `SoftDeletingScope` untuk resource domain.
- Pertahankan dukungan soft delete pada table action dan filter.
- Pastikan form tidak menginput manual `created_by`, `updated_by`, dan `deleted_by`.

Catatan:

- Audit user otomatis diisi oleh trait `AuditedBySoftDelete`.
- Kolom `active` boleh tampil di form sebagai toggle.
- Kolom `deleted_by` hanya relevan untuk data yang sudah soft delete.
- Field upload menyimpan path file sesuai rancangan database.
- Kolom nominal Rupiah (`decimal(20, 6)`) di form memakai `TextInput::numeric()` dengan prefix "Rp" dan di table memakai `TextColumn::money('IDR')`.

## 11. Kolom Audit Table

Semua table resource domain wajib menambahkan kolom:

```php
TextColumn::make('createdBy.name')
    ->label('Dibuat Oleh')
    ->badge()
    ->description(fn ($record) => $record->created_at?->format('d M Y H:i'))
    ->sortable(),

TextColumn::make('updatedBy.name')
    ->label('Diubah Oleh')
    ->badge()
    ->description(fn ($record) => $record->updated_at?->format('d M Y H:i'))
    ->sortable()
    ->toggleable(isToggledHiddenByDefault: true),

TextColumn::make('deletedBy.name')
    ->label('Dihapus Oleh')
    ->badge()
    ->description(fn ($record) => $record->deleted_at?->format('d M Y H:i'))
    ->sortable()
    ->toggleable(isToggledHiddenByDefault: true),
```

Ketentuan:

- `Dibuat Oleh` tampil default di table.
- `Diubah Oleh` dan `Dihapus Oleh` dibuat toggleable agar table tetap ringkas.
- Relasi audit berasal dari trait `AuditedBySoftDelete` (`createdBy`, `updatedBy`, `deletedBy`).

## 12. Action dan Filter Table

Action resource domain:

- `ViewAction`
- `EditAction`
- `DeleteAction`
- `RestoreAction`
- `ForceDeleteAction`

Bulk action resource domain:

- `DeleteBulkAction`
- `RestoreBulkAction`
- `ForceDeleteBulkAction`

Filter umum:

- Filter `active` untuk semua resource master.
- Filter trashed/soft delete untuk resource domain.
- Filter tanggal transaksi untuk resource transaksi.

Filter khusus:

- Filter `company_id` untuk `MsEmployee`, `MsProjectType`, dan resource transaksi.
- Filter `project_type_id` untuk `MsProjectWorkflow`, `TransQuotationHd`, dan `TransProjectHd`.
- Filter `item_category_id` untuk `MsItem`.
- Filter `type` untuk `MsStatus` (quotation, project, project_dt, term, invoice, payment).
- Filter `client_id` untuk `TransQuotationHd`, `TransProjectHd`, dan `TransInvoiceHd`.
- Filter `status_id` untuk semua resource transaksi (query status dibatasi per `type` entitas).
- Filter `due_date` untuk `TransInvoiceHd`.
- Filter `payment_method` dan `status_id` untuk `TransPayment`.

Catatan:

- `ForceDeleteAction` dibatasi untuk role super admin.
- `UserResource` tidak perlu action soft delete selama tabel `users` belum memakai soft delete.
- Resource transaksi punya **custom action** untuk transisi status (lihat §14 bagian resource terkait).

## 13. Standar Upload File

Field upload wajib memakai `FileUpload`.

Contoh konfigurasi:

```php
FileUpload::make('image')
    ->label('Gambar')
    ->image()
    ->disk('public')
    ->directory('items')
    ->visibility('public')
    ->preserveFilenames()
    ->maxSize(2048);
```

Ketentuan:

- Gunakan `disk('public')` untuk semua upload gambar dan dokumen.
- Gunakan `directory()` sesuai nama tabel atau konteks resource.
- Database hanya menyimpan path file, bukan binary file.
- Jalankan `php artisan storage:link` agar file di disk public bisa diakses dari browser.

Mapping directory upload:

| Resource | Field | Disk | Directory |
| --- | --- | --- | --- |
| `UserResource` | `avatar` | `public` | `users/avatar` |
| `MsItemResource` | `image` | `public` | `items` |
| `TransPaymentResource` | `payment_proof` | `public` | `payments/proof` |

## 14. Form Per Resource

### 14.1 User

Field:

- `name`
- `email`
- `password`
- `employee_id`
- `avatar`
- role Spatie Permission

Catatan:

- `employee_id` memakai select relasi ke `MsEmployee`; unique karena satu employee maksimal satu user.
- `password` wajib saat create, opsional saat edit.
- `avatar` memakai `FileUpload`, opsional.
- Role user dikelola melalui Spatie Permission.

### 14.2 MsCompany

Field:

- `code`
- `name`
- `legal_name`
- `email`
- `phone`
- `description`
- `parent_id`
- `pic_employee_id`
- `active`

Catatan:

- `parent_id` memakai select relasi self ke `MsCompany`; opsional.
- `pic_employee_id` memakai select relasi ke `MsEmployee`; opsional.
- `description` memakai textarea.
- `active` memakai toggle.

### 14.3 MsEmployee

Field:

- `code`
- `name`
- `position`
- `company_id`
- `email`
- `phone`
- `active`

Catatan:

- `company_id` memakai select relasi ke `MsCompany`, wajib.

### 14.4 MsClient

Field:

- `code`
- `name`
- `pic_employee_id`
- `email`
- `phone`
- `address`
- `description`
- `active`

Catatan:

- `code` wajib unik (FR-03).
- `pic_employee_id` memakai select relasi ke `MsEmployee`, opsional.
- `address` memakai textarea.

### 14.5 MsProjectType

Field:

- `code`
- `name`
- `company_id`
- `active`

Catatan:

- `company_id` memakai select relasi ke `MsCompany`, opsional.

### 14.6 MsProjectWorkflow

Field:

- `type_id`
- `name`
- `sequence`
- `description`
- `progress_weight`
- `active`

Catatan:

- `type_id` memakai select relasi ke `MsProjectType`, wajib.
- `progress_weight` validasi 0–100.
- Validasi aplikasi: total `progress_weight` per `type_id` harus 100 sebelum workflow dipakai di quotation/project (BR-03).

### 14.7 MsItemCategory

Field:

- `code`
- `name`
- `description`
- `active`

### 14.8 MsItem

Field:

- `item_category_id`
- `code`
- `name`
- `image`
- `description`
- `active`

Relation manager:

- `UnitsRelationManager` → mengelola `MsItemUnit` dari halaman detail Item.

Catatan:

- `item_category_id` memakai select relasi ke `MsItemCategory`, wajib.
- `image` memakai `FileUpload` disk `public` directory `items`, opsional.
- Setiap item wajib punya tepat satu unit dasar (`is_base = true`); divalidasi di relation manager.

### 14.9 MsUnit

Field:

- `code`
- `name`
- `type`
- `description`
- `active`

### 14.10 MsBankAccount

Field:

- `code`
- `name`
- `bank_name`
- `account_number`
- `account_name`
- `description`
- `active`

Catatan:

- `account_number` disimpan sebagai string agar nol awal tidak hilang.

### 14.11 MsStatus

Field:

- `type`
- `code`
- `name`
- `description`
- `active`

Catatan:

- `type` memakai select dengan opsi fixed: `quotation`, `project`, `project_dt`, `term`, `invoice`, `payment`. Idealnya pakai PHP Enum.
- Kombinasi (`type`, `code`) wajib unik.
- Data awal 25 status disiapkan via seeder sesuai SRS §6.1.

### 14.12 TransQuotationHd

Field:

- `code`
- `transaction_date`
- `client_id`
- `project_type_id`
- `company_id`
- `payment_type`
- `vat_type`
- `vat_amount`
- `discount_total`
- `subtotal`
- `grand_total`
- `notes`
- `status_id`

Relation manager:

- `ItemsRelationManager` → mengelola `TransQuotationDt`.
- `TermsRelationManager` → mengelola `TransQuotationTerm`.

Custom action (menggantikan edit status langsung):

- `SendAction` → ubah status dari Draft ke Sent.
- `ApproveAction` → ubah status dari Sent ke Approved; project kemudian dibuat lewat form create Project dengan memilih quotation Approved.
- `RejectAction` → ubah status dari Sent ke Rejected.

Catatan:

- `client_id`, `project_type_id`, `company_id` wajib.
- `status_id` select dibatasi `MsStatus::where('type', 'quotation')`.
- `subtotal`, `vat_amount`, `discount_total`, `grand_total` dihitung dari detail; input manual dinonaktifkan atau read-only di form.
- Setelah status Approved/Rejected, form tidak bisa diedit.
- Total `percentage` di `TransQuotationTerm` harus 100 sebelum quotation bisa di-send (BR-01).

### 14.13 TransProjectHd

Field:

- `code`
- `transaction_date`
- `quotation_hd_id`
- `client_id`
- `company_id`
- `project_type_id`
- `pic_employee_id`
- `project_name`
- `start_date`
- `end_date`
- `progress_percentage`
- `notes`
- `status_id`

Relation manager:

- `StagesRelationManager` → mengelola `TransProjectDt`.
- `MonitoringRelationManager` → menampilkan `TransProjectMonitoring` **read-only** (tidak ada create/edit/delete action).

Catatan:

- Form create Project memilih quotation berstatus Approved. `client_id`, `company_id`, dan `project_type_id` terisi dari quotation tersebut; service tetap memvalidasi status dan mencegah project ganda.
- `quotation_hd_id` unique agar satu quotation hanya dapat memiliki satu project. Tahap project dibuat dari master workflow saat form create Project disimpan.
- `progress_percentage` dihitung dari jumlah bobot tahap Completed; read-only di form.
- `status_id` select dibatasi `MsStatus::where('type', 'project')`.
- Setelah termin pertama ditagih, bobot tahap dan daftar tahap tidak bisa diubah (BR-06).

### 14.14 TransInvoiceHd

Field:

- `code`
- `transaction_date`
- `project_hd_id`
- `quotation_hd_id`
- `client_id`
- `company_id`
- `bank_account_id`
- `due_date`
- `subtotal`
- `vat_type`
- `vat_amount`
- `grand_total`
- `notes`
- `status_id`

Relation manager:

- `TermsRelationManager` → mengelola `TransInvoiceDt`.
- `PaymentsRelationManager` → menampilkan `TransPayment` terkait invoice.

Custom action:

- `VoidAction` → ubah status jadi Void, kembalikan termin ke Ready to Bill.
- `PrintPdfAction` → cetak invoice.

Catatan:

- `status_id` select dibatasi `MsStatus::where('type', 'invoice')`.
- Pembuatan invoice sekaligus ubah termin jadi Invoiced dan kunci tahap Completed; wajib transaksi database (NFR-04).
- Invoice tidak bisa diedit setelah ada pembayaran (BR-11).

### 14.15 TransPayment

Field:

- `code`
- `transaction_date`
- `invoice_hd_id`
- `amount`
- `payment_method`
- `reference_no`
- `payment_proof`
- `notes`
- `status_id`

Catatan:

- `invoice_hd_id` memakai select relasi ke `TransInvoiceHd`, wajib.
- `payment_proof` memakai `FileUpload` disk `public` directory `payments/proof`, opsional.
- `reference_no` opsional (tidak semua metode punya referensi, misal cash).
- `status_id` select dibatasi `MsStatus::where('type', 'payment')`.
- Hanya payment berstatus Confirmed yang dihitung ke pelunasan invoice.

## 15. Table Per Resource

### 15.1 User

Kolom: `name`, `email`, `employee.name`, role Spatie, `created_at`.

### 15.2 MsCompany

Kolom: `code`, `name`, `legal_name`, `parent.name`, `picEmployee.name`, `active`, audit.

### 15.3 MsEmployee

Kolom: `code`, `name`, `position`, `company.name`, `email`, `active`, audit.

### 15.4 MsClient

Kolom: `code`, `name`, `picEmployee.name`, `email`, `phone`, `active`, audit.

### 15.5 MsProjectType

Kolom: `code`, `name`, `company.name`, `active`, audit.

### 15.6 MsProjectWorkflow

Kolom: `type.name`, `sequence`, `name`, `progress_weight`, `active`, audit.

### 15.7 MsItemCategory

Kolom: `code`, `name`, `active`, audit.

### 15.8 MsItem

Kolom: `code`, `name`, `category.name`, `image`, `active`, audit.

### 15.9 MsUnit

Kolom: `code`, `name`, `type`, `active`, audit.

### 15.10 MsBankAccount

Kolom: `code`, `name`, `bank_name`, `account_number`, `account_name`, `active`, audit.

### 15.11 MsStatus

Kolom: `type`, `code`, `name`, `active`, audit.

### 15.12 TransQuotationHd

Kolom: `code`, `transaction_date`, `client.name`, `projectType.name`, `grand_total`, `status.name`, audit.

### 15.13 TransProjectHd

Kolom: `code`, `project_name`, `client.name`, `start_date`, `end_date`, `progress_percentage`, `status.name`, audit.

### 15.14 TransInvoiceHd

Kolom: `code`, `transaction_date`, `due_date`, `project.code`, `client.name`, `grand_total`, `status.name`, audit.

### 15.15 TransPayment

Kolom: `code`, `transaction_date`, `invoice.code`, `amount`, `payment_method`, `status.name`, audit.

## 16. Infolist Per Resource

Semua infolist memiliki minimal section `Informasi Utama` dan `Audit Data`. Susunan field mengikuti standar dua kolom §7.1; deskripsi dan teks panjang memakai lebar penuh.

### 16.1 User

Tampilkan: nama, email, karyawan, avatar, role, tanggal dibuat.

### 16.2 MsCompany

Tampilkan: kode, nama, nama legal, email, telepon, deskripsi, induk, PIC karyawan, status aktif, audit data.

### 16.3 MsEmployee

Tampilkan: kode, nama, jabatan, perusahaan, email, telepon, status aktif, audit data.

### 16.4 MsClient

Tampilkan: kode, nama, PIC karyawan, email, telepon, alamat, deskripsi, status aktif, audit data.

### 16.5 MsProjectType

Tampilkan: kode, nama, perusahaan, status aktif, audit data.

### 16.6 MsProjectWorkflow

Tampilkan: tipe project, urutan, nama tahap, deskripsi, bobot progress, status aktif, audit data.

### 16.7 MsItemCategory

Tampilkan: kode, nama, deskripsi, status aktif, audit data.

### 16.8 MsItem

Tampilkan: kode, nama, kategori, gambar, deskripsi, daftar unit (relation manager), status aktif, audit data.

### 16.9 MsUnit

Tampilkan: kode, nama, tipe, deskripsi, status aktif, audit data.

### 16.10 MsBankAccount

Tampilkan: kode, nama, nama bank, nomor rekening, nama pemilik, deskripsi, status aktif, audit data.

### 16.11 MsStatus

Tampilkan: tipe entitas, kode, nama, deskripsi, status aktif, audit data.

### 16.12 TransQuotationHd

Tampilkan: kode, tanggal transaksi, klien, tipe project, perusahaan penerbit, skema pembayaran, catatan, nominal (subtotal/vat/discount/grand total), status, item (relation manager), termin (relation manager), audit data.

### 16.13 TransProjectHd

Tampilkan: kode, tanggal transaksi, quotation sumber, klien, perusahaan, tipe project, PIC karyawan, nama project, tanggal mulai, tanggal selesai, progress, catatan, status, tahap (relation manager), log monitoring (relation manager read-only), audit data.

### 16.14 TransInvoiceHd

Tampilkan: kode, tanggal transaksi, project, quotation, klien, perusahaan penerbit, rekening tujuan, jatuh tempo, nominal (subtotal/vat/grand total), catatan, status, termin (relation manager), pembayaran (relation manager), audit data.

### 16.15 TransPayment

Tampilkan: kode, tanggal transaksi, invoice, nominal, metode pembayaran, nomor referensi, bukti pembayaran, catatan, status, audit data.

## 17. Relation Manager

### 17.1 MsItemResource → UnitsRelationManager

Mengelola `MsItemUnit`.

Form field:

- `unit_id` (select relasi ke `MsUnit`)
- `is_base` (toggle)
- `conversion` (integer, faktor konversi)

Table kolom: `unit.name`, `is_base`, `conversion`, audit.

Catatan:

- Pasangan (`item_id`, `unit_id`) unik.
- Tepat satu baris `is_base = true` per item; validasi di model observer atau form.

### 17.2 TransQuotationHdResource → ItemsRelationManager

Mengelola `TransQuotationDt`.

Form field:

- `item_id` (select relasi ke `MsItem`)
- `unit_id` (select relasi ke `MsUnit`, dibatasi per item)
- `qty` (integer)
- `unit_price` (decimal 20,6)
- `discount` (decimal 20,6)
- `total_price` (decimal 20,6, dihitung otomatis)
- `notes`

Table kolom: `item.name`, `unit.name`, `qty`, `unit_price`, `discount`, `total_price`.

Catatan:

- `total_price` dihitung di form berdasarkan `qty`, `unit_price`, dan `discount`.
- Perubahan item menghitung ulang subtotal/grand_total di header.

### 17.3 TransQuotationHdResource → TermsRelationManager

Mengelola `TransQuotationTerm`.

Form field:

- `sequence` (integer)
- `term_name`
- `percentage` (integer 0–100)
- `amount` (decimal 20,6, dihitung dari persentase × grand total)
- `progress_threshold` (integer 0–100)
- `notes`
- `status_id` (select dibatasi `MsStatus::where('type', 'term')`)

Table kolom: `sequence`, `term_name`, `percentage`, `amount`, `progress_threshold`, `status.name`.

Catatan:

- (`quotation_hd_id`, `sequence`) unik.
- Total `percentage` harus 100 sebelum quotation bisa di-send (BR-01).

### 17.4 TransProjectHdResource → StagesRelationManager

Mengelola `TransProjectDt`.

Form field:

- `sequence`
- `work_name`
- `progress_weight` (integer 0–100)
- `status_id` (select dibatasi `MsStatus::where('type', 'project_dt')`)
- `start_date`
- `end_date`
- `notes`
- `editable` (toggle)

Table kolom: `sequence`, `work_name`, `progress_weight`, `status.name`, `start_date`, `end_date`, `editable`.

Catatan:

- Perubahan `status_id` memicu pembuatan baris `TransProjectMonitoring` secara otomatis (BR-10).
- Tahap dengan `editable = false` (karena sudah masuk invoice) hanya boleh diubah oleh role override, wajib dengan alasan (FR-25).

### 17.5 TransProjectHdResource → MonitoringRelationManager

Menampilkan `TransProjectMonitoring` — **read-only, append-only log**.

Table kolom: `created_at`, `project_dt.work_name`, `log_type`, `statusBefore.name`, `statusAfter.name`, `progress_before`, `progress_after`, `notes`, `createdBy.name`.

Catatan:

- **Tidak ada** `CreateAction`, `EditAction`, atau `DeleteAction` di relation manager ini.
- Log dibuat otomatis dari event perubahan tahap, bukan input manual.
- Koreksi dibuat sebagai log baru (BR-10).

### 17.6 TransInvoiceHdResource → TermsRelationManager

Mengelola `TransInvoiceDt`.

Form field:

- `quotation_term_id` (select relasi ke `TransQuotationTerm`, dibatasi termin project terkait dengan status Ready to Bill)
- `sequence`
- `description`
- `percentage` (integer, snapshot dari termin)
- `amount` (decimal 20,6, snapshot dari termin)

Table kolom: `sequence`, `quotationTerm.term_name`, `percentage`, `amount`.

Catatan:

- Satu termin hanya boleh masuk satu invoice aktif.
- Pembuatan baris memicu lock tahap Completed terkait termin tersebut.

### 17.7 TransInvoiceHdResource → PaymentsRelationManager

Menampilkan `TransPayment` terkait invoice. Read-write bila status invoice masih aktif.

Table kolom: `code`, `transaction_date`, `amount`, `payment_method`, `status.name`, `reference_no`, `payment_proof`.

Catatan:

- Hanya payment berstatus Confirmed yang dihitung ke pelunasan.
- Invoice otomatis Paid saat total payment Confirmed ≥ `grand_total`.

## 18. Catatan Khusus Repo

- Database mengikuti `docs/database.md`.
- Nama tabel domain memakai prefix `ms_` dan `trans_` tanpa pluralisasi Laravel.
- Model domain wajib menulis `protected $table` eksplisit.
- Model domain memakai `protected $guarded = ['id'];`, bukan `$fillable`.
- Resource `User` mengarah ke tabel auth bawaan `users`, bukan `admin`.
- Permission panel dikelola Spatie Permission.
- Semua tabel domain memakai soft delete dari `$this->addAuditColumns($table);`.
- Semua resource domain harus mengaktifkan query tanpa `SoftDeletingScope` agar data trashed bisa dikelola dari Filament.
- Kolom `active` digunakan untuk status aktif/nonaktif data master (FR-07); data nonaktif tidak tersedia di transaksi baru tapi tetap terlihat di histori.
- Field upload wajib memakai `FileUpload`, disk `public`, dan `directory()` sesuai mapping (§13).
- Kolom nominal Rupiah bertipe `decimal(20, 6)` di database dan di-cast `decimal:6` di model.

## 19. Catatan Aman Testing dan Database

- Jangan menjalankan `migrate:fresh`, `migrate:fresh --seed`, atau `db:wipe` pada database lokal yang berisi data kerja tanpa backup.
- Jika ingin testing resource, gunakan database testing terpisah.
- Pastikan storage link sudah dibuat sebelum mengetes upload gambar (`php artisan storage:link`).
- **Trait `AuditedBySoftDelete` melempar `RuntimeException` jika tidak ada user login.** Di seeder, console command, atau test, panggil `Auth::setUser($user)` sebelum menyimpan model domain. Jangan andalkan fallback user ID — fallback itu sudah dihapus.
- Pastikan akun super admin dibuat lewat seeder sebelum panel dipakai, dan `Auth::setUser()` dipanggil sebelum seeder master lain berjalan.

## 20. Urutan Pengerjaan

Urutan implementasi resource:

1. Install Filament dan buat panel admin.
2. Install Spatie Permission dan pasang `HasRoles` pada model `User`.
3. Pastikan model dan migration sesuai `docs/database.md` (urutan migration lihat `database.md` §6).
4. Seed `MsStatus` dengan 25 status awal (SRS §6.1); seed super admin + `Auth::setUser()`.
5. Generate `UserResource`.
6. Generate resource master data: `MsCompany`, `MsEmployee`, `MsClient`, `MsProjectType`, `MsProjectWorkflow`, `MsItemCategory`, `MsItem`, `MsUnit`, `MsBankAccount`, `MsStatus`.
7. Generate resource penjualan: `TransQuotationHd` + relation manager item & term.
8. Generate resource project: `TransProjectHd` + relation manager stage & monitoring (read-only).
9. Generate resource keuangan: `TransInvoiceHd` + relation manager term & payment; `TransPayment` standalone.
10. Tambahkan policy atau permission gate sesuai role Spatie.
11. Tambahkan custom action untuk transisi status (Send/Approve/Reject quotation, Void invoice, dll.).

Alasan:

- Status dan user harus siap duluan karena hampir semua resource bergantung pada keduanya.
- Master data dan relasi dibuat sebelum transaksi.
- Transaksi dikerjakan sesuai alur bisnis: quotation → project → invoice → payment.
- Permission dan custom action dirapikan setelah panel admin berjalan.

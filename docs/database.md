# Database Aplikasi Web Sanitra

**Status:** Semua migration master dan infrastruktur sudah dijalankan; tabel transaksi belum dibuat · **Acuan:** [SRS v1.3](https://drive.google.com/file/d/1UPHenlYyMfJjUuIgG9wFn3pJP2gAAPq0/view), [ERD Sanitra](../../../Berkas/Sanitra-ERD.drawio%20%282%29.png) · **Terkait:** [PRD](prd.md), [bisnis-flow/](bisnis-flow/full-flow.md)

## 1. Ringkasan

Dokumen ini menjabarkan struktur database pada ERD Sanitra menjadi panduan awal untuk model Eloquent dan migration Laravel 13. SRS v1.3 (11 Oktober 2026) menambahkan modul **Sales Order** sebagai jembatan antara Quotation dan Project. Total 25 tabel domain: 14 master (termasuk `users`, `ms_client_contact`, `ms_document`) dan 11 transaksi, ditambah tiga tabel infrastruktur: `sys_code_sequence`, `sys_import_log`, dan `sys_error_log`. `basemodel` pada ERD adalah kumpulan kolom umum, bukan tabel yang dibuat di database.

Perubahan penting dari ERD awal (SRS v1.0 → v1.3):

- Tabel transaksi mengikuti prefix `trans_sales_*` untuk entitas modul sales (quotation, order, term, invoice, payment). Project tetap `trans_project_*`.
- Entitas baru: `trans_sales_order_hd/dt` sebagai salinan kontrak dari quotation Approved.
- `trans_sales_term` memiliki FK `quotation_hd_id` (wajib, lokasi penyusunan) dan `order_hd_id` (nullable awalnya, diisi saat SO dibuat).
- `trans_project_hd` sekarang punya FK `order_hd_id` (bukan langsung `quotation_hd_id`).
- `trans_sales_invoice_hd.project_hd_id` **nullable** karena invoice DP dibuat sebelum project ada.
- `ms_status` berisi 31 status untuk 8 tipe (bertambah dari 25 di versi 1.0): tipe baru `order` dan `order_payment`; status `project`, `project_dt`, `term`, dan `invoice` diperbarui.

Nama tabel autentikasi memakai `users` bawaan Laravel (bukan `user` seperti pada ERD). Nama kolom dinormalkan dari typo ERD: `employee_id`, `address`, `progress_weight`, `progress_before`, `progress_after`. Perubahan ini harus dibawa kembali ke ERD agar seragam. Semua model dan migration master sudah dibuat dan dijalankan; migration tabel transaksi belum dibuat.

## 2. Daftar tabel dan model

| Kelompok | Tabel | Model Eloquent |
| --- | --- | --- |
| Master | `users` | `User` |
| Master | `ms_company` | `MsCompany` |
| Master | `ms_employee` | `MsEmployee` |
| Master | `ms_client` | `MsClient` |
| Master (ekstensi) | `ms_client_contact` | `MsClientContact` |
| Master (ekstensi) | `ms_document` | `MsDocument` |
| Master | `ms_project_type` | `MsProjectType` |
| Master | `ms_project_workflow` | `MsProjectWorkflow` |
| Master | `ms_item_category` | `MsItemCategory` |
| Master | `ms_item` | `MsItem` |
| Master | `ms_unit` | `MsUnit` |
| Master | `ms_item_unit` | `MsItemUnit` |
| Master | `ms_bank_account` | `MsBankAccount` |
| Master | `ms_status` | `MsStatus` |
| Transaksi | `trans_sales_quotation_hd` | `TransSalesQuotationHd` |
| Transaksi | `trans_sales_quotation_dt` | `TransSalesQuotationDt` |
| Transaksi | `trans_sales_order_hd` | `TransSalesOrderHd` |
| Transaksi | `trans_sales_order_dt` | `TransSalesOrderDt` |
| Transaksi | `trans_sales_term` | `TransSalesTerm` |
| Transaksi | `trans_project_hd` | `TransProjectHd` |
| Transaksi | `trans_project_dt` | `TransProjectDt` |
| Transaksi | `trans_project_monitoring` | `TransProjectMonitoring` |
| Transaksi | `trans_sales_invoice_hd` | `TransSalesInvoiceHd` |
| Transaksi | `trans_sales_invoice_dt` | `TransSalesInvoiceDt` |
| Transaksi | `trans_sales_payment` | `TransSalesPayment` |
| Infrastruktur | `sys_code_sequence` | — |
| Infrastruktur | `sys_import_log` | `ImportLog` |
| Infrastruktur | `sys_error_log` | `ErrorLog` |

`hd` adalah header, `dt` adalah detail. Nama model di atas adalah pemetaan Laravel; nama tabel tetap mengikuti ERD. Karena tabel domain tidak mengikuti pluralisasi Laravel, setiap model domain perlu menetapkan `protected $table` secara eksplisit.

## 3. Base column

Setiap tabel domain memiliki `id` sebagai primary key `bigint unsigned`. ERD menampilkan `basemodel` sebagai pola kolom audit berikut; SRS menyebut `active` untuk tabel master, sedangkan tabel transaksi memakai audit tanpa `active`.

| Kolom | Tipe konsep | Master | Transaksi | Keterangan |
| --- | --- | --- | --- | --- |
| `active` | boolean | ya | tidak | Status aktif untuk pilihan data baru; default `true`. |
| `created_by` | bigint unsigned | ya | ya | ID user pembuat data. |
| `updated_by` | bigint unsigned, nullable | ya | ya | ID user terakhir mengubah data. |
| `deleted_by` | bigint unsigned, nullable | ya | ya | ID user yang melakukan soft delete. |
| `created_at` | timestamp | ya | ya | Waktu pembuatan. |
| `updated_at` | timestamp, nullable | ya | ya | Waktu perubahan terakhir. |
| `deleted_at` | timestamp, nullable | ya | ya | Soft delete. |

Khusus `trans_project_monitoring`, SRS hanya menetapkan `created_by` dan `created_at`; log bersifat append-only. Kolom audit pada tabel `users` perlu disesuaikan dengan migration autentikasi Laravel agar tidak menggandakan kolom bawaan. `created_by` tidak diberi default ID pada migration: nilainya berasal dari user login. Trait model `AuditedBySoftDelete` melempar `RuntimeException` jika tidak ada user login — di seeder atau console command, panggil `Auth::setUser($user)` sebelum menyimpan model yang memakai trait ini.

Gunakan `MasterTableColumns` untuk tabel master. Trait ini menambahkan `active`, tiga kolom audit, timestamps, dan soft delete. Polanya: trait dipasang pada kelas migration, kemudian `$this->addAuditColumns($table)` dipanggil setelah kolom domain. Tidak perlu membuat tabel `basemodel`:

```php
use App\Traits\MasterTableColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use MasterTableColumns;

    public function up(): void
    {
        Schema::create('ms_item_category', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 128);
            $table->text('description')->nullable();
            $this->addAuditColumns($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_item_category');
    }
};
```

Gunakan `TransactionTableColumns` pada tabel transaksi. `$this->addAuditColumns($table)` otomatis menambahkan `status_id` dengan foreign key ke `ms_status.id`, selain kolom audit, timestamps, dan soft delete. Jangan deklarasikan `status_id` dua kali. `id` dan kolom domain tetap ditulis pada migration masing-masing.

Tabel `trans_sales_quotation_dt`, `trans_sales_order_dt`, `trans_sales_invoice_dt`, dan tabel detail transaksi lain tanpa `status_id` memakai `$this->addAuditColumns($table, withStatus: false)`. Jangan memakai trait ini untuk `trans_project_monitoring`: buat hanya `created_by` dan `created_at` secara eksplisit sesuai aturan log append-only. Trait migration mengatur struktur kolom; validasi bahwa `ms_status.type` sesuai entitas tetap harus dilakukan di aplikasi.

Contoh untuk detail transaksi tanpa status dan untuk log monitoring:

```php
// Di migration trans_sales_quotation_dt yang memakai TransactionTableColumns:
Schema::create('trans_sales_quotation_dt', function (Blueprint $table) {
    $table->id();
    $table->foreignId('quotation_hd_id')->constrained('trans_sales_quotation_hd');
    // Tambahkan kolom detail lainnya.
    $this->addAuditColumns($table, withStatus: false);
});

// Di migration trans_project_monitoring, tanpa trait kolom:
Schema::create('trans_project_monitoring', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_hd_id')->constrained('trans_project_hd');
    $table->foreignId('project_dt_id')->constrained('trans_project_dt');
    // Tambahkan kolom log lainnya.
    $table->foreignId('created_by');
    $table->timestamp('created_at');
});
```

## 4. Detail tabel

Tabel berikut mencantumkan **kolom domain** dari ERD; `id` dan kolom umum pada §3 tidak diulang di setiap tabel. `Ya` pada kolom Null berarti ERD menandai `NULL`. Relasi foreign key mengacu ke `id` tabel tujuan.

### 4.1 `users`

Akun untuk login aplikasi. Model: `User`. Memakai tabel `users` bawaan Laravel (bukan `user` dari ERD).

| Kolom | Tipe | Null | Keterangan |
| --- | --- | --- | --- |
| `email` | varchar(128) | tidak | Email login; unik. |
| `password` | varchar(255) | tidak | Hash password. |
| `avatar` | varchar(255) | ya | Path avatar; nullable karena user baru belum tentu punya avatar. |
| `employee_id` | bigint unsigned | ya | FK ke `ms_employee.id`; unique karena satu employee maksimal satu user (SRS). |

Seluruh FK audit (`created_by`, `updated_by`, `deleted_by`) dan FK employee pada tabel domain mengarah ke `users.id`.

### 4.2 `ms_company`

Data company penerbit dokumen dan struktur induk. Model: `MsCompany`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode company, unik. |
| `name` | varchar(128) | tidak | Nama singkat. |
| `legal_name` | varchar(128) | tidak | Nama legal. |
| `email` | varchar(128) | tidak | Email company. |
| `phone` | varchar(18) | tidak | Nomor telepon. |
| `address` | text | ya | Alamat lengkap untuk identitas/kop surat; wajib saat membuat data baru. |
| `postal_code` | varchar(10) | ya | Kode pos untuk identitas/kop surat; wajib saat membuat data baru. |
| `logo` | varchar(255) | ya | Path logo pada disk `public`. |
| `description` | text | ya | Keterangan. |
| `parent_id` | bigint unsigned | ya | FK ke `ms_company.id`. |
| `pic_employee_id` | bigint unsigned | ya | FK ke `ms_employee.id`. |

Perusahaan utama adalah PT Sanitra Sapta Utama dengan ID 1, aktif, tanpa induk, dan tidak dapat dihapus. Semua perusahaan lain memakai `parent_id = 1`; PIC, jika diisi, harus karyawan dari perusahaan yang sama.

### 4.3 `ms_employee`

Karyawan dan PIC internal. Model: `MsEmployee`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode employee. |
| `name` | varchar(128) | tidak | Nama karyawan. |
| `position` | varchar(128) | tidak | Jabatan; input baru memilih Owner, Marketing, Teknis, atau Finance dari enum aplikasi. |
| `company_id` | bigint unsigned | tidak | FK ke `ms_company.id`. |
| `email` | varchar(128) | tidak | Email karyawan. |
| `phone` | varchar(18) | tidak | Nomor telepon. |

### 4.4 `ms_client`

Client dan PIC internal yang menangani relasi client. Model: `MsClient`. `email` dan `phone` adalah kontak umum perusahaan; orang yang dapat dihubungi dicatat di `ms_client_contact`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode client; unik menurut FR-03. |
| `name` | varchar(128) | tidak | Nama client. |
| `pic_employee_id` | bigint unsigned | ya | FK ke `ms_employee.id`. |
| `email` | varchar(128) | tidak | Email umum client. |
| `phone` | varchar(18) | tidak | Telepon umum client. |
| `address` | varchar(255) | tidak | Alamat. |
| `description` | text | ya | Keterangan. |

### 4.4a `ms_client_contact` (ekstensi)

Kontak orang di pihak client; berbeda dari `ms_client.pic_employee_id` yang menunjuk PIC internal Sanitra. Satu client dapat memiliki banyak kontak. Model: `MsClientContact`.

| Kolom | Tipe | Null | Keterangan |
| --- | --- | --- | --- |
| `client_id` | bigint unsigned | tidak | FK ke `ms_client.id`. |
| `name` | varchar(128) | tidak | Nama orang. |
| `position` | varchar(128) | ya | Jabatan. |
| `email` | varchar(128) | ya | Email langsung kontak. |
| `phone` | varchar(18) | ya | Telepon langsung kontak. |
| `is_primary` | boolean | tidak | Kontak utama; service menjaga maksimal satu per client. |

Tabel memakai `MasterTableColumns`, termasuk `active`, audit user, timestamps, dan soft delete. `SaveClientContactService` mengunci baris client dan mengosongkan penanda utama kontak lain ketika kontak utama baru disimpan.

### 4.4b `ms_document` (ekstensi)

Dokumen bersama untuk berbagai entitas. Model: `MsDocument`. Relasi dan UI client serta employee sudah diimplementasikan; project dapat memakai tabel yang sama setelah relasi serta aliasnya ditambahkan.

| Kolom | Tipe | Null | Keterangan |
| --- | --- | --- | --- |
| `ref_type` | varchar(32) | tidak | Alias pemilik dari morph map: `client` atau `employee`. Diisi relasi aplikasi. |
| `ref_id` | bigint unsigned | tidak | ID pemilik sesuai `ref_type`. Diisi relasi aplikasi. |
| `name` | varchar(128) | tidak | Nama dokumen, teks bebas. |
| `file_path` | varchar(255) | tidak | Path file pada disk private `local`. |
| `notes` | text | ya | Catatan dokumen. |

Tabel memakai `MasterTableColumns` dan index gabungan (`ref_type`, `ref_id`). Pasangan referensi polymorphic tidak memiliki foreign key database dinamis; relasi model dan akses file harus selalu dibatasi ke pemiliknya. Kolom `document_type` dan `mime_type` tidak disimpan. Model melindungi `ref_type` dan `ref_id` dari mass assignment; form tidak meminta keduanya.

### 4.5 `ms_project_type`

Jenis project yang menjadi dasar workflow tahap. Model: `MsProjectType`.

Seeder contoh mengisi tiga tipe: Kontraktor, Konsultan, dan Service dan Maintenance, beserta contoh tahap workflow masing-masing.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode tipe project. |
| `name` | varchar(128) | tidak | Nama tipe project. |
| `company_id` | bigint unsigned | ya | FK ke `ms_company.id`. |

### 4.6 `ms_project_workflow`

Template urutan dan bobot tahap untuk satu tipe project. Model: `MsProjectWorkflow`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `type_id` | bigint unsigned | tidak | FK ke `ms_project_type.id`. |
| `name` | varchar(255) | tidak | Nama tahap. |
| `sequence` | integer | tidak | Urutan tahap dalam tipe project. |
| `description` | text | ya | Keterangan. |
| `progress_weight` | integer | tidak | Bobot tahap. |

Jumlah `progress_weight` per `type_id` harus 100 sebelum workflow dipakai (BR-03).

### 4.7 `ms_item_category`

Kategori item. Model: `MsItemCategory`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode kategori. |
| `name` | varchar(128) | tidak | Nama kategori. |
| `description` | text | ya | Keterangan. |

### 4.8 `ms_item`

Katalog jasa yang dapat masuk quotation. Model: `MsItem`. Kategori item sudah membedakan kelompok layanan, sehingga tidak ada kolom tipe Item tersendiri.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `item_category_id` | bigint unsigned | tidak | FK ke `ms_item_category.id`. |
| `code` | varchar(50) | tidak | Kode item. |
| `name` | varchar(128) | tidak | Nama item. |
| `image` | varchar(255) | ya | Path gambar. |
| `description` | text | ya | Keterangan. |

### 4.9 `ms_unit`

Daftar satuan. Model: `MsUnit`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode unit. |
| `name` | varchar(128) | tidak | Nama unit. |
| `type` | varchar(50) | tidak | Jenis unit. |
| `description` | text | ya | Keterangan. |

### 4.10 `ms_item_unit`

Unit yang tersedia untuk item. Model: `MsItemUnit`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `item_id` | bigint unsigned | tidak | FK ke `ms_item.id`. |
| `unit_id` | bigint unsigned | tidak | FK ke `ms_unit.id`. |
| `is_base` | boolean | tidak | Menandai unit dasar item. |
| `conversion` | integer | tidak | Faktor konversi; unit dasar selalu `1`, unit lain minimal `1`. |

Pasangan (`item_id`, `unit_id`) unik. Unit pertama menjadi `is_base = true`; unit berikutnya `false`. Unit dasar tidak dapat diganti atau dihapus melalui UI. Item yang dibuat manual mendapat unit pertama dari halaman detail; import Item membuat Item dan unit dasarnya dalam satu transaksi. Baris unit yang soft deleted dipulihkan bila unit sama ditambahkan lagi agar tidak berbenturan dengan indeks unik.

### 4.11 `ms_bank_account`

Rekening tujuan pada invoice. Model: `MsBankAccount`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode rekening. |
| `name` | varchar(128) | tidak | Nama referensi rekening. |
| `bank_name` | varchar(128) | tidak | Nama bank. |
| `account_number` | varchar(128) | tidak | Nomor rekening; string agar nol awal tidak hilang. |
| `account_name` | varchar(128) | tidak | Nama pemilik rekening. |
| `description` | text | ya | Keterangan. |

### 4.12 `ms_status`

Status proses per tipe entitas. Model: `MsStatus`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `type` | varchar(128) | tidak | Tipe entitas: `quotation`, `order`, `order_payment`, `project`, `project_dt`, `term`, `invoice`, `payment`. |
| `code` | varchar(128) | tidak | Kode status stabil untuk logika aplikasi. |
| `name` | varchar(128) | tidak | Label status. |
| `description` | text | ya | Keterangan. |

Kombinasi (`type`, `code`) unik. Data awal berisi **31 status** sesuai SRS v1.3 bagian 6.1 (bertambah dari 25 di SRS v1.0 karena penambahan tipe `order`, `order_payment` dan perubahan status `project`, `project_dt`, `term`, `invoice`).

Daftar lengkap:

| type | code | name |
| --- | --- | --- |
| quotation | draft | Draft |
| quotation | sent | Sent |
| quotation | approved | Approved |
| quotation | rejected | Rejected |
| order | waiting_dp | Waiting DP |
| order | on_progress | On Progress |
| order | closed | Closed |
| order | cancelled | Cancelled |
| order_payment | unpaid | Unpaid |
| order_payment | partial | Partial |
| order_payment | paid | Paid |
| project | open | Open |
| project | on_progress | On Progress |
| project | completed | Completed |
| project | cancelled | Cancelled |
| project_dt | draft | Belum Dimulai |
| project_dt | on_progress | Sedang Dikerjakan |
| project_dt | completed | Selesai |
| project_dt | blocked | Terkendala |
| term | draft | Draft |
| term | pending | Pending |
| term | ready_to_bill | Ready to Bill |
| term | invoiced | Invoiced |
| term | paid | Paid |
| invoice | unpaid | Unpaid |
| invoice | partial | Partial |
| invoice | paid | Paid |
| invoice | void | Void |
| payment | pending | Pending |
| payment | confirmed | Confirmed |
| payment | rejected | Rejected |

**Catatan:** `Overdue` pada invoice adalah hasil perhitungan (`due_date < now()` + status `unpaid`/`partial`), bukan status tersimpan.

### 4.13 `trans_sales_quotation_hd`

Header quotation. Model: `TransSalesQuotationHd`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Nomor/kode quotation, format `QUO/YY/MM/0001`. |
| `transaction_date` | date | tidak | Tanggal quotation. |
| `client_id` | bigint unsigned | tidak | FK ke `ms_client.id`. |
| `project_type_id` | bigint unsigned | tidak | FK ke `ms_project_type.id`. |
| `company_id` | bigint unsigned | tidak | FK ke `ms_company.id`; penerbit. |
| `payment_type` | varchar(50) | tidak | Jenis/skema pembayaran. |
| `notes` | text | ya | Catatan. |
| `vat_type` | varchar(50) | tidak | Jenis PPN. |
| `vat_amount` | decimal(20,6) | tidak | Nilai PPN. |
| `discount_total` | decimal(20,6) | tidak | Total diskon. |
| `subtotal` | decimal(20,6) | tidak | Subtotal. |
| `grand_total` | decimal(20,6) | tidak | Total akhir. |
| `status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `quotation`. |

Detail item ada di `trans_sales_quotation_dt`, skema termin di `trans_sales_term`. Siklus revisi dikelola lewat `status_id` (`Draft` → `Sent` → `Approved`/`Rejected`; revisi `Sent` → `Draft`); tidak ada kolom nomor revisi di tabel (BR-12).

### 4.14 `trans_sales_quotation_dt`

Detail item quotation. Model: `TransSalesQuotationDt`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `quotation_hd_id` | bigint unsigned | tidak | FK ke `trans_sales_quotation_hd.id`. |
| `item_id` | bigint unsigned | tidak | FK ke `ms_item.id`. |
| `unit_id` | bigint unsigned | tidak | FK ke `ms_unit.id`. |
| `notes` | text | ya | Catatan item. |
| `qty` | integer | tidak | Jumlah. |
| `unit_price` | decimal(20,6) | tidak | Harga per unit saat quotation dibuat. |
| `discount` | decimal(20,6) | tidak | Diskon baris. |
| `total_price` | decimal(20,6) | tidak | Total baris. |

### 4.15 `trans_sales_order_hd`

Header Sales Order. Dibuat otomatis saat quotation Approved (BR-17). Model: `TransSalesOrderHd`.

| Kolom | Tipe | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Format `SO/YY/MM/0001`. |
| `transaction_date` | date | tidak | Tanggal SO = tanggal quotation Approved. |
| `quotation_hd_id` | bigint unsigned | tidak | FK ke `trans_sales_quotation_hd.id`; **unique** (satu quotation → satu SO). |
| `client_id` | bigint unsigned | tidak | Disalin dari quotation. |
| `project_type_id` | bigint unsigned | tidak | Disalin dari quotation. |
| `company_id` | bigint unsigned | tidak | Disalin dari quotation. |
| `payment_type` | varchar(50) | tidak | Disalin dari quotation. |
| `vat_type` | varchar(50) | tidak | Disalin dari quotation. |
| `vat_amount` | decimal(20,6) | tidak | Disalin dari quotation. |
| `discount_total` | decimal(20,6) | tidak | Disalin dari quotation. |
| `subtotal` | decimal(20,6) | tidak | Disalin dari quotation. |
| `grand_total` | decimal(20,6) | tidak | Disalin dari quotation. |
| `notes` | text | ya | Catatan tambahan. |
| `status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `order`. |
| `payment_status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `order_payment`. |

**Catatan migration:** `TransactionTableColumns::addAuditColumns($table)` menambahkan `status_id` otomatis. Kolom `payment_status_id` ditambah manual setelah trait: `$table->foreignId('payment_status_id')->constrained('ms_status');`.

### 4.16 `trans_sales_order_dt`

Snapshot item dari `trans_sales_quotation_dt`. Model: `TransSalesOrderDt`.

| Kolom | Tipe | Null | Keterangan |
| --- | --- | --- | --- |
| `order_hd_id` | bigint unsigned | tidak | FK ke `trans_sales_order_hd.id`. |
| `quotation_dt_id` | bigint unsigned | tidak | FK ke `trans_sales_quotation_dt.id` (sumber salinan). |
| `item_id` | bigint unsigned | tidak | FK ke `ms_item.id`. |
| `unit_id` | bigint unsigned | tidak | FK ke `ms_unit.id`. |
| `qty` | integer | tidak | Disalin. |
| `unit_price` | decimal(20,6) | tidak | Disalin. |
| `discount` | decimal(20,6) | tidak | Disalin. |
| `total_price` | decimal(20,6) | tidak | Disalin. |
| `notes` | text | ya | Disalin. |

Memakai `addAuditColumns($table, withStatus: false)`.

### 4.17 `trans_sales_term`

Termin yang disepakati. Disusun di quotation, dikelola di Sales Order setelah Approved (BR-16). Model: `TransSalesTerm`.

| Kolom | Tipe | Null | Keterangan |
| --- | --- | --- | --- |
| `quotation_hd_id` | bigint unsigned | tidak | FK ke `trans_sales_quotation_hd.id` (lokasi penyusunan termin). |
| `order_hd_id` | bigint unsigned | **ya** | FK ke `trans_sales_order_hd.id`; **nullable** sampai SO dibuat (BR-16). |
| `sequence` | integer | tidak | Urutan termin. |
| `term_name` | varchar(128) | tidak | Nama termin. |
| `percentage` | integer | tidak | Persentase nilai quotation. |
| `amount` | decimal(20,6) | tidak | Nilai termin; `0` saat Draft, dihitung dan dikunci saat SO dibuat (BR-02). |
| `progress_threshold` | integer | tidak | Minimal progress agar siap tagih. Threshold `0` = DP (BR-04). |
| `notes` | text | ya | Catatan termin. |
| `status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `term`. |

Total `percentage` setiap quotation harus 100 (BR-01); (`quotation_hd_id`, `sequence`) unik.

### 4.18 `trans_project_hd`

Header project yang berasal dari Sales Order (BR-15). Model: `TransProjectHd`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode project, format `PRO/YY/MM/0001`. |
| `transaction_date` | date | tidak | Tanggal transaksi (= tanggal project dibuat). |
| `order_hd_id` | bigint unsigned | tidak | FK ke `trans_sales_order_hd.id`; **unique** (satu SO → satu Project, BR-17). |
| `client_id` | bigint unsigned | tidak | Disalin dari SO. |
| `company_id` | bigint unsigned | tidak | Disalin dari SO. |
| `project_type_id` | bigint unsigned | tidak | Disalin dari SO. |
| `pic_employee_id` | bigint unsigned | tidak | FK ke `ms_employee.id` (PIC project). |
| `project_name` | varchar(128) | tidak | Nama project. |
| `start_date` | date | tidak | Tanggal mulai. |
| `end_date` | date | tidak | Tanggal selesai / rencana selesai. |
| `progress_percentage` | integer | tidak | Progress dari bobot tahap Completed (BR-03). |
| `notes` | text | ya | Catatan project. |
| `status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `project`. |

Project dibuat setelah invoice DP Paid (jika ada DP) atau bersamaan dengan SO (jika tidak ada DP). Status awal `open`.

### 4.19 `trans_project_dt`

Snapshot tahap project yang dibuat dari master workflow. Model: `TransProjectDt`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `project_hd_id` | bigint unsigned | tidak | FK ke `trans_project_hd.id`. |
| `project_workflow_id` | bigint unsigned | tidak | FK ke `ms_project_workflow.id`. |
| `sequence` | integer | tidak | Urutan tahap. |
| `work_name` | varchar(128) | tidak | Nama tahap pada project. |
| `progress_weight` | integer | tidak | Bobot tahap. |
| `status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `project_dt`. |
| `start_date` | date | ya | Tanggal mulai tahap. |
| `end_date` | date | ya | Tanggal selesai tahap. |
| `notes` | text | ya | Catatan. |
| `editable` | boolean | tidak | Penanda tahap tidak terkunci. |

Saat status tahap `blocked` (label **Terkendala**), `notes` wajib berisi penjelasan kendala.

### 4.20 `trans_project_monitoring`

Log perubahan status/progress tahap project. Model: `TransProjectMonitoring`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `project_hd_id` | bigint unsigned | tidak | FK ke `trans_project_hd.id`. |
| `project_dt_id` | bigint unsigned | tidak | FK ke `trans_project_dt.id`. |
| `status_before_id` | bigint unsigned | tidak | FK ke `ms_status.id` sebelum perubahan. |
| `status_after_id` | bigint unsigned | tidak | FK ke `ms_status.id` setelah perubahan. |
| `log_type` | varchar(50) | tidak | Jenis log, termasuk perubahan/override/rework. |
| `progress_before` | integer | tidak | Progress sebelumnya. |
| `progress_after` | integer | tidak | Progress sesudahnya. |
| `notes` | text | ya | Alasan atau keterangan. |

Log tidak diedit atau dihapus; koreksi dibuat sebagai log baru (BR-10). Kolom audit khusus: `created_by` dan `created_at`.

### 4.21 `trans_sales_invoice_hd`

Header invoice/tagihan. Model: `TransSalesInvoiceHd`.

| Kolom | Tipe | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Nomor invoice, format `INV/YY/MM/0001`. |
| `transaction_date` | date | tidak | Tanggal invoice. |
| `order_hd_id` | bigint unsigned | tidak | FK ke `trans_sales_order_hd.id`; **wajib** (invoice selalu dari SO). |
| `project_hd_id` | bigint unsigned | **ya** | FK ke `trans_project_hd.id`; **nullable** untuk invoice DP yang dibuat sebelum project ada. |
| `quotation_hd_id` | bigint unsigned | tidak | FK ke `trans_sales_quotation_hd.id`; disalin dari SO untuk referensi. |
| `client_id` | bigint unsigned | tidak | Disalin dari SO. |
| `company_id` | bigint unsigned | tidak | Disalin dari SO. |
| `bank_account_id` | bigint unsigned | tidak | FK ke `ms_bank_account.id`. |
| `due_date` | date | tidak | Jatuh tempo. |
| `subtotal` | decimal(20,6) | tidak | Σ amount termin yang ditagih. |
| `vat_type` | varchar(50) | tidak | Disalin dari SO. |
| `vat_amount` | decimal(20,6) | tidak | Dihitung dari subtotal + vat_type. |
| `grand_total` | decimal(20,6) | tidak | subtotal + vat_amount. |
| `notes` | text | ya | Catatan. |
| `status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `invoice`. |

### 4.22 `trans_sales_invoice_dt`

Termin yang dimasukkan ke invoice. Model: `TransSalesInvoiceDt`.

| Kolom | Tipe | Null | Keterangan |
| --- | --- | --- | --- |
| `invoice_hd_id` | bigint unsigned | tidak | FK ke `trans_sales_invoice_hd.id`. |
| `sales_term_id` | bigint unsigned | tidak | FK ke `trans_sales_term.id`. |
| `sequence` | integer | tidak | Urutan baris. |
| `description` | text | ya | Keterangan tambahan. |
| `percentage` | integer | tidak | Snapshot `percentage` termin saat invoice dibuat. |
| `amount` | decimal(20,6) | tidak | Snapshot `amount` termin saat invoice dibuat. |

Satu termin hanya boleh masuk satu invoice **aktif** (status ≠ `void`). Validasi dilakukan di `CreateInvoiceFromTermsService` dengan `lockForUpdate()`; unique constraint parsial sulit di-enforce di MySQL/MariaDB (FR-31, FR-34, BR-09).

### 4.23 `trans_sales_payment`

Pembayaran dan bukti transfer invoice. Model: `TransSalesPayment`.

| Kolom | Tipe | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode pembayaran, format `PAY/YY/MM/0001`. |
| `transaction_date` | date | tidak | Tanggal pembayaran. |
| `invoice_hd_id` | bigint unsigned | tidak | FK ke `trans_sales_invoice_hd.id`. |
| `amount` | decimal(20,6) | tidak | Nominal pembayaran. |
| `payment_method` | varchar(128) | tidak | Metode pembayaran (enum PHP `PaymentMethod`: `cash`, `transfer`, `giro`, dll.). |
| `status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `payment`. |
| `notes` | text | ya | Catatan. |
| `reference_no` | varchar(50) | ya | Nomor referensi/transaksi bank; nullable (mis. cash). |
| `payment_proof` | varchar(255) | ya | Path file bukti transfer; nullable saat payment belum di-upload buktinya. |

Hanya payment berstatus Confirmed yang dihitung ke pelunasan invoice.

### 4.24 `sys_import_log`

Log hasil import Excel. Bersifat append-only — tidak ada `updated_at`, `deleted_at`, dan tidak memakai trait audit. Model: `ImportLog`.

| Kolom | Tipe | Null | Keterangan |
| --- | --- | --- | --- |
| `imported_by` | bigint unsigned | tidak | FK ke `users.id`. |
| `entity` | varchar(64) | tidak | Nama entitas yang diimport (mis. `MsItem`, `MsEmployee`). |
| `filename` | varchar(255) | tidak | Nama file yang diupload. |
| `total_rows` | int unsigned | tidak | Total baris data pada file. |
| `success_count` | int unsigned | tidak | Baris yang berhasil disimpan. |
| `failed_count` | int unsigned | tidak | Baris yang gagal. |
| `errors` | json | ya | Detail error per baris; null jika tidak ada error. |
| `status` | varchar(16) | tidak | Status proses (`done`). Default `done`. |
| `created_at` | timestamp | tidak | Waktu import selesai; default `CURRENT_TIMESTAMP`. |

### 4.25 `sys_code_sequence`

Counter kode otomatis untuk semua entitas yang membutuhkan kode unik. Dikelola oleh `GenerateCodeService` menggunakan `SELECT ... FOR UPDATE` untuk mencegah race condition. Tidak memiliki model Eloquent; diakses langsung via `DB::table`. Detail implementasi di `docs/architecture.md` §6.

Ada dua format kode yang ditentukan oleh `AutoCodePrefix::format()`:

| Format | Contoh | `year` | `month` | Reset |
| --- | --- | --- | --- | --- |
| Master (`CodeFormat::Master`) | `COM0002`, `ITM0011` | NULL | NULL | Tidak pernah |
| Transaksi (`CodeFormat::Transaction`) | `QUO/26/10/0001` | tahun | bulan | Tiap bulan |

Prefix master (`COM`, `EMP`, `CLI`, `PTY`, `IC`, `ITM`, `BNK`) menyimpan `year = NULL` dan `month = NULL` karena nomornya berjalan terus tanpa reset. Prefix transaksi (`QUO`, `SO`, `PRO`, `INV`, `PAY`) membuat baris baru tiap bulan sehingga penomoran mulai dari 1 setiap awal bulan.

| Kolom | Tipe | Null | Keterangan |
| --- | --- | --- | --- |
| `prefix` | varchar(10) | tidak | Prefix kode entitas (e.g. `COM`, `QUO`). |
| `year` | smallint unsigned | ya | Tahun periode; NULL untuk master. |
| `month` | tinyint unsigned | ya | Bulan periode; NULL untuk master. |
| `last_number` | int unsigned | tidak | Nomor urut terakhir yang digunakan. Default `0`. |
| `created_at` | timestamp | ya | Waktu baris pertama kali dibuat. |
| `updated_at` | timestamp | ya | Waktu terakhir nomor di-increment. |

Constraint unique `uniq_prefix_period` pada `(prefix, year, month)` menjamin satu counter per prefix per periode.

### 4.26 `sys_error_log`

Log exception aplikasi. Bersifat append-only — tidak ada `updated_at`, `deleted_at`, dan tidak memakai trait audit. Model: `ErrorLog`. Detail implementasi di `docs/error-log.md`.

| Kolom | Tipe | Null | Keterangan |
| --- | --- | --- | --- |
| `level` | varchar(20) | tidak | Tingkat error (`error`, `critical`, `warning`). |
| `message` | text | tidak | Pesan exception. |
| `url` | varchar(500) | ya | URL saat exception terjadi. |
| `method` | varchar(10) | ya | HTTP method (`GET`, `POST`, dst.). |
| `user_id` | bigint unsigned | ya | FK ke `users.id`; null jika dari console/sistem. |
| `ip_address` | varchar(45) | ya | IP address request. |
| `trace` | json | ya | 10 frame stack trace pertama; tiap frame berisi `call`, `file`, `line`. |
| `context` | json | ya | Metadata tambahan: `exception`, `file`, `line`, `http_code`. |
| `created_at` | timestamp | tidak | Waktu exception terjadi. |

## 5. Relasi antar tabel

| Induk | Anak / foreign key | Kardinalitas |
| --- | --- | --- |
| `ms_company` | `ms_company.parent_id` | satu induk memiliki banyak company anak |
| `ms_company` | `ms_employee.company_id`, `ms_project_type.company_id` | satu company memiliki banyak employee/tipe project |
| `ms_employee` | `users.employee_id`, `ms_company.pic_employee_id`, `ms_client.pic_employee_id`, `trans_project_hd.pic_employee_id` | satu employee dapat menjadi PIC; relasi user maksimal satu |
| `ms_client` | `ms_client_contact.client_id` | satu client memiliki banyak kontak |
| `ms_client` (alias `client`) | `ms_document.ref_type` + `ms_document.ref_id` | satu client memiliki banyak dokumen lewat relasi polymorphic |
| `ms_employee` (alias `employee`) | `ms_document.ref_type` + `ms_document.ref_id` | satu employee memiliki banyak dokumen lewat relasi polymorphic |
| `ms_project_type` | `ms_project_workflow.type_id`, quotation/order/project `project_type_id` | satu tipe memiliki banyak template tahap dan transaksi |
| `ms_item_category` | `ms_item.item_category_id` | satu kategori memiliki banyak item |
| `ms_item`, `ms_unit` | `ms_item_unit.item_id`, `ms_item_unit.unit_id` | banyak-ke-banyak melalui konversi unit |
| `trans_sales_quotation_hd` | `trans_sales_quotation_dt.quotation_hd_id`, `trans_sales_term.quotation_hd_id` | satu quotation memiliki banyak item dan termin |
| `trans_sales_quotation_hd` | `trans_sales_order_hd.quotation_hd_id` | satu quotation Approved memiliki tepat satu Sales Order (unique, BR-17) |
| `trans_sales_order_hd` | `trans_sales_order_dt.order_hd_id`, `trans_sales_term.order_hd_id` | satu Sales Order memiliki banyak item (salinan) dan termin |
| `trans_sales_order_hd` | `trans_project_hd.order_hd_id` | satu Sales Order memiliki maksimal satu Project (unique, BR-17) |
| `trans_sales_order_hd` | `trans_sales_invoice_hd.order_hd_id` | satu Sales Order dapat memiliki banyak invoice (DP + progress + pelunasan) |
| `trans_project_hd` | `trans_project_dt.project_hd_id`, `trans_project_monitoring.project_hd_id`, `trans_sales_invoice_hd.project_hd_id` | satu project memiliki banyak tahap, log, dan invoice (invoice DP boleh belum punya project) |
| `trans_sales_invoice_hd` | `trans_sales_invoice_dt.invoice_hd_id`, `trans_sales_payment.invoice_hd_id` | satu invoice memiliki banyak baris termin dan pembayaran |
| `trans_sales_invoice_dt` | `trans_sales_term.id` via `sales_term_id` | satu termin hanya boleh masuk satu invoice aktif |
| `ms_status` | seluruh `status_id`, `payment_status_id`, `status_before_id`, `status_after_id` | status dibatasi menurut `type` entitas |

Relasi status pada aplikasi harus memeriksa `type` yang sesuai. Foreign key ke `ms_status.id` saja tidak menjamin tipe status benar. Khusus `trans_sales_order_hd` memiliki dua FK ke `ms_status`: `status_id` (tipe `order`) dan `payment_status_id` (tipe `order_payment`).

## 6. Urutan migration

1. Tabel autentikasi Laravel (`users`) bawaan Laravel. Cache dan jobs bawaan Laravel tetap terpisah dari 25 tabel domain ERD.
2. `ms_company` dengan `parent_id`, tetapi tunda foreign key `pic_employee_id`.
3. `ms_employee`, lalu tambahkan foreign key `ms_company.pic_employee_id` dan FK `users.employee_id`. Urutan dua langkah ini menghindari siklus company ↔ employee.
4. `ms_client`, `ms_project_type`, `ms_project_workflow`.
5. `ms_item_category`, `ms_unit`, `ms_item`, `ms_item_unit`.
6. `ms_bank_account`, `ms_status`.
7. `trans_sales_quotation_hd`, `trans_sales_quotation_dt`.
8. `trans_sales_order_hd`, `trans_sales_order_dt`.
9. `trans_sales_term` (FK ke `trans_sales_quotation_hd` wajib, FK ke `trans_sales_order_hd` nullable).
10. `trans_project_hd` (FK ke `trans_sales_order_hd`), `trans_project_dt`, `trans_project_monitoring`.
11. `trans_sales_invoice_hd` (FK ke `trans_sales_order_hd` wajib, FK ke `trans_project_hd` nullable), `trans_sales_invoice_dt`, `trans_sales_payment`.

Migration ekstensi `ms_client_contact` dan `ms_document` berjalan setelah `ms_client`. Tabel infrastruktur `sys_code_sequence` dan `sys_import_log` berjalan setelah tabel master; `sys_error_log` berjalan setelah `users`. Semua migration master dan infrastruktur sudah dijalankan; migration tabel transaksi (langkah 7–11) belum dibuat.

Migration `down()` menghapus foreign key tambahan dan tabel dalam urutan terbalik. Proyek memakai `users` dari bawaan Laravel; nama `user` pada ERD tidak dipakai.

## 7. Index dan constraint utama

- Unique pada `users.email`, kode master yang merupakan identifier, (`ms_status.type`, `ms_status.code`), `trans_sales_order_hd.quotation_hd_id`, `trans_project_hd.order_hd_id`, (`ms_project_workflow.type_id`, `sequence`), (`trans_sales_term.quotation_hd_id`, `sequence`), dan (`ms_item_unit.item_id`, `unit_id`).
- Index pada seluruh foreign key, kode dokumen, status + tanggal transaksi, `trans_sales_invoice_hd.due_date`, serta `trans_sales_payment.invoice_hd_id` untuk daftar outstanding dan histori.
- `ms_document` memiliki index (`ref_type`, `ref_id`); tidak ada foreign key dinamis untuk pemilik polymorphic. `ms_client_contact.client_id` memakai foreign key biasa.
- Validasi rentang 0–100 untuk persentase termin, threshold, bobot workflow/tahap, dan progress project. Jumlah lintas baris seperti total termin atau total bobot workflow, serta tepat satu unit dasar per item, divalidasi saat transaksi disimpan.
- `trans_sales_invoice_dt.sales_term_id` tidak dapat dibuat unique global jika invoice Void tetap disimpan dan termin dapat ditagih ulang. Saat pembuatan invoice, kunci termin dengan `lockForUpdate()` dan validasi bahwa belum ada invoice aktif (status ≠ `void`) yang memakainya.
- `trans_sales_term.order_hd_id` nullable: NULL saat termin masih di quotation Draft/Sent; di-set otomatis oleh `CreateSalesOrderFromQuotationService` saat Approved.
- Nilai Rupiah pakai `decimal(20, 6)` untuk seluruh kolom nominal (subtotal, grand_total, amount, unit_price, discount, vat_amount, dsb.). ERD menulis `INT`; abaikan dan ikut rekomendasi dokumen ini. Konsisten di semua migration, model cast (`decimal:6`), dan form input.
- Soft delete bukan pengganti status bisnis Void, Rejected, Revised, atau Cancelled. Histori transaksi dan log monitoring harus tetap dapat ditelusuri.

## 8. Pola model Eloquent dan migration

Model domain perlu nama tabel eksplisit serta relasi dengan nama foreign key dari ERD. Trait migration di §3 tidak dipasang pada model. Model master dan model transaksi yang memiliki kolom audit lengkap dapat memakai trait model `AuditedBySoftDelete` yang sudah ada, bersama `SoftDeletes`. Contoh model master:

```php
namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsItem extends Model
{
    use AuditedBySoftDelete, SoftDeletes;

    protected $table = 'ms_item';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MsItemCategory::class, 'item_category_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(MsItemUnit::class, 'item_id');
    }
}
```

Contoh migration transaksi berstatus. Pastikan migration `ms_status` berjalan sebelum migration ini:

```php
use App\Traits\TransactionTableColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use TransactionTableColumns;

    public function up(): void
    {
        Schema::create('trans_sales_term', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_hd_id')->constrained('trans_sales_quotation_hd');
            $table->foreignId('order_hd_id')->nullable()->constrained('trans_sales_order_hd');
            $table->unsignedInteger('sequence');
            $table->string('term_name', 128);
            $table->unsignedTinyInteger('percentage');
            $table->decimal('amount', 20, 6)->default(0);
            $table->unsignedTinyInteger('progress_threshold');
            $table->text('notes')->nullable();
            $this->addAuditColumns($table);

            $table->unique(['quotation_hd_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trans_sales_term');
    }
};
```

Pada model `TransSalesTerm`, gunakan `AuditedBySoftDelete` dan `SoftDeletes`, serta `protected $table = 'trans_sales_term';`. Untuk `TransProjectMonitoring`, jangan pasang trait model itu karena event update/delete tidak sesuai sifat log yang tidak boleh diubah. File gambar dan bukti pembayaran disimpan di storage; database hanya menyimpan path.

### Catatan penamaan dari ERD

ERD menulis typo `employe_id`, `addres`, `progres_weight`, `progres_before`, dan `progres_after`. Dokumen ini menormalkan ejaan menjadi `employee_id`, `address`, `progress_weight`, `progress_before`, dan `progress_after`. Perbaikan harus dibawa kembali ke ERD agar seragam dengan migration, model, form, dan query.

### Role dan permission

Role dan permission memakai [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission). Setup dan assignment dilakukan di Filament, bukan dari migration domain ini. Tabel `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, dan `role_has_permissions` dibuat oleh migration bawaan paket.

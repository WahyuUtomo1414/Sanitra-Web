# Database Aplikasi Web Sanitra

**Status:** Semua migration master dan infrastruktur sudah dijalankan; tabel transaksi belum dibuat · **Acuan:** [ERD Sanitra](../../../Berkas/Sanitra-ERD.drawio%20%282%29.png), [SRS Sanitra](../../../Berkas/SRS_Aplikasi_Web_Sanitra.pdf) · **Terkait:** [PRD](prd.md)

## 1. Ringkasan

Dokumen ini menjabarkan struktur database pada ERD Sanitra menjadi panduan awal untuk model Eloquent dan migration Laravel 13. ERD memuat 21 tabel domain: 12 master (termasuk `users`) dan 9 transaksi. Aplikasi menambahkan `ms_client_contact` dan `ms_document` di luar ERD awal, serta tiga tabel infrastruktur: `sys_code_sequence`, `sys_import_log`, dan `sys_error_log`. `basemodel` pada ERD adalah kumpulan kolom umum, bukan tabel yang dibuat di database.

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
| Transaksi | `trans_quotation_hd` | `TransQuotationHd` |
| Transaksi | `trans_quotation_dt` | `TransQuotationDt` |
| Transaksi | `trans_quotation_term` | `TransQuotationTerm` |
| Transaksi | `trans_project_hd` | `TransProjectHd` |
| Transaksi | `trans_project_dt` | `TransProjectDt` |
| Transaksi | `trans_project_monitoring` | `TransProjectMonitoring` |
| Transaksi | `trans_invoice_hd` | `TransInvoiceHd` |
| Transaksi | `trans_invoice_dt` | `TransInvoiceDt` |
| Transaksi | `trans_payment` | `TransPayment` |
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

Tabel `trans_quotation_dt`, `trans_invoice_dt`, dan tabel detail transaksi lain tanpa `status_id` memakai `$this->addAuditColumns($table, withStatus: false)`. Jangan memakai trait ini untuk `trans_project_monitoring`: buat hanya `created_by` dan `created_at` secara eksplisit sesuai aturan log append-only. Trait migration mengatur struktur kolom; validasi bahwa `ms_status.type` sesuai entitas tetap harus dilakukan di aplikasi.

Contoh untuk detail transaksi tanpa status dan untuk log monitoring:

```php
// Di migration trans_quotation_dt yang memakai TransactionTableColumns:
Schema::create('trans_quotation_dt', function (Blueprint $table) {
    $table->id();
    $table->foreignId('quotation_hd_id')->constrained('trans_quotation_hd');
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
| `type` | varchar(128) | tidak | Tipe entitas: quotation, project, project_dt, term, invoice, payment. |
| `code` | varchar(128) | tidak | Kode status stabil untuk logika aplikasi. |
| `name` | varchar(128) | tidak | Label status. |
| `description` | text | ya | Keterangan. |

Kombinasi (`type`, `code`) unik. Data awal berisi 25 status sesuai SRS bagian 6.1.

### 4.13 `trans_quotation_hd`

Header quotation. Model: `TransQuotationHd`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Nomor/kode quotation. |
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

Detail item ada di `trans_quotation_dt`, skema termin di `trans_quotation_term`. Siklus revisi dikelola lewat `status_id` (`Draft` → `Sent` → `Approved`/`Rejected`); tidak ada kolom nomor revisi di tabel.

### 4.14 `trans_quotation_dt`

Detail item quotation. Model: `TransQuotationDt`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `quotation_hd_id` | bigint unsigned | tidak | FK ke `trans_quotation_hd.id`. |
| `item_id` | bigint unsigned | tidak | FK ke `ms_item.id`. |
| `unit_id` | bigint unsigned | tidak | FK ke `ms_unit.id`. |
| `notes` | text | ya | Catatan item. |
| `qty` | integer | tidak | Jumlah. |
| `unit_price` | decimal(20,6) | tidak | Harga per unit saat quotation dibuat. |
| `discount` | decimal(20,6) | tidak | Diskon baris. |
| `total_price` | decimal(20,6) | tidak | Total baris. |

### 4.15 `trans_quotation_term`

Termin yang disepakati dalam quotation. Model: `TransQuotationTerm`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `quotation_hd_id` | bigint unsigned | tidak | FK ke `trans_quotation_hd.id`. |
| `sequence` | integer | tidak | Urutan termin. |
| `term_name` | varchar(128) | tidak | Nama termin. |
| `percentage` | integer | tidak | Persentase nilai quotation. |
| `amount` | decimal(20,6) | tidak | Nilai termin; dibekukan saat Approved. |
| `progress_threshold` | integer | tidak | Minimal progress agar siap tagih. |
| `notes` | text | ya | Catatan termin. |
| `status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `term`. |

Total `percentage` setiap quotation harus 100; (`quotation_hd_id`, `sequence`) unik.

### 4.16 `trans_project_hd`

Header project yang berasal dari quotation Approved. Model: `TransProjectHd`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode project. |
| `transaction_date` | date | tidak | Tanggal transaksi. |
| `quotation_hd_id` | bigint unsigned | tidak | FK ke quotation; unik untuk satu project per quotation. |
| `client_id` | bigint unsigned | tidak | FK ke `ms_client.id`. |
| `company_id` | bigint unsigned | tidak | FK ke `ms_company.id`. |
| `project_type_id` | bigint unsigned | tidak | FK ke `ms_project_type.id`. |
| `pic_employee_id` | bigint unsigned | tidak | FK ke `ms_employee.id`. |
| `project_name` | varchar(128) | tidak | Nama project. |
| `start_date` | date | tidak | Tanggal mulai. |
| `end_date` | date | tidak | Tanggal selesai/rencana selesai pada ERD. |
| `progress_percentage` | integer | tidak | Progress dari bobot tahap Completed. |
| `notes` | text | ya | Catatan project. |
| `status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `project`. |

### 4.17 `trans_project_dt`

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

### 4.18 `trans_project_monitoring`

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

### 4.19 `trans_invoice_hd`

Header invoice/tagihan. Model: `TransInvoiceHd`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Nomor invoice. |
| `transaction_date` | date | tidak | Tanggal invoice. |
| `project_hd_id` | bigint unsigned | tidak | FK ke `trans_project_hd.id`. |
| `quotation_hd_id` | bigint unsigned | tidak | FK ke `trans_quotation_hd.id`. |
| `client_id` | bigint unsigned | tidak | FK ke `ms_client.id`. |
| `company_id` | bigint unsigned | tidak | FK ke `ms_company.id`; penerbit. |
| `bank_account_id` | bigint unsigned | tidak | FK ke `ms_bank_account.id`. |
| `due_date` | date | tidak | Jatuh tempo. |
| `subtotal` | decimal(20,6) | tidak | Subtotal tagihan. |
| `vat_type` | varchar(50) | tidak | Jenis PPN. |
| `vat_amount` | decimal(20,6) | tidak | Nilai PPN. |
| `grand_total` | decimal(20,6) | tidak | Total tagihan. |
| `notes` | text | ya | Catatan. |
| `status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `invoice`. |

### 4.20 `trans_invoice_dt`

Termin yang dimasukkan ke invoice. Model: `TransInvoiceDt`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `invoice_hd_id` | bigint unsigned | tidak | FK ke `trans_invoice_hd.id`. |
| `quotation_term_id` | bigint unsigned | tidak | FK ke `trans_quotation_term.id`. |
| `sequence` | integer | tidak | Urutan baris. |
| `description` | text | ya | Keterangan. |
| `percentage` | integer | tidak | Persentase termin saat invoice dibuat. |
| `amount` | decimal(20,6) | tidak | Nilai termin saat invoice dibuat. |

Satu termin hanya boleh masuk satu invoice **aktif**. Invoice Void tetap tersimpan dalam histori dan termin dapat masuk invoice baru (FR-31, FR-34).

### 4.21 `trans_payment`

Pembayaran dan bukti transfer invoice. Model: `TransPayment`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode pembayaran. |
| `transaction_date` | date | tidak | Tanggal pembayaran. |
| `invoice_hd_id` | bigint unsigned | tidak | FK ke `trans_invoice_hd.id`. |
| `amount` | decimal(20,6) | tidak | Nominal pembayaran. |
| `payment_method` | varchar(128) | tidak | Metode pembayaran. |
| `status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `payment`. |
| `notes` | text | ya | Catatan. |
| `reference_no` | varchar(50) | ya | Nomor referensi; nullable karena tidak semua metode punya (mis. cash). |
| `payment_proof` | varchar(255) | ya | Path file bukti transfer; nullable saat payment belum di-upload buktinya. |

Hanya payment berstatus Confirmed yang dihitung ke pelunasan invoice.

### 4.22 `sys_import_log`

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

### 4.23 `sys_code_sequence`

Sequence counter untuk generator kode otomatis. Tidak memiliki model Eloquent; diakses langsung via `DB::table`. Detail implementasi di `docs/architecture.md` §6.

### 4.24 `sys_error_log`

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
| `ms_project_type` | `ms_project_workflow.type_id`, quotation/project `project_type_id` | satu tipe memiliki banyak template tahap dan transaksi |
| `ms_item_category` | `ms_item.item_category_id` | satu kategori memiliki banyak item |
| `ms_item`, `ms_unit` | `ms_item_unit.item_id`, `ms_item_unit.unit_id` | banyak-ke-banyak melalui konversi unit |
| `trans_quotation_hd` | `trans_quotation_dt.quotation_hd_id`, `trans_quotation_term.quotation_hd_id` | satu quotation memiliki banyak item dan termin |
| `trans_quotation_hd` | `trans_project_hd.quotation_hd_id` | satu quotation Approved memiliki maksimal satu project |
| `trans_project_hd` | `trans_project_dt.project_hd_id`, `trans_project_monitoring.project_hd_id`, `trans_invoice_hd.project_hd_id` | satu project memiliki banyak tahap, log, dan invoice |
| `trans_invoice_hd` | `trans_invoice_dt.invoice_hd_id`, `trans_payment.invoice_hd_id` | satu invoice memiliki banyak termin dan pembayaran |
| `ms_status` | seluruh `status_id`, `status_before_id`, `status_after_id` | status dibatasi menurut `type` entitas |

Relasi status pada aplikasi harus memeriksa `type` yang sesuai. Foreign key ke `ms_status.id` saja tidak menjamin tipe status benar.

## 6. Urutan migration

1. Tabel autentikasi Laravel (`users`) bawaan Laravel. Cache dan jobs bawaan Laravel tetap terpisah dari 21 tabel domain ERD.
2. `ms_company` dengan `parent_id`, tetapi tunda foreign key `pic_employee_id`.
3. `ms_employee`, lalu tambahkan foreign key `ms_company.pic_employee_id` dan FK `users.employee_id`. Urutan dua langkah ini menghindari siklus company ↔ employee.
4. `ms_client`, `ms_project_type`, `ms_project_workflow`.
5. `ms_item_category`, `ms_unit`, `ms_item`, `ms_item_unit`.
6. `ms_bank_account`, `ms_status`.
7. `trans_quotation_hd`, `trans_quotation_dt`, `trans_quotation_term`.
8. `trans_project_hd`, `trans_project_dt`, `trans_project_monitoring`.
9. `trans_invoice_hd`, `trans_invoice_dt`, `trans_payment`.

Migration ekstensi `ms_client_contact` dan `ms_document` berjalan setelah `ms_client`. Tabel infrastruktur `sys_code_sequence` dan `sys_import_log` berjalan setelah tabel master; `sys_error_log` berjalan setelah `users`. Semua migration sudah dijalankan.

Migration `down()` menghapus foreign key tambahan dan tabel dalam urutan terbalik. Proyek memakai `users` dari bawaan Laravel; nama `user` pada ERD tidak dipakai.

## 7. Index dan constraint utama

- Unique pada `users.email`, kode master yang merupakan identifier, (`ms_status.type`, `ms_status.code`), `trans_project_hd.quotation_hd_id`, (`ms_project_workflow.type_id`, `sequence`), (`trans_quotation_term.quotation_hd_id`, `sequence`), dan (`ms_item_unit.item_id`, `unit_id`).
- Index pada seluruh foreign key, kode dokumen, status + tanggal transaksi, `trans_invoice_hd.due_date`, serta `trans_payment.invoice_hd_id` untuk daftar outstanding dan histori.
- `ms_document` memiliki index (`ref_type`, `ref_id`); tidak ada foreign key dinamis untuk pemilik polymorphic. `ms_client_contact.client_id` memakai foreign key biasa.
- Validasi rentang 0–100 untuk persentase termin, threshold, bobot workflow/tahap, dan progress project. Jumlah lintas baris seperti total termin atau total bobot workflow, serta tepat satu unit dasar per item, divalidasi saat transaksi disimpan.
- `trans_invoice_dt.quotation_term_id` tidak dapat dibuat unique global jika invoice Void tetap disimpan dan termin dapat ditagih ulang. Saat pembuatan invoice, kunci termin dan validasi bahwa belum ada invoice aktif yang memakainya.
- Nilai Rupiah pakai `decimal(20, 6)` untuk seluruh kolom nominal (subtotal, grand_total, amount, unit_price, discount, vat_amount, dsb.). ERD menulis `INT`; abaikan dan ikut rekomendasi dokumen ini. Konsisten di semua migration, model cast (`decimal:6`), dan form input.
- Soft delete bukan pengganti status bisnis Void, Rejected, atau Revised. Histori transaksi dan log monitoring harus tetap dapat ditelusuri.

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
        Schema::create('trans_quotation_term', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_hd_id')->constrained('trans_quotation_hd');
            $table->unsignedInteger('sequence');
            $table->string('term_name', 128);
            $table->unsignedTinyInteger('percentage');
            $table->decimal('amount', 20, 6);
            $table->unsignedTinyInteger('progress_threshold');
            $table->text('notes')->nullable();
            $this->addAuditColumns($table);

            $table->unique(['quotation_hd_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trans_quotation_term');
    }
};
```

Pada model `TransQuotationTerm`, gunakan `AuditedBySoftDelete` dan `SoftDeletes`, serta `protected $table = 'trans_quotation_term';`. Untuk `TransProjectMonitoring`, jangan pasang trait model itu karena event update/delete tidak sesuai sifat log yang tidak boleh diubah. File gambar dan bukti pembayaran disimpan di storage; database hanya menyimpan path.

### Catatan penamaan dari ERD

ERD menulis typo `employe_id`, `addres`, `progres_weight`, `progres_before`, dan `progres_after`. Dokumen ini menormalkan ejaan menjadi `employee_id`, `address`, `progress_weight`, `progress_before`, dan `progress_after`. Perbaikan harus dibawa kembali ke ERD agar seragam dengan migration, model, form, dan query.

### Role dan permission

Role dan permission memakai [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission). Setup dan assignment dilakukan di Filament, bukan dari migration domain ini. Tabel `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, dan `role_has_permissions` dibuat oleh migration bawaan paket.

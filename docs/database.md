# Database Aplikasi Web Sanitra

**Status:** Konsep awal · **Acuan:** [ERD Sanitra](../../../Berkas/Sanitra-ERD.drawio%20%282%29.png), [SRS Sanitra](../../../Berkas/SRS_Aplikasi_Web_Sanitra.pdf) · **Terkait:** [PRD](prd.md)

## 1. Ringkasan

Dokumen ini menjabarkan struktur database pada ERD Sanitra menjadi panduan awal untuk model Eloquent dan migration Laravel 13. ERD memuat 21 tabel domain: 12 master (termasuk `user`) dan 9 transaksi. `basemodel` pada ERD adalah kumpulan kolom umum, bukan tabel yang dibuat di database.

Nama tabel dan nama kolom di bagian detail mengikuti **ejaan pada ERD**. Contoh kode hanya menunjukkan pola; belum ada model, migration, trait, atau package yang dibuat dari dokumen ini.

## 2. Daftar tabel dan model

| Kelompok | Tabel | Model Eloquent |
| --- | --- | --- |
| Master | `user` | `User` |
| Master | `ms_company` | `MsCompany` |
| Master | `ms_employee` | `MsEmployee` |
| Master | `ms_client` | `MsClient` |
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

Khusus `trans_project_monitoring`, SRS hanya menetapkan `created_by` dan `created_at`; log bersifat append-only. Kolom audit pada tabel `user` perlu disesuaikan dengan migration autentikasi Laravel agar tidak menggandakan kolom bawaan. `created_by` tidak diberi default ID tertentu: nilainya berasal dari user login atau dari strategi seeder yang nanti digunakan.

Pola migration untuk sebuah master dapat ditulis langsung dengan Blueprint Laravel. Tidak perlu membuat tabel `basemodel`:

```php
Schema::create('ms_item_category', function (Blueprint $table) {
    $table->id();
    $table->string('code', 50)->unique();
    $table->string('name', 128);
    $table->text('description')->nullable();

    $table->boolean('active')->default(true);
    $table->foreignId('created_by');
    $table->foreignId('updated_by')->nullable();
    $table->foreignId('deleted_by')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

Kolom audit bisa dikemas sebagai helper/trait migration milik project saat implementasi supaya tidak berulang; contoh ini memakai API Laravel bawaan dan tidak membutuhkan package tambahan.

## 4. Detail tabel

Tabel berikut mencantumkan **kolom domain** dari ERD; `id` dan kolom umum pada §3 tidak diulang di setiap tabel. `Ya` pada kolom Null berarti ERD menandai `NULL`. Relasi foreign key mengacu ke `id` tabel tujuan.

### 4.1 `user`

Akun untuk login aplikasi. Model: `User`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `email` | varchar(128) | tidak | Email login; unik. |
| `password` | varchar(255) | tidak | Hash password. |
| `avatar` | varchar(255) | tidak | Path avatar menurut ERD. |
| `employe_id` | bigint unsigned | ya | FK ke `ms_employee`; ejaan mengikuti ERD. |

Laravel baru sudah memiliki tabel `users` dan model `User`. Bila migration mengikuti ERD secara harfiah (`user`), model auth harus diarahkan ke tabel tersebut; bila mempertahankan `users`, seluruh relasi audit dan employee mengarah ke `users`. Jangan membuat kedua tabel auth. Relasi employee harus unik jika satu employee maksimal satu user seperti SRS.

### 4.2 `ms_company`

Data company penerbit dokumen dan struktur induk. Model: `MsCompany`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode company. |
| `name` | varchar(128) | tidak | Nama singkat. |
| `legal_name` | varchar(128) | tidak | Nama legal. |
| `email` | varchar(128) | tidak | Email company. |
| `phone` | varchar(18) | tidak | Nomor telepon. |
| `description` | text | ya | Keterangan. |
| `parent_id` | bigint unsigned | ya | FK ke `ms_company.id`. |
| `pic_employee_id` | bigint unsigned | ya | FK ke `ms_employee.id`. |

### 4.3 `ms_employee`

Karyawan dan PIC internal. Model: `MsEmployee`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode employee. |
| `name` | varchar(128) | tidak | Nama karyawan. |
| `position` | varchar(128) | tidak | Jabatan. |
| `company_id` | bigint unsigned | tidak | FK ke `ms_company.id`. |
| `email` | varchar(128) | tidak | Email karyawan. |
| `phone` | varchar(18) | tidak | Nomor telepon. |

### 4.4 `ms_client`

Client dan PIC internal yang menangani relasi client. Model: `MsClient`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode client; unik menurut FR-03. |
| `name` | varchar(128) | tidak | Nama client. |
| `pic_employee_id` | bigint unsigned | ya | FK ke `ms_employee.id`. |
| `email` | varchar(128) | tidak | Email client. |
| `phone` | varchar(18) | tidak | Nomor telepon. |
| `addres` | varchar(255) | tidak | Alamat; ejaan mengikuti ERD. |
| `description` | text | ya | Keterangan. |

### 4.5 `ms_project_type`

Jenis project yang menjadi dasar workflow tahap. Model: `MsProjectType`.

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
| `progres_weight` | integer | tidak | Bobot tahap; ejaan mengikuti ERD. |

Jumlah `progres_weight` per `type_id` harus 100 sebelum workflow dipakai (BR-03).

### 4.7 `ms_item_category`

Kategori item. Model: `MsItemCategory`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode kategori. |
| `name` | varchar(128) | tidak | Nama kategori. |
| `description` | text | ya | Keterangan. |

### 4.8 `ms_item`

Master barang, jasa, atau paket yang dapat masuk quotation. Model: `MsItem`.

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

Unit yang tersedia untuk item beserta konversinya. Model: `MsItemUnit`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `item_id` | bigint unsigned | tidak | FK ke `ms_item.id`. |
| `unit_id` | bigint unsigned | tidak | FK ke `ms_unit.id`. |
| `is_base` | boolean | tidak | Menandai unit dasar item. |
| `conversion` | integer | tidak | Faktor konversi sesuai ERD. |

Pasangan (`item_id`, `unit_id`) unik. Setiap item harus mempunyai tepat satu baris `is_base = true` (FR-05).

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
| `notes` | text | tidak | Catatan. |
| `vat_type` | varchar(50) | tidak | Jenis PPN. |
| `vat_amount` | integer | tidak | Nilai PPN. |
| `discount_total` | integer | tidak | Total diskon. |
| `subtotal` | integer | tidak | Subtotal. |
| `grand_total` | integer | tidak | Total akhir. |
| `status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `quotation`. |

Detail item ada di `trans_quotation_dt`, skema termin di `trans_quotation_term`. Versi quotation lama harus tetap tersimpan saat revisi (FR-13); ERD saat ini belum memuat kolom nomor revisi.

### 4.14 `trans_quotation_dt`

Detail item quotation. Model: `TransQuotationDt`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `quotation_hd_id` | bigint unsigned | tidak | FK ke `trans_quotation_hd.id`. |
| `item_id` | bigint unsigned | tidak | FK ke `ms_item.id`. |
| `unit_id` | bigint unsigned | tidak | FK ke `ms_unit.id`. |
| `notes` | text | ya | Catatan item. |
| `qty` | integer | tidak | Jumlah. |
| `unit_price` | integer | tidak | Harga per unit saat quotation dibuat. |
| `discount` | integer | tidak | Diskon baris. |
| `total_price` | integer | tidak | Total baris. |

### 4.15 `trans_quotation_term`

Termin yang disepakati dalam quotation. Model: `TransQuotationTerm`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `quotation_hd_id` | bigint unsigned | tidak | FK ke `trans_quotation_hd.id`. |
| `sequence` | integer | tidak | Urutan termin. |
| `term_name` | varchar(128) | tidak | Nama termin. |
| `percentage` | integer | tidak | Persentase nilai quotation. |
| `amount` | integer | tidak | Nilai termin; dibekukan saat Approved. |
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
| `progres_weight` | integer | tidak | Bobot tahap; ejaan mengikuti ERD. |
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
| `progres_before` | integer | tidak | Progress sebelumnya; ejaan mengikuti ERD. |
| `progres_after` | integer | tidak | Progress sesudahnya; ejaan mengikuti ERD. |
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
| `subtotal` | integer | tidak | Subtotal tagihan. |
| `vat_type` | varchar(50) | tidak | Jenis PPN. |
| `vat_amount` | integer | tidak | Nilai PPN. |
| `grand_total` | integer | tidak | Total tagihan. |
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
| `amount` | integer | tidak | Nilai termin saat invoice dibuat. |

Satu termin hanya boleh masuk satu invoice **aktif**. Invoice Void tetap tersimpan dalam histori dan termin dapat masuk invoice baru (FR-31, FR-34).

### 4.21 `trans_payment`

Pembayaran dan bukti transfer invoice. Model: `TransPayment`.

| Kolom | Tipe ERD | Null | Keterangan |
| --- | --- | --- | --- |
| `code` | varchar(50) | tidak | Kode pembayaran. |
| `transaction_date` | date | tidak | Tanggal pembayaran. |
| `invoice_hd_id` | bigint unsigned | tidak | FK ke `trans_invoice_hd.id`. |
| `amount` | integer | tidak | Nominal pembayaran. |
| `payment_method` | varchar(128) | tidak | Metode pembayaran. |
| `status_id` | bigint unsigned | tidak | FK ke `ms_status.id` tipe `payment`. |
| `notes` | text | ya | Catatan. |
| `reference_no` | varchar(50) | tidak | Nomor referensi. |
| `payment_proof` | varchar(255) | tidak | Path file bukti transfer. |

Hanya payment berstatus Confirmed yang dihitung ke pelunasan invoice.

## 5. Relasi antar tabel

| Induk | Anak / foreign key | Kardinalitas |
| --- | --- | --- |
| `ms_company` | `ms_company.parent_id` | satu induk memiliki banyak company anak |
| `ms_company` | `ms_employee.company_id`, `ms_project_type.company_id` | satu company memiliki banyak employee/tipe project |
| `ms_employee` | `user.employe_id`, `ms_company.pic_employee_id`, `ms_client.pic_employee_id`, `trans_project_hd.pic_employee_id` | satu employee dapat menjadi PIC; relasi user maksimal satu |
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

1. Tabel autentikasi Laravel (`users`) atau `user` sesuai nama tabel yang akan digunakan. Cache dan jobs bawaan Laravel tetap terpisah dari 21 tabel domain ERD.
2. `ms_company` dengan `parent_id`, tetapi tunda foreign key `pic_employee_id`.
3. `ms_employee`, lalu tambahkan foreign key `ms_company.pic_employee_id` dan FK employee pada tabel auth. Urutan dua langkah ini menghindari siklus company ↔ employee.
4. `ms_client`, `ms_project_type`, `ms_project_workflow`.
5. `ms_item_category`, `ms_unit`, `ms_item`, `ms_item_unit`.
6. `ms_bank_account`, `ms_status`.
7. `trans_quotation_hd`, `trans_quotation_dt`, `trans_quotation_term`.
8. `trans_project_hd`, `trans_project_dt`, `trans_project_monitoring`.
9. `trans_invoice_hd`, `trans_invoice_dt`, `trans_payment`.

Migration `down()` menghapus foreign key tambahan dan tabel dalam urutan terbalik. Nama tabel `user` pada ERD berbeda dari `users` bawaan Laravel; seluruh migration dan relasi harus konsisten memakai satu pilihan nama.

## 7. Index dan constraint utama

- Unique pada `user.email`, kode master yang merupakan identifier, (`ms_status.type`, `ms_status.code`), `trans_project_hd.quotation_hd_id`, (`ms_project_workflow.type_id`, `sequence`), (`trans_quotation_term.quotation_hd_id`, `sequence`), dan (`ms_item_unit.item_id`, `unit_id`).
- Index pada seluruh foreign key, kode dokumen, status + tanggal transaksi, `trans_invoice_hd.due_date`, serta `trans_payment.invoice_hd_id` untuk daftar outstanding dan histori.
- Validasi rentang 0–100 untuk persentase termin, threshold, bobot workflow/tahap, dan progress project. Jumlah lintas baris seperti total termin atau total bobot workflow, serta tepat satu unit dasar per item, divalidasi saat transaksi disimpan.
- `trans_invoice_dt.quotation_term_id` tidak dapat dibuat unique global jika invoice Void tetap disimpan dan termin dapat ditagih ulang. Saat pembuatan invoice, kunci termin dan validasi bahwa belum ada invoice aktif yang memakainya.
- Nilai Rupiah di ERD bertipe `INT`. Saat migration dibuat, gunakan tipe integer yang mampu menampung total transaksi project; `bigInteger` lebih aman untuk nilai uang dan tetap tidak memakai float.
- Soft delete bukan pengganti status bisnis Void, Rejected, atau Revised. Histori transaksi dan log monitoring harus tetap dapat ditelusuri.

## 8. Pola model Eloquent dan migration

Model domain perlu nama tabel eksplisit serta relasi dengan nama foreign key dari ERD. Contoh model master dengan Laravel bawaan:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsItem extends Model
{
    use SoftDeletes;

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

Contoh migration transaksi dengan relasi eksplisit:

```php
Schema::create('trans_quotation_term', function (Blueprint $table) {
    $table->id();
    $table->foreignId('quotation_hd_id')->constrained('trans_quotation_hd');
    $table->unsignedInteger('sequence');
    $table->string('term_name', 128);
    $table->unsignedTinyInteger('percentage');
    $table->unsignedBigInteger('amount');
    $table->unsignedTinyInteger('progress_threshold');
    $table->text('notes')->nullable();
    $table->foreignId('status_id')->constrained('ms_status');
    $table->foreignId('created_by');
    $table->foreignId('updated_by')->nullable();
    $table->foreignId('deleted_by')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['quotation_hd_id', 'sequence']);
});
```

Contoh tersebut sengaja memperlihatkan kolom langsung, bukan trait custom yang belum ada di project. Saat implementasi, kolom audit dapat dipindahkan ke helper/trait internal tanpa mengubah skema. File gambar dan bukti pembayaran disimpan di storage; database hanya menyimpan path.

### Catatan penamaan dari ERD

ERD menulis `employe_id`, `addres`, `progres_weight`, `progres_before`, dan `progres_after`. Dokumen ini mempertahankan ejaan itu agar sesuai sumber. Jika nama diperbaiki saat implementasi, perubahan harus seragam di ERD, migration, model, form, dan query. Tabel role/permission yang disebut SRS belum digambar dalam ERD ini, sehingga belum dijabarkan sebagai tabel domain di dokumen konsep awal.

# Strategi Testing — Per Halaman Resource

Lihat index di [`playwright-strategy.md`](./playwright-strategy.md) untuk konteks stack & environment.

Urutan prioritas: **Master data dulu, baru Transaksi** — resource transaksi bergantung pada master data (item, client, dll) sudah benar duluan.

## 0. Pola UI nyata yang sudah terverifikasi (dari `MsUnit` & `MsItemCategory`)

Ditemukan dengan cara probing langsung ke app (bukan nebak) saat menulis 2 test resource pertama — berlaku untuk resource Filament lain yang pakai konvensi sama (cek ulang per resource, jangan asumsi buta):

- UI 100% Bahasa Indonesia. Tombol: "Buat `<Label>`" (create, di halaman list), "Simpan" (bukan "Simpan perubahan", di halaman edit), "Buat" + "Buat & buat lainnya" + "Batal" (di halaman create). Notifikasi sukses: **"Data berhasil dibuat"**, **"Data berhasil disimpan"**, **"Data berhasil dihapus"** — konsisten di semua resource standar Filament (bukan per-resource custom text).
- Setelah **Create** sukses → redirect ke halaman **View** (`Lihat <Label>`), bukan ke Edit atau List.
- Setelah **Edit/Simpan** sukses → **tetap di halaman Edit** (tidak redirect), form tetap terbuka untuk edit lanjutan.
- Row actions di tabel **bukan dropdown "Open actions"** — tiap aksi (`Lihat`/`Ubah`/`Hapus`) adalah link/button terpisah langsung di cell "Aksi". Role yang read-only (misal Marketing di `MsItemCategory`) cuma punya `Lihat`, tanpa `Ubah`/`Hapus` sama sekali (bukan disabled, tapi tidak ada).
- Delete pakai modal konfirmasi: `getByRole('alertdialog', { name: 'Hapus <Label>' })`, isinya tombol **"Batal"** dan **"Hapus"** (dua tombol literal "Hapus" di halaman — satu di row, satu di modal — harus discope ke dalam `alertdialog` biar tidak ambigu).
- Field `Select`/dropdown Filament untuk enum (misal `Tipe` di `MsUnit`) dirender sebagai **native `<select>`** (`role: combobox`), bukan custom listbox — pakai `selectOption({ label: '...' })`, bukan klik buka dropdown lalu klik opsi.
- Field kode auto-generate (misal `MsItemCategory.code`) di form create: `TextInput` dengan `disabled()` + `dehydrated(false)` — di UI tampil sebagai textbox disabled dengan placeholder "Akan di-generate otomatis saat disimpan". Assert `toBeDisabled()`, jangan coba isi.
- Halaman 403 Filament: `heading "403"` + `text "Forbidden"` (bukan redirect ke halaman lain atau pesan custom).
- Role tanpa akses menu (ViewAny ditolak): link resource itu memang **hilang total** dari sidebar, bukan ada-tapi-disabled.

**Gotcha infra penting (lihat `playwright-strategy.md` §2.1 untuk detail lengkap):**
- Rate limiter login: maksimal 5 percobaan/60 detik per IP (bukan per akun) — jangan run `test:e2e` dua kali berturut-turut dalam <60 detik.
- `workers: 1` wajib di config — server dev lokal tidak tahan concurrent load, 2+ worker bikin notifikasi toast flaky (hilang/duplikat).
- Kalau menulis test baru dan tidak yakin label/behavior sebenarnya, **jangan nebak** — buat test probe sementara yang sengaja gagal (`expect(page.getByText('__force_fail__')).toBeVisible()`) untuk dump accessibility snapshot halaman asli ke `test-results/.../error-context.md`, baca dari situ, baru tulis assertion final. Hapus file probe setelah selesai.

## 1. Checklist generik per resource

Checklist ini berlaku untuk tiap resource Filament (baik master maupun transaksi), ditest di file `resources/<nama-resource>.spec.ts`:

1. **List/Table**
   - Halaman index ter-load (`/dashboard/<resource-slug>`), tabel tampil tanpa error.
   - Kolom-kolom utama tampil dengan data yang benar.
   - Search (global search box) jalan dan filter hasil sesuai keyword.
   - Filter (kalau resource punya `TableFilter`) jalan dan hasil sesuai kondisi filter.
   - Pagination jalan kalau data > 1 halaman.
2. **Create**
   - Tombol "New"/"Tambah" membuka form create.
   - Submit form kosong → validasi field wajib muncul, tidak submit.
   - Submit form valid → sukses, notification muncul, record baru muncul di list.
3. **View/Edit**
   - Buka detail/edit record, data ter-load benar di form.
   - Ubah field, simpan → perubahan ter-reflect di list & detail.
4. **Delete / Soft Delete**
   - Tombol delete memunculkan konfirmasi dialog.
   - Setelah konfirmasi, record hilang dari list (atau masuk ke trashed kalau soft delete), notification muncul.
   - Kalau resource support restore (soft delete), test restore juga.
5. **Permission per role**
   - Role tanpa akses: menu resource tidak muncul di sidebar, dan akses langsung via URL menghasilkan 403/redirect.
   - Role dengan akses terbatas (misal hanya `ViewAny`+`View`): tombol Create/Edit/Delete tidak muncul.
   - Role dengan akses penuh: semua tombol relevan muncul dan berfungsi.
6. **Field/behavior khusus resource**
   - Auto-generate code (`GenerateCodeService`) — field kode muncul otomatis saat create, format sesuai pola resource tersebut.
   - Money mask input (lihat memory `feedback_filament_money_mask`) — input angka Rupiah ter-format benar, tidak ada mask loop.
   - Computed/derived field (misal total dari item lines) — update otomatis saat input berubah.

## 2. Resource Master Data (prioritas pertama)

Daftar resource master (`app/Filament/Resources/`), semua role punya campuran akses berbeda sesuai `docs/permision.md` §6:

| Resource | Permission subject | Akses Marketing | Akses Finance | Akses Teknis |
|---|---|---|---|---|
| `MsItemCategory` | `MsItemCategory` | ViewAny, View | - | - |
| `MsItem` | `MsItem` | ViewAny, View | - | - |
| `MsClient` | `MsClient` | Full CRUD (+ kontak & dokumen client) | ViewAny saja (list, bukan detail) | ViewAny, View |
| `MsProjectType` | `MsProjectType` | ViewAny, View | - | ViewAny, View |
| `MsProjectWorkflow` | `MsProjectWorkflow` | ViewAny, View | - | ViewAny, View |
| `MsBankAccount` | `MsBankAccount` | - | ViewAny, View | - |
| `MsCompany` | `MsCompany` | sesuai matriks §6 lanjutan (cek `docs/permision.md`) | | |
| `MsEmployee` | `MsEmployee` | sesuai matriks §6 lanjutan | | |
| `MsUnit` | `MsUnit` | sesuai matriks §6 lanjutan | | |
| `MsStatus` | `MsStatus` | biasanya super_admin only (reference data status) | | |
| `UserProfile` / `User` | `User` | biasanya super_admin only | | |

**Urutan test yang disarankan** (karena saling bergantung sebagai referensi):

1. ✅ `MsUnit`, `MsItemCategory` — tidak bergantung resource lain. **Selesai** (`tests/e2e/resources/ms-unit.spec.ts`, `ms-item-category.spec.ts`).
2. `MsItem` — bergantung `MsItemCategory` dan `MsUnit`.
3. `MsClient` — independen, tapi dipakai di Quotation nanti.
4. `MsCompany`, `MsEmployee`, `MsBankAccount` — independen, dipakai di konfigurasi perusahaan/invoice.
5. `MsProjectType`, `MsProjectWorkflow` — independen, dipakai nanti saat modul Project ada.
6. `MsStatus` — reference data, biasanya read-only untuk semua role, full CRUD untuk super_admin saja.

**Catatan khusus `MsClient`:** test harus cover juga sub-data kontak & dokumen client (bukan cuma field utama client), karena Marketing dapat full CRUD untuk itu sesuai `docs/permision.md`.

## 3. Resource Transaksi (prioritas kedua)

| Resource | Permission subject | Akses Marketing | Akses Finance | Akses Teknis |
|---|---|---|---|---|
| `TransSalesQuotationHd` | `TransSalesQuotationHd` | Full: list, lihat, buat, ubah/hapus saat Draft, + Kirim/Setujui/Revisi/Tolak | ViewAny, View saja | Tidak ada akses |
| `TransSalesOrderHd` | `TransSalesOrderHd` | ViewAny, View, Update (catatan) | ViewAny, View, Update (catatan) + Buat Invoice (izin terpisah) | ViewAny, View saja |
| `TransSalesInvoiceHd` | `TransSalesInvoiceHd` | Tidak ada akses menu | Full: ViewAny, View, Update (sebelum ada pembayaran), Buat Invoice dari SO, Batalkan Invoice | Tidak ada akses |
| `TransSalesPayment` | `TransSalesPayment` | Tidak ada akses menu | Full: ViewAny, View, Create, Konfirmasi, Tolak | Tidak ada akses |

Karena akses resource transaksi ini **sangat berbeda per role** (banyak "tidak ada akses menu"), test permission-nya krusial — jangan cuma test "role X bisa akses", tapi juga eksplisit test "role Y TIDAK bisa akses" (403/menu hilang), khususnya:

- Marketing mencoba akses `/dashboard/trans-sales-invoice-hds` langsung via URL → harus ditolak.
- Teknis mencoba akses halaman Invoice/Payment sama sekali → harus ditolak.

### 3.1 Checklist tambahan khusus resource transaksi

Selain checklist generik §1, resource transaksi punya action/tombol custom yang butuh test terpisah (bukan CRUD biasa) — ini overlap dengan `business-flow-strategy.md` tapi perlu di-test juga di level resource/unit untuk memastikan guard-nya benar di luar flow penuh:

| Resource | Tombol/Action custom | Permission | Syarat status |
|---|---|---|---|
| Quotation | Kirim Penawaran | `Send:TransSalesQuotationHd` | Draft |
| Quotation | Setujui Penawaran | `Approve:TransSalesQuotationHd` | Sent |
| Quotation | Revisi | `Revise:TransSalesQuotationHd` | Sent |
| Quotation | Tolak | `Reject:TransSalesQuotationHd` | Sent |
| Sales Order | Buat Invoice | `Create:TransSalesInvoiceHd` | - (dari termin Ready to Bill) |
| Sales Order | Tutup SO | `Close:TransSalesOrderHd` (super_admin only) | - |
| Invoice | Batalkan Invoice | `Void:TransSalesInvoiceHd` | status/payment tertentu |
| Payment | Konfirmasi Pembayaran | `Confirm:TransSalesPayment` | Pending |
| Payment | Tolak Pembayaran | `Reject:TransSalesPayment` | Pending |

Test tiap tombol: (a) tombol tidak muncul kalau status tidak sesuai syarat, (b) tombol tidak muncul kalau role tidak punya permission, (c) klik tombol pada kondisi valid menghasilkan perubahan status yang benar.

## 4. Konvensi Penamaan Test

- File: `resources/<kebab-case-resource-name>.spec.ts`, misal `resources/ms-item.spec.ts`, `resources/trans-sales-quotation-hd.spec.ts`.
- `test.describe` per resource, nested `test.describe` per role kalau permission test-nya banyak.
- Nama test deskriptif bahasa Indonesia atau Inggris konsisten dengan gaya docs (ikuti yang sudah ada di `tests/` PHPUnit kalau ada referensi).

# Role dan Permission Sanitra

**Status:** Seeder dan pengamanan action utama sudah ditulis dan dirapihkan (hardcoded role → permission + enum `RoleName`); seeder belum dijalankan.
**Sumber:** Analisis snapshot database server yang diberikan pada 31 Oktober 2026 (berkas dump sudah dihapus), konfigurasi, dan kode aplikasi pada tanggal tersebut.
**Package terpasang:** `spatie/laravel-permission` 8.3.0, `bezhansalleh/filament-shield` 4.3.1, Filament 5.9.0

## 1. Cara kerja di aplikasi ini

- Spatie menyimpan role di `roles`, permission di `permissions`, hubungan role ke permission di `role_has_permissions`, dan hubungan user ke role di `model_has_roles`. `model_has_permissions` tersedia untuk permission langsung ke user, tetapi kosong pada snapshot server.
- Model `User` memakai trait `HasRoles`. `UserSeeder` memanggil `syncRoles()` untuk keempat akun contoh.
- Nama role disimpan di enum `App\Enums\RoleName` (`SuperAdmin`, `Marketing`, `Finance`, `Teknis`). Seeder dan guard di Filament memakai enum ini, bukan string literal, agar rename aman.
- Filament Shield terpasang pada panel `dashboard`. Shield menghasilkan nama permission dari resource, page, dan widget. Format konfigurasi proyek adalah `Aksi:Subjek` dengan huruf Pascal, misalnya `ViewAny:TransSalesQuotationHd` dan `Create:Role`.
- Policy resource memanggil `$authUser->can('Aksi:Subjek')`. Permission menentukan akses ke resource dan aksinya. Beberapa widget memakai pemeriksaan role langsung melalui `hasAnyRole()`.
- `User::canAccessPanel()` saat ini selalu mengembalikan `true`; pembatasan menu dan aksi berasal dari policy/permission dan logika widget, bukan dari penolakan akses panel di method tersebut.
- Shield dikonfigurasi dengan `super_admin.define_via_gate = false`. Artinya role `super_admin` mendapat permission yang diberikan secara eksplisit, bukan akses otomatis ke permission baru melalui Gate.

## 2. Kondisi pada snapshot database server

| Role | Jumlah permission | Jumlah user yang tertaut di `model_has_roles` |
| --- | ---: | ---: |
| `super_admin` | 221 | 1 |
| `Marketing` | 0 | 0 |
| `Finance` | 0 | 0 |
| `Teknis` | 0 | 0 |

Snapshot memuat 4 user, 4 role, dan 221 permission. Semua 221 baris `role_has_permissions` mengarah ke `super_admin`; tidak ada pemberian permission langsung ke user. Ini hanya menggambarkan isi dump tersebut, bukan jaminan kondisi database server setelah dump dibuat.

Ada 18 subjek dengan 12 aksi standar masing-masing: `Activity`, `ErrorLog`, `MsBankAccount`, `MsClient`, `MsCompany`, `MsEmployee`, `MsItem`, `MsItemCategory`, `MsProjectType`, `MsProjectWorkflow`, `MsStatus`, `MsUnit`, `Role`, `TransSalesInvoiceHd`, `TransSalesOrderHd`, `TransSalesPayment`, `TransSalesQuotationHd`, dan `User`. Lima subjek lain hanya memiliki izin `View`: `ImportMasterData`, `MonthlyQuotationsChart`, `OwnerStats`, `ProjectStatusChart`, dan `WelcomeWidget`.

Daftar permission pada dump adalah snapshot. Saat menulis seeder, cocokkan lagi dengan resource, page, widget, dan policy yang ada saat itu; jangan mengandalkan ID permission atau menganggap daftar lama sudah lengkap.

## 3. Seeder yang ada sekarang

`RoleSeeder` memastikan permission sesuai §6 tersedia dan memakai `syncPermissions()` untuk Marketing, Finance, Teknis, serta `super_admin` dengan guard `web`. Dua permission khusus `super_admin` (`Close:TransSalesOrderHd` dan `Access:ImportMasterData`) ditulis dalam konstanta terpisah `SUPER_ADMIN_ONLY_PERMISSIONS` dan dibuat via `firstOrCreate()` sebelum super admin disinkronkan dengan seluruh permission `web`. `UserSeeder` mengaitkan empat user contoh ke role masing-masing lewat `syncRoles()` dengan enum `RoleName`. Seeder belum dijalankan pada database lokal atau server, sehingga snapshot lama belum mencerminkan perubahan ini.

Shield menyediakan perintah `shield:seeder` untuk mengekspor role dan permission dari database. Mapping aplikasi ini ditulis langsung di `RoleSeeder`, sehingga tidak bergantung pada hasil ekspor snapshot server lama.

## 4. Keputusan seeder

1. `RoleSeeder` menerapkan matriks §6 dengan `syncPermissions()`. Izin manual yang tidak ada di matriks akan dilepas dari Marketing, Finance, dan Teknis saat seeder dijalankan ulang.
2. Permission diidentifikasi dengan nama dan guard `web`, bukan ID dump. Seeder memakai API Spatie dan membersihkan cache permission sebelum dan sesudah sinkronisasi.
3. `super_admin` diselaraskan dengan semua permission `web` yang tersedia saat seeder berjalan. Jika Shield menghasilkan permission baru setelah itu, jalankan ulang `RoleSeeder`.
4. Akun development tetap dibuat oleh `UserSeeder`; seeder tidak mengimpor user atau password dari snapshot server.

## 5. Inventaris tombol yang perlu diatur

Tombol CRUD bawaan Filament (`CreateAction`, `ViewAction`, `EditAction`, `DeleteAction`, restore, dan force delete) mengikuti policy resource terkait. Permission standar seperti `Create:TransSalesPayment` sudah dibuat Shield; seeder cukup memberikannya ke role yang tepat. Kondisi bisnis seperti status Draft atau Pending tetap perlu diperiksa selain permission.

| Halaman dan tombol | Status pengamanan kode | Permission / keputusan |
| --- | --- | --- |
| Quotation — **Kirim Penawaran** | Role (enum `RoleName`), permission, dan kesiapan Draft dicek | `Send:TransSalesQuotationHd` |
| Quotation — **Setujui Penawaran** | Role (enum `RoleName`), permission, dan status Sent dicek | `Approve:TransSalesQuotationHd` |
| Quotation — **Revisi** | Role (enum `RoleName`), permission, dan status Sent dicek | `Revise:TransSalesQuotationHd` |
| Quotation — **Tolak** | Role (enum `RoleName`), permission, dan status Sent dicek | `Reject:TransSalesQuotationHd` |
| Sales Order — **Buat Invoice** | Izin dicek di tombol, halaman, dan submit | Permission standar `Create:TransSalesInvoiceHd`; tidak ada tombol Add di daftar Invoice karena invoice dibuat dari termin SO |
| Sales Order — **Buat Project** | Tombol, route, dan file halaman sudah dihapus | Project akan dibuat manual melalui resource Project yang belum dibuat |
| Sales Order — **Tutup SO** | Permission dicek via `Gate::allows()` + policy Update + status SO | `Close:TransSalesOrderHd` (custom permission, hanya diberikan ke `super_admin`) |
| Invoice — **Batalkan Invoice** | Permission, izin Update Invoice, dan syarat status/payment dicek | `Void:TransSalesInvoiceHd` |
| Payment — **Konfirmasi Pembayaran** | Permission dan status Pending dicek di dua lokasi | `Confirm:TransSalesPayment` |
| Payment — **Tolak Pembayaran** | Permission dan status Pending dicek di dua lokasi | `Reject:TransSalesPayment` |
| Halaman Import Master Data — **Mulai Import** | Halaman, submit, dan route unduh template memeriksa permission | `Access:ImportMasterData` (custom permission, hanya diberikan ke `super_admin`) |

`Catat Pembayaran` pada Invoice memakai `CreateAction` dan sudah memeriksa `TransSalesPaymentResource::canCreate()`; izinnya adalah permission standar `Create:TransSalesPayment`. Tombol lihat dokumen dan Download PDF dapat mengikuti izin View data terkait. Export Excel perlu diputuskan terpisah: jika pengguna yang boleh melihat daftar belum tentu boleh mengunduh seluruh data, buat permission `Export` untuk aksi tersebut.

Pemeriksaan izin pada action bisnis di atas diterapkan saat tombol ditampilkan dan saat request dijalankan. Konfigurasi/route cache yang lama harus diperbarui saat aplikasi dijalankan dengan kode ini (`php artisan config:clear && php artisan route:clear`).

## 6. Mapping role yang disepakati

`ViewAny` membuka daftar/menu resource; `View` membuka detail. `Full` di bawah berarti semua **alur yang tersedia di UI** untuk modul tersebut, bukan otomatis semua 12 permission yang dihasilkan Shield. Aksi yang memang dinonaktifkan oleh resource tetap dinonaktifkan.

| Area | Marketing | Finance | Teknis |
| --- | --- | --- | --- |
| Quotation (`TransSalesQuotationHd`) | Full: daftar, lihat, buat, ubah/hapus saat Draft, serta Kirim, Setujui, Revisi, Tolak | `ViewAny`, `View` saja | Tidak diberi akses |
| Sales Order (`TransSalesOrderHd`) | `ViewAny`, `View`, `Update` untuk catatan | `ViewAny`, `View`, `Update` untuk catatan; Buat Invoice memakai izin Create Invoice terpisah | `ViewAny`, `View` saja |
| Invoice (`TransSalesInvoiceHd`) | Tidak diberi akses menu | Full alur tersedia: `ViewAny`, `View`, `Update` sebelum ada pembayaran, Buat Invoice dari SO, Batalkan Invoice | Tidak diberi akses |
| Pembayaran (`TransSalesPayment`) | Tidak diberi akses menu | Full alur tersedia: `ViewAny`, `View`, `Create`, Konfirmasi, Tolak | Tidak diberi akses |
| Kategori Item (`MsItemCategory`) dan Item (`MsItem`) | `ViewAny`, `View` saja | Tidak diberi akses | Tidak diberi akses |
| Client (`MsClient`) | Full CRUD yang tersedia, termasuk kontak dan dokumen client | `ViewAny` saja (daftar Client; detail belum diizinkan) | `ViewAny`, `View` saja |
| Tipe Project (`MsProjectType`) dan Workflow Project (`MsProjectWorkflow`) | `ViewAny`, `View` saja | Tidak diberi akses | `ViewAny`, `View` saja |
| Rekening Bank (`MsBankAccount`) | Tidak diberi akses | `ViewAny`, `View` saja | Tidak diberi akses |
| Profil Saya (`UserProfileResource`) | Lihat dan ubah profil sendiri | Lihat dan ubah profil sendiri | Lihat dan ubah profil sendiri |
| Dashboard | Widget Marketing | Widget Finance | Widget Teknis |

**Izin tombol khusus yang mengikuti mapping:** `Send`, `Approve`, `Revise`, dan `Reject` Quotation untuk Marketing; `Create:TransSalesInvoiceHd`, `Void:TransSalesInvoiceHd`, `Create:TransSalesPayment`, `Confirm:TransSalesPayment`, dan `Reject:TransSalesPayment` untuk Finance; `Close:TransSalesOrderHd` dan `Access:ImportMasterData` hanya untuk `super_admin`. `Create` Invoice/Payment adalah permission standar Shield; sembilan permission bisnis lain terdaftar di `config/filament-shield.php` kunci `custom_permissions`. Profil Saya tidak memerlukan permission Shield tersendiri karena resource sudah membatasi query dan aksi ke user yang sedang login.

Widget yang dipakai saat ini: Marketing (`MarketingStatsWidget`, `MarketingConversionChartWidget`), Finance (`FinanceStatsWidget`, `FinanceRevenueChartWidget`, `FinanceInvoiceStatusChartWidget`), dan Teknis (`TeknisStatsWidget`). Semua widget ini saat ini memakai `canView()` berbasis role. Permission `View` widget yang mungkin dihasilkan Shield tidak otomatis menjadi pemeriksaan di method tersebut; seeder dan kode widget perlu dibuat konsisten bila nanti akses dashboard dipindahkan ke permission.

**Batas mapping saat ini:** Pembuatan Project nantinya manual melalui resource Project, bukan tombol dari halaman SO. Resource dan mapping transaksi Project belum dibuat. Tombol Tutup SO belum diberikan ke Marketing, Finance, atau Teknis; sementara hanya `super_admin` yang boleh. Untuk frasa “full akses” Client dan Quotation, cakupan `Restore`/`ForceDelete` dan aksi massal perlu dipastikan sebelum seeder final; izin destruktif tersebut tidak diasumsikan otomatis dari kata “full”.

## 7. Status pengamanan action

1. [x] Edit/hapus Quotation Draft memeriksa role Marketing/super admin (via enum `RoleName`), policy `Update`/`Delete`, dan status. Tombol Kirim, Setujui, Revisi, Tolak memeriksa role serta permission khusus.
2. [x] Buat Invoice dari SO memeriksa `Create:TransSalesInvoiceHd` pada tombol, halaman tujuan, dan method submit; izin Update catatan SO tidak lagi menjadi dasar pembuatan Invoice.
3. [x] Tombol, route, dan file halaman `CreateOrderProject` sudah dihapus. Project baru nantinya dibuat lewat resource Project.
4. [x] Konfirmasi/Tolak Payment di halaman Payment dan relation manager Invoice memeriksa permission khusus masing-masing. Batalkan Invoice memeriksa `Void:TransSalesInvoiceHd`. Tutup SO memakai permission `Close:TransSalesOrderHd` (bukan hardcoded role).
5. [x] `UserProfileResource` tetap membatasi akses ke profil user login; role seeder tidak memberi `View:User` atau `Update:User` hanya untuk fitur Profil Saya.
6. [x] Halaman Import Master Data, method import, dan route unduh template memakai permission `Access:ImportMasterData` (bukan hardcoded role).

## 8. Urutan Shield dan seeder

1. Untuk mapping yang sudah ditulis, **tidak wajib** menjalankan `shield:generate` lagi: `RoleSeeder` memakai `firstOrCreate()` untuk semua permission yang dipakai, termasuk permission custom. Jalankan `shield:generate --all --option=permissions --panel=dashboard --no-interaction` bila ada resource/page/widget baru dan ingin permission Shield untuk entitas baru tersebut. Shield tidak perlu diinstal ulang.
2. Jalankan `RoleSeeder` sebelum `UserSeeder`. Urutan itu sudah dipakai oleh `SanitraMasterSeeder`. `syncPermissions()` menyelaraskan izin Marketing, Finance, dan Teknis secara penuh: izin tambahan yang pernah diberikan manual kepada ketiga role tersebut akan dilepas saat seeder dijalankan ulang.
3. `super_admin.define_via_gate = false`, sehingga `RoleSeeder` menyelaraskan role super admin dengan semua permission `web` yang tersedia saat seeder berjalan. Permission baru yang dihasilkan Shield setelahnya memerlukan rerun `RoleSeeder`.
4. Perintah `shield:seeder` adalah ekspor dari kondisi database saat dijalankan dan tidak diperlukan untuk mapping yang sudah ditulis di `RoleSeeder`.

Kode seeder belum dieksekusi; database lokal dan server belum diubah. Test juga belum dijalankan sesuai aturan proyek. Konfigurasi dan route sedang di-cache di workspace ini, sehingga perubahan pada keduanya baru terlihat setelah cache aplikasi diperbarui.

## 9. Referensi package

- [Spatie: seeding role dan permission](https://spatie.be/docs/laravel-permission/v8/advanced-usage/seeding)
- [Spatie: permission melalui role](https://spatie.be/docs/laravel-permission/v8/basic-usage/role-permissions)
- [Spatie: cache permission](https://spatie.be/docs/laravel-permission/v8/advanced-usage/cache)
- [Filament Shield: konfigurasi dan perintah](https://filamentphp.com/plugins/bezhansalleh-shield?v=v3)

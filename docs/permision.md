# Role dan Permission Sanitra

**Status:** Pemetaan role kerja; seeder dan pengamanan action belum dibuat  
**Sumber:** Snapshot [`database/u301495856_sanitratest.sql`](../database/u301495856_sanitratest.sql), konfigurasi dan kode aplikasi per 31 Oktober 2026  
**Package terpasang:** `spatie/laravel-permission` 8.3.0, `bezhansalleh/filament-shield` 4.3.1, Filament 5.9.0

## 1. Cara kerja di aplikasi ini

- Spatie menyimpan role di `roles`, permission di `permissions`, hubungan role ke permission di `role_has_permissions`, dan hubungan user ke role di `model_has_roles`. `model_has_permissions` tersedia untuk permission langsung ke user, tetapi kosong pada snapshot server.
- Model `User` memakai trait `HasRoles`. `UserSeeder` memanggil `syncRoles()` untuk akun Marketing, Finance, dan Teknis.
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

`RoleSeeder` memakai `firstOrCreate()` untuk membuat role Marketing, Finance, dan Teknis dengan guard `web`. Seeder ini belum mengisi `role_has_permissions`. `UserSeeder` mengaitkan user contoh ke role tersebut lewat `syncRoles()`, sedangkan akun super admin dikelola melalui perintah Shield. Karena dump hanya mencatat satu hubungan user-role, hasil seeding lokal dan snapshot server belum sama.

Shield menyediakan perintah `shield:seeder` untuk mengekspor role dan permission yang sudah terkonfigurasi. Jika dijalankan berdasarkan snapshot ini, hasilnya hanya mencerminkan permission `super_admin`; perintah itu tidak dapat menyimpulkan hak akses Marketing, Finance, atau Teknis yang belum ditentukan.

## 4. Keputusan sebelum membuat seeder permission

1. Terapkan matriks izin per role di §6. Dump tidak dapat menjadi sumber pembagian hak akses karena Marketing, Finance, dan Teknis memiliki nol permission di snapshot itu.
2. Tentukan apakah seeder boleh menyelaraskan role secara penuh dengan `syncPermissions()` atau hanya menambahkan izin. `syncPermissions()` akan melepas izin lama yang tidak ada dalam matriks seeder; pilihan ini berpengaruh bila izin pernah diubah manual melalui Shield di server.
3. Putuskan cara menyelaraskan `super_admin` ketika Shield menghasilkan permission baru. Dengan konfigurasi sekarang, permission baru perlu diberikan ke role tersebut secara eksplisit.
4. Pastikan seeder idempoten, memakai nama permission dan guard `web` sebagai identitas, serta tidak mengandalkan ID dari dump. Urutan aman: sediakan permission, buat role, lalu kaitkan permission ke role. Gunakan API Spatie agar cache permission diperbarui.
5. Pisahkan pengisian role/permission dari penetapan user produksi. Seeder contoh saat ini memakai akun development; dump tidak boleh dijadikan sumber password atau penetapan user baru tanpa keputusan terpisah.

## 5. Inventaris tombol yang perlu diatur

Tombol CRUD bawaan Filament (`CreateAction`, `ViewAction`, `EditAction`, `DeleteAction`, restore, dan force delete) mengikuti policy resource terkait. Permission standar seperti `Create:TransSalesPayment` sudah dibuat Shield; seeder cukup memberikannya ke role yang tepat. Kondisi bisnis seperti status Draft atau Pending tetap perlu diperiksa selain permission.

| Halaman dan tombol | Pemeriksaan saat ini | Izin yang perlu dipakai saat implementasi |
| --- | --- | --- |
| Quotation — **Kirim Penawaran** | Kesiapan Draft | Role `Marketing` atau `super_admin`, permission aksi `Send:TransSalesQuotationHd`, dan syarat Draft |
| Quotation — **Setujui Penawaran** | Status Sent | Role `Marketing` atau `super_admin`, permission aksi `Approve:TransSalesQuotationHd`, dan status Sent |
| Quotation — **Revisi** | Status Sent | Role `Marketing` atau `super_admin`, permission aksi `Revise:TransSalesQuotationHd`, dan status Sent |
| Quotation — **Tolak** | Status Sent | Role `Marketing` atau `super_admin`, permission aksi `Reject:TransSalesQuotationHd`, dan status Sent |
| Sales Order — **Buat Invoice** | Izin Update SO dan termin siap tagih | Permission standar `Create:TransSalesInvoiceHd`; cek juga pada halaman tujuan dan saat submit. Tidak perlu tombol Add di daftar Invoice karena invoice dibuat dari termin SO. |
| Sales Order — **Buat Project** | Tombol dan halaman sudah ada; saat ini memakai izin Update SO | Alur ini tidak dipakai dalam rancangan berikutnya. Pembuatan Project akan dilakukan manual dari resource Project yang belum dibuat; tombol dan halaman SO perlu disesuaikan saat modul tersebut dikerjakan. |
| Sales Order — **Tutup SO** | Izin Update SO dan status SO | Permission aksi `Close:TransSalesOrderHd` |
| Invoice — **Batalkan Invoice** | Izin Update Invoice dan syarat status/payment | Permission aksi `Void:TransSalesInvoiceHd` |
| Payment — **Konfirmasi Pembayaran** | Izin Update Payment dan status Pending | Permission aksi `Confirm:TransSalesPayment` di halaman Payment dan relation manager Invoice |
| Payment — **Tolak Pembayaran** | Izin Update Payment dan status Pending | Permission aksi `Reject:TransSalesPayment` di kedua lokasi yang sama |
| Halaman Import Master Data — **Mulai Import** | Halaman memiliki permission `View:ImportMasterData`; aksi perlu diperiksa saat dijalankan | Khusus role `super_admin`. Tidak perlu permission `Import:MasterData` untuk role lain; pastikan method submit juga menolak user di luar `super_admin`. |

`Catat Pembayaran` pada Invoice memakai `CreateAction` dan sudah memeriksa `TransSalesPaymentResource::canCreate()`; izinnya adalah permission standar `Create:TransSalesPayment`. Tombol lihat dokumen dan Download PDF dapat mengikuti izin View data terkait. Export Excel perlu diputuskan terpisah: jika pengguna yang boleh melihat daftar belum tentu boleh mengunduh seluruh data, buat permission `Export` untuk aksi tersebut.

Untuk setiap tombol aksi khusus yang dipakai, pemeriksaan izin harus berlaku saat tombol ditampilkan **dan** saat request dijalankan. Halaman `CreateOrderInvoice` perlu memeriksa ulang izin pada method submit. Halaman `CreateOrderProject` yang sudah ada perlu ditangani sebagai alur lama ketika resource Project dibuat. Tabel ini adalah daftar pekerjaan otorisasi sebelum seeder permission dijalankan.

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

**Izin tombol khusus yang mengikuti mapping:** `Send`, `Approve`, `Revise`, dan `Reject` Quotation untuk Marketing; `Create:TransSalesInvoiceHd`, `Void:TransSalesInvoiceHd`, `Create:TransSalesPayment`, `Confirm:TransSalesPayment`, dan `Reject:TransSalesPayment` untuk Finance. `Create` Invoice/Payment adalah permission standar Shield; `Send`, `Approve`, `Revise`, `Reject`, `Void`, dan `Confirm` perlu dibuat sebagai custom permission. Profil Saya tidak memerlukan permission Shield tersendiri karena resource sudah membatasi query dan aksi ke user yang sedang login.

Widget yang dipakai saat ini: Marketing (`MarketingStatsWidget`, `MarketingConversionChartWidget`), Finance (`FinanceStatsWidget`, `FinanceRevenueChartWidget`, `FinanceInvoiceStatusChartWidget`), dan Teknis (`TeknisStatsWidget`). Semua widget ini saat ini memakai `canView()` berbasis role. Permission `View` widget yang mungkin dihasilkan Shield tidak otomatis menjadi pemeriksaan di method tersebut; seeder dan kode widget perlu dibuat konsisten bila nanti akses dashboard dipindahkan ke permission.

**Batas mapping saat ini:** Pembuatan Project nantinya manual melalui resource Project, bukan tombol dari halaman SO. Resource dan mapping transaksi Project belum dibuat. Tombol Tutup SO belum diberikan ke Marketing, Finance, atau Teknis; sementara hanya `super_admin` yang boleh. Untuk frasa “full akses” Client dan Quotation, cakupan `Restore`/`ForceDelete` dan aksi massal perlu dipastikan sebelum seeder final; izin destruktif tersebut tidak diasumsikan otomatis dari kata “full”.

## 7. Temuan kode dan penyesuaian sebelum mapping berlaku

1. `TransSalesQuotationHdResource::canEdit()` dan `canDelete()` saat ini hanya memeriksa status Draft, tanpa memanggil policy induk. Untuk edit/hapus, wajib cocokkan role `Marketing` (atau `super_admin`), permission `Update`/`Delete`, dan status Draft. Tombol transisi Quotation juga perlu role Marketing/super admin, permission aksinya, dan status yang sesuai. Finance tetap hanya boleh melihat.
2. Tombol dan halaman Buat Invoice masih memeriksa `Update:TransSalesOrderHd`. Finance memang boleh mengubah catatan SO, tetapi izin itu tidak boleh menjadi dasar otorisasi pembuatan Invoice. Gunakan `Create:TransSalesInvoiceHd` pada tombol, halaman, dan submit.
3. Tombol dan halaman Buat Project dari SO masih ada di kode dan memakai izin Update SO. Marketing dan Finance sama-sama dapat mengubah catatan SO, sehingga tombol itu berpotensi muncul untuk keduanya. Keduanya tidak boleh membuat Project dari tombol tersebut. Pada implementasi Project berikutnya, buat alur manual di resource Project dan sesuaikan atau hapus alur lama dari SO.
4. Konfirmasi/Tolak Payment di halaman Payment dan relation manager Invoice masih memakai izin `Update:TransSalesPayment`. Aksi tersebut perlu izin khusus yang sama di kedua lokasi. Resource Payment sendiri menonaktifkan edit/hapus umum, sehingga “full akses” Finance tidak berarti mengaktifkan kembali edit/hapus.
5. `UserProfileResource` sudah membatasi akses ke profil user login; role seeder tidak perlu memberi `View:User` atau `Update:User` hanya untuk fitur Profil Saya.

## 8. Urutan Shield dan seeder

1. Finalkan permission aksi khusus di konfigurasi Shield atau seeder dan pasang pemeriksaan izin pada tombol, halaman, serta method submit. Jangan jalankan seeder role sebelum nama permission yang dipakai kode tersedia.
2. Setelah resource/page/widget atau definisi custom permission berubah, jalankan `shield:generate` untuk panel `dashboard` dengan opsi **permissions saja** agar permission terbaru dibuat tanpa menimpa policy yang sudah dikustomisasi. Tidak perlu menjalankan `shield:setup`/instal ulang Shield setiap kali seed.
3. Jalankan seeder role-permission yang idempoten setelah daftar permission tersedia, lalu jalankan `UserSeeder` untuk akun contoh. `RoleSeeder` saat ini dipanggil sebelum `UserSeeder`, sehingga bisa dikembangkan atau diikuti seeder permission khusus pada urutan itu.
4. Karena `super_admin.define_via_gate = false`, sinkronkan permission baru ke role `super_admin` juga. Seeder dapat membuat permission yang belum ada dengan `firstOrCreate()` agar tidak bergantung pada ID dump, lalu memakai API Spatie untuk mengaitkan ke role dan menjaga cache.
5. Perintah `shield:seeder` adalah ekspor dari kondisi database saat dijalankan. Dump saat ini hanya memiliki izin `super_admin`, jadi perintah tersebut tidak menggantikan matriks role di §6.

Urutan di atas adalah rencana, belum dieksekusi. Belum ada database, policy, action, atau seeder yang diubah untuk mapping ini.

## 9. Referensi package

- [Spatie: seeding role dan permission](https://spatie.be/docs/laravel-permission/v8/advanced-usage/seeding)
- [Spatie: permission melalui role](https://spatie.be/docs/laravel-permission/v8/basic-usage/role-permissions)
- [Spatie: cache permission](https://spatie.be/docs/laravel-permission/v8/advanced-usage/cache)
- [Filament Shield: konfigurasi dan perintah](https://filamentphp.com/plugins/bezhansalleh-shield?v=v3)

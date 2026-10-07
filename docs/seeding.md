# Data awal master Sanitra

`SanitraMasterSeeder` mengisi seluruh 11 tabel master. `DatabaseSeeder` memanggil seeder ini, sehingga perintah seed standar juga mengisinya.

## Sumber dan batas data

Data perusahaan, kelompok layanan, produk penunjang, dan nama organisasi diambil dari [situs Sanitra](https://sanitra.co.id/), [halaman kontak](https://sanitra.co.id/kontak/), dan [portofolio](https://sanitra.co.id/portfolio/). Situs tidak menyediakan identitas pegawai, PIC pelanggan, detail kontak pelanggan, rekening bank, SKU, atau SOP proyek. Seeder memberi penanda jelas untuk data tersebut; jangan menganggapnya data resmi.

| Master | Isi awal | Status data |
| --- | --- | --- |
| Perusahaan | PT Sanitra Sapta Utama (ID 1), kontak publik, alamat, dan kode pos | Berdasarkan [halaman kontak resmi](https://sanitra.co.id/kontak/); logo belum diisi karena belum ada berkas lokal yang disetujui |
| Pegawai | Dua peran contoh Teknis dan Marketing, tanpa akun login | Placeholder, nonaktif |
| Klien | Tujuh nama organisasi dari portofolio | Nama berdasarkan situs; kontak dan alamat placeholder, nonaktif sampai diverifikasi |
| Tipe proyek | IPAL, perizinan lingkungan, TPS limbah B3, pest control, pengadaan penunjang IPAL | Kategori berdasarkan layanan situs |
| Alur proyek | 22 tahapan dengan bobot 100% per tipe | Template contoh; sesuaikan dengan SOP |
| Kategori item | Bakteri, bahan kimia, peralatan, dan tiga kelompok jasa | Kategori berdasarkan situs |
| Satuan | Jam, hari, bulan (`waktu`); unit, liter, kilogram, paket (`barang`) | Satuan kerja contoh |
| Item dan satuan item | Delapan entri katalog generik, masing-masing satu satuan dasar | Contoh katalog; bukan SKU, spesifikasi, atau harga resmi |
| Rekening bank | Satu rekening berlabel **REKENING CONTOH - JANGAN DIGUNAKAN** | Placeholder, nonaktif; nomor `0000000000` bukan rekening pembayaran |
| Status | 25 status dari enum quotation, project, detail project, term, invoice, dan payment | Mengikuti definisi aplikasi |

Email dengan domain `.invalid`, tanda `-` pada telepon, dan teks `Belum diverifikasi` sengaja dipakai agar data yang belum tersedia tidak terlihat seperti kontak asli. Verifikasi dan perbarui sebelum mengaktifkan klien, pegawai, atau rekening.

## Menjalankan

Setelah migrasi penambahan kolom Company selesai:

```bash
php artisan db:seed --class=SanitraMasterSeeder --no-interaction
```

Atau jalankan `php artisan db:seed --no-interaction` untuk memanggil `DatabaseSeeder`. Seeder membuat pengguna audit internal `master-seeder@sanitra.invalid` dengan kata sandi acak yang tidak ditampilkan. Ini hanya untuk mengisi `created_by`, bukan akun operasional.

Seeder dapat dijalankan ulang. Baris dicocokkan berdasarkan identitas stabil dan baris yang sudah ada (termasuk yang soft deleted) tidak dibuat ulang. Khusus Company ID 1, `address` dan `postal_code` yang masih kosong diisi dari halaman kontak resmi; nilai yang sudah diisi manual tetap dipertahankan. Dua jabatan pegawai contoh lama juga dinormalkan seperti dijelaskan di bawah. Master lainnya tidak diubah. Jika katalog awal perlu diperbarui, edit melalui panel atau migrasi data tersendiri.

Seeder mensyaratkan PT Sanitra Sapta Utama menempati ID 1, aktif, dan tanpa induk. Jika ID 1 sudah dipakai perusahaan lain atau Sanitra ada di ID berbeda, seeder berhenti dengan pesan kesalahan agar konflik data diselesaikan secara sadar. Migration penambahan alamat, kode pos, logo, dan indeks unik kode Company sudah disiapkan tetapi belum dijalankan.

Pada dua pegawai contoh saja, seeder mengubah jabatan lama `Operasional` → `Teknis` dan `Penjualan` → `Marketing` saat dijalankan ulang. Jabatan pegawai lain tidak diubah. Import Employee memerlukan Company yang sudah ada dan hanya menerima empat jabatan dari enum aplikasi.

Kode otomatis `COM`, `EMP`, `CLI`, `PTY`, `IC`, `ITM`, dan `BNK` dibuat melalui `GenerateCodeService` hanya untuk baris baru. Sebelum seed, seeder menyelaraskan `sys_code_sequence` dengan nomor tertinggi yang sudah ada, termasuk baris soft deleted. Nomor berikutnya tetap meningkat saat seed dijalankan ulang atau setelah ada kode master lama yang lebih tinggi. Kode satuan dan status merupakan kode tetap, bukan nomor otomatis.

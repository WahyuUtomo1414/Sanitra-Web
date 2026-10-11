# UI Conventions

Panduan bersama untuk layout form, input, tabel, detail, dan format data di aplikasi Sanitra. Aturan ini menjaga tampilan tetap konsisten tanpa memaksa semua modul memakai layout yang sama.

## Layout Form

- Urutkan field mengikuti alur kerja pengguna. Letakkan pilihan yang menentukan field lain lebih dulu, lalu tampilkan nilai turunannya setelah pilihan tersebut.
- Kelompokkan field berdasarkan konteks. Pisahkan informasi utama dari ringkasan keuangan bila pengguna perlu membaca keduanya sebagai bagian yang berbeda.
- Gunakan dua kolom untuk field pendek yang berpasangan. Field catatan, alamat, atau teks panjang memakai lebar penuh.
- Tampilkan field relasi sebagai select yang bisa dicari jika pilihannya berasal dari master data. Bila pembuatan data baru merupakan bagian dari alur form, sediakan action tambah di dekat select dan gunakan kembali schema master datanya.
- Beri label yang singkat dan jelas dalam bahasa Indonesia. Tambahkan placeholder atau helper text hanya saat membantu pengguna mengisi field.
- Tandai field wajib melalui validasi `required()`. Field opsional harus menerima nilai kosong di form dan pada service.

## Field Otomatis dan Read-only

- Tampilkan nomor dokumen dan hasil hitungan sebagai read-only atau disabled agar pengguna tahu nilainya tanpa bisa mengubahnya.
- Jangan mengandalkan state form untuk aturan penting. Generate nomor dan hitung nilai final di service atau lifecycle server-side.
- Field tampilan yang tidak boleh ditulis kembali menggunakan `dehydrated(false)`. Data hasil hitungan tetap dihitung ulang oleh server sebelum disimpan.
- Perhitungan langsung di form hanya untuk memberi preview yang responsif. Service tetap menjadi sumber nilai yang disimpan.

## Format Rupiah

- Gunakan format Indonesia dengan pemisah ribuan titik, misalnya `Rp 120.000.000`.
- Sembunyikan angka desimal di tampilan. Jangan membulatkan atau mengubah presisi nilai yang disimpan untuk hasil perhitungan otomatis.
- Input nominal menggunakan komponen `MoneyInput::make('field_name')`. Gunakan `MoneyInput::display('field_name')` untuk nominal otomatis atau read-only.
- Untuk input bermask, state awal yang diberikan ke mask harus berupa angka mentah tanpa pemisah ribuan. Biarkan mask menampilkan pemisah agar nilai seperti `12.000.000` tidak dibaca ulang sebagai `12` saat form edit dibuka.
- Tabel, infolist, dan PDF memakai `IndonesianMoneyFormatter::formatRupiah($amount)` agar formatnya konsisten.
- Untuk nilai uang editable yang dimasukkan pengguna, gunakan rupiah penuh di input. Biarkan service menghitung nilai turunan dengan presisi database.

## Tabel, Detail, dan Status

- Tabel menampilkan kolom yang membantu identifikasi dan keputusan utama. Detail tambahan dapat berada di halaman detail atau kolom yang bisa disembunyikan.
- Format nominal, tanggal, dan status harus sama di tabel, halaman detail, dan dokumen yang diunduh.
- Tampilkan action sesuai status record agar pengguna melihat langkah yang tersedia. Otorisasi dan validasi status tetap diperiksa server-side.

## Reuse dan Perubahan

- Gunakan komponen atau formatter bersama untuk pola yang berulang, seperti input Rupiah. Hindari menyalin mask dan format angka ke setiap resource.
- Layout tetap mengikuti kebutuhan alur bisnis tiap modul. Buat komponen layout reusable hanya ketika struktur dan perilakunya memang berulang.
- Saat mengubah UI, pertahankan test fokus pada hasil bisnis dan perilaku penting. Hindari mengunci test pada judul atau teks presentasi yang mudah berubah.

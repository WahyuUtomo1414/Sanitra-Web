# Rencana Test E2E — Quotation sampai Sales Order

**Status:** Rencana; spec belum dibuat atau dijalankan.  
**Cakupan:** Dua skenario Marketing mencakup `Draft`, `Sent`, `Rejected`, revisi kembali ke `Draft`, dan `Approved` hingga Sales Order terbentuk.  
**Acuan:** [strategi alur bisnis](business-flow-strategy.md), [alur Quotation](../bisnis-flow/quotation.md), [alur Sales Order dan termin](../bisnis-flow/sales-order-term.md), [keamanan testing](../testing-safety.md).

## 1. Batas skenario

Satu spec Playwright yang direncanakan: `tests/e2e/flows/quotation-to-so.spec.ts`. Jalankan skenario sebagai **Marketing** (`chromium-marketing`); project `setup` tetap menyiapkan sesi login. Skenario berhenti setelah SO `draft` terbentuk. Invoice, pembayaran DP, perubahan SO ke `open`, dan Project menjadi cakupan berikutnya.

Test ini memakai server E2E `127.0.0.1:8011` dengan SQLite sementara dari `tests/e2e/start-server.sh`. Sebelum menjalankannya nanti, pastikan guard isolasi database pada script tetap aktif. Jangan arahkan test mutasi data ke server aplikasi biasa atau MySQL `sanitra`.

## 2. Data skenario

| Data | Nilai rencana | Alasan |
| --- | --- | --- |
| Klien | Buat Klien aktif lewat modal **Tambah Klien** di form Quotation. Isi nama dan email unik per run, telepon, dan alamat. | Sekalian menguji pembuatan Klien dari form Quotation. |
| Tipe Proyek | `Konsultan` | Disediakan aktif oleh `ProjectSeeder`; perusahaan penerbit terisi dari tipe proyek. |
| Item | `Jasa Survei IPAL` dengan unit `Hari` | Keduanya tersedia dari `ItemSeeder`. |
| Detail item | Jumlah `2`, harga satuan `Rp 1.000.000`, diskon `0` | Dengan PPN `Tanpa PPN`, subtotal dan grand total yang diharapkan `Rp 2.000.000`. |
| Termin DP | `DP`, `30%`, threshold `0%` | Nilai yang diharapkan `Rp 600.000`; status SO `Siap Ditagih`. |
| Termin lanjutan | `Pelunasan`, `70%`, threshold `100%` | Nilai yang diharapkan `Rp 1.400.000`; status SO `Menunggu`. |

Gunakan nama Klien yang unik setiap run dan cari Quotation/SO berdasarkan **nomor Quotation yang dibuat test**, bukan baris pertama tabel atau ID tetap. Hindari ketergantungan pada data yang ditulis test lain. Gunakan nilai uang yang diketahui dari input di atas; jangan menghitung nilai harapan dengan helper aplikasi yang sedang diuji.

## 3. Skenario penolakan

1. Buat Quotation baru dengan Klien aktif dari modal **Tambah Klien**, satu item, dan termin sesuai data skenario. Simpan sebagai **Draft**, lalu kirim dan pastikan status menjadi **Dikirim**.
2. Klik **Tolak**, lalu konfirmasi dengan **Ya, tolak**. Pastikan status berubah menjadi **Ditolak** dan aksi Ubah, Revisi, serta Setujui tidak tersedia.
3. Cari nomor Quotation tersebut di daftar Sales Order dan pastikan hasilnya kosong. **Ditolak** adalah status akhir untuk Quotation ini.

## 4. Skenario revisi, persetujuan, dan pembuatan Sales Order

Buat Quotation dan Klien baru untuk skenario ini agar bisa dijalankan sendiri, termasuk ketika skenario penolakan dilewati.

| Langkah UI | Pemeriksaan |
| --- | --- |
| Marketing membuka `/dashboard/quotations/create`, lalu membuat Klien aktif lewat tombol **Tambah Klien** di field **Klien** | Setelah Klien disimpan, modal tertutup dan Klien baru otomatis terpilih di form Quotation. |
| Pilih `Konsultan`, isi tanggal dan PPN `Tanpa PPN`, lalu simpan Quotation dengan Klien yang baru terpilih | Halaman berpindah ke **Edit Quotation** (perilaku `CreateTransSalesQuotationHd`), nomor Quotation tercatat, status `Draft`, dan Klien baru tersimpan pada Quotation. |
| Tambah satu baris item dan dua termin melalui relation manager di halaman Edit | Baris item, jumlah, unit, harga, dan total tampil; total persentase termin `100%` dengan tepat satu threshold `0%`; grand total `Rp 2.000.000`. |
| Buka halaman Lihat Quotation | Tombol `Kirim Penawaran` muncul setelah syarat item dan termin terpenuhi. Belum ada SO untuk nomor Quotation ini. |
| Klik `Kirim Penawaran`, lalu konfirmasi dengan `Ya, kirim` | Status menjadi `Dikirim`; action edit dan tambah/ubah/hapus item/termin tidak tersedia; tombol `Setujui Penawaran` muncul. |
| Klik `Revisi`, lalu konfirmasi dengan `Ya, revisi` | Status kembali ke `Draft` dengan nomor Quotation yang sama. Form dan item/termin dapat diedit kembali. |
| Ubah Catatan Quotation dan simpan, lalu kirim ulang | Catatan revisi tersimpan, status kembali menjadi `Dikirim`, dan pencarian berdasarkan nomor Quotation ini masih belum menemukan SO. |
| Klik `Setujui Penawaran`, lalu konfirmasi dengan `Ya, setujui` | Status Quotation menjadi `Disetujui`; notifikasi sukses muncul; action revisi/ubah tidak tersedia. |
| Buka `/dashboard/sales-orders` dan cari nomor Quotation sumber | **Tepat satu** SO muncul, status order `Menunggu DP` (`draft`), nomor SO terisi, dan `Quotation Sumber` sama dengan Quotation yang disetujui. |
| Buka detail SO | Klien, perusahaan, tipe proyek, jenis PPN, catatan hasil revisi, dan grand total cocok dengan Quotation; satu item SO menyalin item, unit, jumlah, harga, diskon, dan total; termin DP `Siap Ditagih`, termin lanjutan `Menunggu`, dengan nominal yang diharapkan. |

Label status pada UI mengikuti enum saat ini: Quotation `Sent` ditampilkan sebagai **Dikirim**, `Approved` sebagai **Disetujui**; SO `Draft` sebagai **Menunggu DP**; termin `ReadyToBill` sebagai **Siap Ditagih**. Pilih locator berdasarkan label yang benar-benar terlihat saat implementasi, terutama untuk tab relation manager dan modal Filament.

## 5. Assertion yang paling penting

1. **Transisi Quotation:** `Draft → Sent → Rejected` pada data pertama, dan `Draft → Sent → Draft → Sent → Approved` pada data kedua. Revisi mempertahankan nomor Quotation.
2. **Efek otomatis:** SO belum ada sebelum approval, lalu ada **satu** SO yang merujuk nomor Quotation yang sama setelah approval. Tidak ada aksi buat SO manual di UI.
3. **Salinan data:** header dan item SO sama dengan Quotation sumber; total SO tetap `Rp 2.000.000`.
4. **Termin:** DP threshold `0%` menjadi `Siap Ditagih`, termin threshold `100%` tetap `Menunggu`; nominalnya `Rp 600.000` dan `Rp 1.400.000`.
5. **Kunci setelah approval:** Quotation `Disetujui` tidak menawarkan aksi edit, revisi, atau pengubahan item dan termin.

Periksa perubahan pada UI setelah tiap action dengan locator dan assertion Playwright, bukan `waitForTimeout()` tetap. Jangan mengandalkan urutan test lain atau nomor dokumen yang sudah ada.

## 6. Catatan implementasi

- `CreateTransSalesQuotationHd` mengarahkan hasil create ke halaman **Edit**, tempat item dan termin dikelola. Skenario tidak boleh mengasumsikan redirect ke halaman View seperti resource master.
- `QuotationReadinessService` mensyaratkan minimal satu item, total termin tepat `100%`, dan tepat satu DP threshold `0%` sebelum tombol Kirim tersedia.
- `ApproveQuotationService` dan `CreateSalesOrderFromQuotationService` berjalan dalam transaksi database. Test E2E ini memeriksa hasil yang terlihat; pemeriksaan kegagalan atomik secara terperinci dapat dibuat pada feature test service tersendiri bila nanti diminta.
- `RecalculateQuotationService` memperbarui nominal termin dari grand total Quotation sebelum approval. Setelah SO terbentuk, test membandingkan nominal yang tampil dengan nilai input yang diketahui.
- Dokumen strategi lama menyebut path `/dashboard/trans-sales-order-hds`; resource saat ini menetapkan slug `/dashboard/sales-orders`. Gunakan slug yang berlaku saat spec dibuat.
- Belum ada test yang dibuat atau dijalankan sebagai bagian dari penulisan dokumen ini.

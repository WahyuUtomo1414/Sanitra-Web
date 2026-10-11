# PRD — Aplikasi Web Internal Sanitra

**Status:** Draft kerja · **Tanggal:** 3 Oktober 2026  
**Acuan utama:** [SRS Aplikasi Web Sanitra](../../../Berkas/SRS_Aplikasi_Web_Sanitra.pdf), versi 1.0 (draft, 2 Oktober 2026)  
**Acuan visual data/proses:** [ERD](../../../Berkas/Sanitra-ERD.drawio%20%282%29.png) dan [flowchart](../../../Berkas/Sanitra_FlowChart_v2.drawio.png)

> Dokumen ini masih draft berdasarkan SRS v1.0. Untuk alur transaksi yang sedang diimplementasikan (Sales Order, DP wajib, pembayaran invoice penuh, dan pemicu Project), gunakan [bisnis-flow/full-flow.md](bisnis-flow/full-flow.md) beserta dokumen modulnya sebagai acuan terbaru.

Dokumen ini menerjemahkan SRS menjadi kebutuhan produk yang mudah dipakai untuk menyusun backlog dan UAT. SRS menjadi acuan utama untuk detail kebutuhan dan aturan bisnis.

## 1. Tujuan produk

Menyediakan satu aplikasi internal untuk mengelola proses dari penawaran client sampai project selesai dan seluruh tagihan lunas. Tim Marketing, Teknis, dan Finance bekerja pada data yang sama, sementara Owner dapat melihat keseluruhan proses dan mengelola akses.

Hasil yang diharapkan:

- Status quotation, progress project, termin, invoice, dan pembayaran dapat ditelusuri tanpa rekap manual terpisah.
- Termin siap ditagih terdeteksi saat syarat progress terpenuhi; Finance tetap membuat invoice secara eksplisit.
- Perubahan tahap, koreksi, dan pembayaran memiliki jejak audit yang dapat diperiksa.
- Pekerjaan dianggap selesai melalui skenario UAT berdasarkan FR dan BR di SRS.

## 2. Pengguna dan akses

| Peran | Kebutuhan utama | Akses inti |
| --- | --- | --- |
| Owner / Super Admin | Memantau semua data dan mengelola pengguna serta hak akses | Semua modul; override tahap terkunci dengan alasan |
| Marketing | Mengelola client dan quotation serta mencatat respons client | Master terkait, quotation, lihat project dan penagihan |
| Teknis | Mengelola project dan progres tahap | Project, tahap, log monitoring; lihat quotation |
| Finance | Menagih dan mencatat pembayaran | Termin siap tagih, invoice, payment, outstanding |

Hak akses berlaku per role, bukan per company. Satu user boleh tidak terhubung ke employee; satu employee terhubung ke paling banyak satu user.

## 3. Ruang lingkup produk

### 3.1 Master data dan administrasi

- CRUD company dengan alamat lengkap, kode pos, dan logo untuk identitas/kop surat. PT Sanitra Sapta Utama adalah perusahaan utama ID 1 yang wajib aktif dan tidak dapat dihapus; perusahaan lain otomatis berinduk ke ID 1. PIC employee harus berasal dari company yang sama. Semua company berbagi master client, item, bank account, dan unit. `company_id` pada dokumen transaksi menandai penerbit dokumen. (FR-01)
- CRUD employee, client dengan kode otomatis dan PIC internal, tipe project dan template workflow. Jabatan employee dipilih dari Owner, Marketing, Teknis, atau Finance; pemetaan jabatan ke permission ditentukan terpisah. Total bobot tahap untuk setiap tipe project wajib 100 sebelum template dipakai. (FR-02–04)
- Ekstensi yang disetujui: satu client dapat memiliki banyak kontak orang dan banyak dokumen; satu employee juga dapat memiliki banyak dokumen. Email/telepon pada client tetap menjadi kontak umum, sedangkan PIC employee tetap PIC internal. Dokumen memakai tabel bersama `ms_document` agar kelak dapat dipakai project. UI dokumen tersedia pada client dan employee. Nama dokumen berupa teks bebas, catatan opsional, dan file disimpan private. Referensi pemilik diisi otomatis oleh relasi aplikasi.
- CRUD kategori item, item, unit yang diisi manual, dan rekening bank penerima. Item dipakai sebagai katalog jasa untuk quotation, tanpa stok atau tipe barang/jasa tambahan. Unit pertama yang ditambahkan menjadi unit dasar; unit lain menjadi pilihan alternatif. Tiap Unit Item memiliki faktor konversi dengan nilai awal 1; konversi unit dasar selalu 1. Import Item wajib menyertakan kode unit dasar. (FR-05–06)
- Master data dapat dinonaktifkan; data nonaktif tidak tersedia untuk transaksi baru, tetapi tetap terlihat pada histori. (FR-07)
- Login, kelola user, role, dan permission; menu serta aksi mengikuti permission. (FR-40)

### 3.2 Quotation

- Marketing membuat quotation Draft dengan item, jumlah, satuan, harga, diskon, PPN, dan skema termin. Sistem menghitung subtotal, PPN, dan grand total. (FR-10–11)
- Persentase seluruh termin harus tepat 100; persentase dan threshold progress berupa bilangan bulat. Full Payment direpresentasikan sebagai satu termin 100% dengan threshold 0% atau 100%. (BR-01)
- Alur status: Draft → Sent → Approved atau Rejected. Revisi dari Sent menghasilkan versi baru dengan nomor revisi bertambah dan versi lama tetap tersimpan. Approved/Rejected tidak dapat diedit. (FR-12–13, BR-11–12)
- Quotation dapat dicetak ke PDF dengan company penerbit, client, item, dan termin. (FR-14)

### 3.3 Project dan monitoring

- User membuat project melalui form dengan memilih quotation Approved; satu quotation hanya dapat dipakai untuk satu project. Client, company, tipe project, dan tahap disalin dari quotation serta master workflow. (FR-20–21)
- Teknis memperbarui status dan tanggal tahap. Progress project = jumlah bobot tahap berstatus Completed. Setiap perubahan mencatat status/progress sebelum dan sesudah, user, dan waktu. (FR-22–23, BR-03)
- Status tahap yang mundur memerlukan alasan. Tahap yang telah terkunci karena penagihan hanya boleh diubah oleh pihak berizin override, wajib dengan alasan dan log. Rework sesudah penagihan dicatat tanpa mengubah progress. (FR-24–25, BR-07–08)
- Sejak termin pertama ditagih, tahap tidak dapat ditambah, dihapus, atau diubah bobotnya. Project Closed otomatis bila progress 100% dan semua termin Paid. (BR-06, BR-13)

### 3.4 Termin, invoice, dan pembayaran

- Termin dengan threshold 0% langsung Ready to Bill saat project dibuat. Termin lain berubah Ready to Bill ketika progress mencapai threshold. Finance menerima email untuk termin yang baru siap ditagih. (FR-30, BR-04)
- Finance dapat menggabungkan satu atau lebih termin Ready to Bill dari project yang sama dalam satu invoice. Satu termin tidak boleh muncul pada lebih dari satu invoice aktif. Pembuatan invoice sekaligus mengubah termin menjadi Invoiced dan mengunci tahap Completed dalam satu transaksi database. (FR-31–32, BR-05)
- Finance mencatat pembayaran sebagian atau penuh beserta bukti transfer. Pembayaran yang dihitung adalah yang Confirmed; invoice Paid ketika jumlahnya mencapai grand total. Bukti dapat ditolak. (FR-33, status payment pada SRS bagian 6)
- Invoice Void mengembalikan termin ke Ready to Bill dan membuka hanya kunci tahap yang tidak tercakup invoice aktif lain. Invoice tidak dapat diubah setelah ada pembayaran. (FR-34, BR-09, BR-11)
- Finance melihat outstanding, sisa tagihan, dan jatuh tempo; invoice dapat dicetak ke PDF dengan company penerbit serta rekening tujuan. (FR-35–36)

### 3.5 Dashboard, ekspor, dan notifikasi

- Dashboard menampilkan jumlah quotation per status, project berjalan dan progress, termin Ready to Bill, serta total outstanding. Tampilan mengikuti akses role. (FR-41)
- Setiap daftar master dan transaksi dapat diekspor ke `.xlsx` dengan filter aktif, termasuk rentang tanggal. (FR-43)
- Email ke Teknis dan PIC project dikirim saat termin pertama Paid. Jika termin pertama bukan DP (threshold > 0), email dikirim saat quotation Approved. Email ke Finance dikirim saat termin Ready to Bill. (SRS bagian 8)

## 4. Alur utama dan kriteria penerimaan

| Langkah | Pemicu / tindakan | Hasil yang harus terlihat |
| --- | --- | --- |
| 1. Penawaran | Marketing menyimpan Draft, mengirim, lalu mencatat respons | Versi dan status quotation terlacak; total serta persentase termin valid |
| 2. Project | User memilih quotation Approved pada form create Project | Hanya satu project per quotation; tahap mengikuti workflow yang disetujui |
| 3. Pelaksanaan | Teknis mengubah tahap | Progress dihitung dari bobot Completed; perubahan dan alasan tercatat |
| 4. Siap tagih | Progress mencapai threshold | Termin Ready to Bill tepat sekali dan Finance menerima notifikasi |
| 5. Invoice | Finance memilih termin Ready to Bill | Invoice terbentuk, termin Invoiced, tahap Completed terkunci secara atomik |
| 6. Pembayaran | Finance memverifikasi pembayaran | Outstanding berkurang; invoice dan termin Paid setelah lunas |
| 7. Penutupan | Progress 100% dan semua termin Paid | Project Closed otomatis |

Daftar FR-01–07, FR-10–14, FR-20–25, FR-30–36, FR-40–43 dan BR-01–14 pada SRS adalah checklist UAT rinci. UAT maksimal dua putaran revisi sesuai SRS; perubahan di luar scope dicatat sebagai Change Request.

## 5. Kebutuhan nonfungsional

- Web responsif untuk desktop dan smartphone; Chrome, Edge, dan Safari versi terbaru. Antarmuka berbahasa Indonesia dan nominal Rupiah. (NFR-01, NFR-07)
- Login wajib, password di-hash, izin dibatasi per role, dan akses production memakai HTTPS. (NFR-02)
- Data master dan transaksi menyimpan pembuat, pengubah, waktu, serta soft delete sesuai aturan SRS. Log monitoring tidak dapat diedit atau dihapus; koreksi dibuat sebagai log baru. (NFR-03, BR-10)
- Pembuatan invoice, pengubahan status termin, dan penguncian tahap harus berada dalam satu transaksi database. (NFR-04)
- Deployment pada satu server production dengan backup database harian. Penyimpanan backup di luar server bergantung layanan yang disediakan Sanitra. (NFR-05–06)

## 6. Di luar scope

Payment gateway, e-Faktur, integrasi akuntansi, jurnal/buku besar/laporan keuangan, aplikasi mobile native, biaya server/domain/layanan pihak ketiga, dan workflow yang tidak tercantum dalam SRS.

Rancangan tabel, model, relasi, constraint, dan urutan migration ada di [database.md](database.md).

# Strategi Testing — Per Alur Bisnis

Lihat index di [`playwright-strategy.md`](./playwright-strategy.md) untuk konteks stack & environment.

Berbeda dari `resource-testing-strategy.md` (testing satu resource secara terisolasi), test di sini **lintas-resource dan lintas-role**, mengikuti state machine riil sesuai `docs/bisnis-flow/full-flow.md`. Tujuannya memverifikasi bahwa *side effect otomatis* antar service (auto-create SO, auto Ready to Bill, auto SO jadi `open`, dst) benar-benar terjadi lewat UI, bukan cuma di level unit test PHP.

**Batas scope:** implementasi saat ini (lihat memory `project_business_flow`) berhenti begitu SO menjadi `open`. Jangan tulis test untuk Create Project / progress project — modul itu masih schema-only.

## 1. Peta Status Lintas Entitas (acuan assertion)

Diringkas dari `docs/bisnis-flow/full-flow.md` §4.1 — ini yang di-assert di tiap langkah flow test:

| Entitas | Status awal | Trigger | Status akhir | Siapa yang trigger |
|---|---|---|---|---|
| Quotation | Draft | Marketing klik "Kirim Penawaran" | Sent | Marketing |
| Quotation | Sent | Client Approve (disimulasikan Marketing klik "Setujui") | Approved | Marketing |
| Quotation | Sent | Client Reject | Rejected | Marketing |
| Quotation | Sent | Revisi | Draft (balik) | Marketing |
| Sales Order | (baru dibuat) | Quotation Approved — otomatis oleh `CreateSalesOrderFromQuotationService` | Draft | Sistem |
| Termin DP | Draft | SO dibuat (threshold 0%) — otomatis | Pending → **Ready to Bill langsung** | Sistem |
| Invoice DP | (baru dibuat) | Finance klik "Buat Invoice" dari termin Ready to Bill | Unpaid | Finance |
| Payment | (baru dicatat) | Finance klik "Catat Pembayaran" | Pending | Finance |
| Payment | Pending | Finance klik "Konfirmasi Pembayaran" | Confirmed | Finance |
| Invoice DP | Unpaid | Payment Confirmed penuh — otomatis oleh `ConfirmPaymentService` | Paid | Sistem |
| Termin DP | Ready to Bill | Invoice dibuat | Invoiced → **Paid** (saat payment confirmed) | Sistem |
| Sales Order | Draft | Invoice DP Paid — otomatis oleh `ConfirmPaymentService` | **Open** | Sistem |

Setiap transisi "otomatis oleh Sistem" WAJIB di-assert di test — ini bagian paling rawan regresi karena tidak ada tombol UI yang user klik, cuma efek samping dari action lain.

## 2. Skenario per File Test

### 2.1 `flows/quotation.spec.ts` — acuan `bisnis-flow/quotation.md`

Role: Marketing.

1. **Happy path Draft → Sent**: buat quotation baru (pilih client, company, project type, isi item lines, isi termin total 100% termasuk 1 termin DP threshold 0%), klik "Kirim Penawaran" → assert status jadi `Sent`, tombol Kirim hilang, tombol Setujui/Revisi/Tolak muncul.
2. **Validasi BR-01**: coba kirim quotation dengan total termin ≠ 100% → harus ditolak dengan error, status tetap `Draft`.
3. **Approved**: dari status `Sent`, klik "Setujui Penawaran" → assert status `Approved`. **Assert efek samping**: Sales Order baru otomatis terbentuk (lihat §2.2).
4. **Rejected**: dari status `Sent`, klik "Tolak" → assert status `Rejected`, tidak ada SO dibuat.
5. **Revisi**: dari status `Sent`, klik "Revisi" → assert status balik ke `Draft`, histori status tercatat (kalau ada UI log/timeline, assert juga).
6. **BR-11 — Edit hanya saat Draft**: assert field quotation terkunci/read-only begitu status bukan `Draft`.

### 2.2 `flows/quotation-to-so.spec.ts` — acuan `bisnis-flow/sales-order-term.md`

Lanjutan dari quotation Approved. Role: Marketing (approve) lalu switch ke Finance (lihat SO).

1. Approve quotation (reuse step dari §2.1) → assert SO baru muncul di list `/dashboard/trans-sales-order-hds` dengan status `Draft`, HD & item (DT) tersalin identik dari quotation (BR-17: satu transaksi DB, item sama).
2. **Assert termin DP otomatis Ready to Bill**: buka detail SO, termin dengan threshold 0% harus langsung berstatus `Ready to Bill` tanpa action manual (BR-04).
3. **Assert BR-02**: nilai (`amount`) termin di SO sama dengan hasil kunci dari quotation, tidak berubah walau grand total quotation sumber "berubah" (quotation sudah Approved, seharusnya read-only juga).
4. **Assert relasi 1:1**: FK `quotation_hd_id` di SO unique — quotation yang sudah Approved tidak bisa menghasilkan SO kedua.

### 2.3 `flows/so-term-to-invoice.spec.ts` — acuan `bisnis-flow/invoice.md`

Role: Finance.

1. Dari SO dengan termin DP `Ready to Bill`, klik "Buat Invoice" → assert Invoice baru terbentuk dengan status `Unpaid`, baris invoice (`DT`) merujuk termin DP yang benar.
2. Assert termin DP berubah status `Ready to Bill` → `Invoiced`.
3. **BR-11**: assert invoice bisa diedit selama belum ada payment; setelah ada payment, field terkunci.
4. **Negative test**: Marketing & Teknis tidak boleh lihat menu Invoice sama sekali (lihat `resource-testing-strategy.md` §3).

### 2.4 `flows/invoice-to-payment.spec.ts` — acuan `bisnis-flow/payment.md`

Role: Finance.

1. Dari Invoice `Unpaid`, klik "Catat Pembayaran" → assert Payment baru status `Pending`, invoice masih `Unpaid` (BR: Payment Pending tidak mengurangi sisa tagihan).
2. Klik "Konfirmasi Pembayaran" → assert Payment jadi `Confirmed`.
3. **Assert efek samping berantai (otomatis oleh `ConfirmPaymentService`)**:
   - Invoice DP → `Paid`.
   - Termin DP → `Paid`.
   - **Sales Order → `Open`** (ini assertion paling penting di seluruh flow — kalau gagal, berarti ada regresi di service orkestrasi).
4. **Alternate path — Tolak Pembayaran**: dari Payment `Pending`, klik "Tolak Pembayaran" → assert Payment `Rejected`, Invoice & Termin & SO status TIDAK berubah.

### 2.5 `flows/full-flow.spec.ts` — acuan `bisnis-flow/full-flow.md` §2 (flowchart)

End-to-end satu skenario panjang, gabungan §2.1–2.4 dalam satu test (atau serangkaian test berurutan dalam satu `describe` dengan state dibagi), switch role di titik yang tepat:

```text
Marketing: buat Quotation → Kirim → Setujui
  → [assert] SO auto Draft, termin DP auto Ready to Bill
Finance: Buat Invoice DP → Catat Payment → Konfirmasi Payment
  → [assert] Invoice Paid, Termin Paid, SO auto Open
```

Ini test yang paling berharga untuk regresi, tapi juga paling lambat — jalankan sebagai suite terpisah (`@e2e-full` tag) supaya bisa di-skip saat iterasi cepat dan cuma dijalankan sebelum deploy atau di smoke test staging.

## 3. Hal yang HARUS diverifikasi di tiap flow test (bukan cuma status akhir)

- **Role yang salah tidak bisa trigger action** — misal Marketing tidak boleh buat Invoice, Finance tidak boleh approve Quotation.
- **Guard status** — action custom (Kirim, Setujui, Buat Invoice, dst) tombolnya hilang/disabled kalau status entity tidak sesuai syarat (lihat tabel di `resource-testing-strategy.md` §3.1).
- **Transaksi atomik** — kalau satu langkah dalam service gagal (misal validasi gagal di tengah create SO), tidak ada data parsial yang kebentuk (sulit di-test murni dari E2E, tapi minimal pastikan error state tidak meninggalkan UI di kondisi aneh).
- **Notification/email** — `TermReadyToBillNotification` terpicu saat termin DP jadi Ready to Bill (sync, karena shared hosting tanpa queue — lihat memory `project_hosting`). Kalau mau di-assert, cek lewat mailtrap/log driver di environment test, bukan assert UI semata.

# Testing Safety — Lokal

**Status:** Wajib dibaca sebelum menjalankan test apa pun di mesin lokal.

## 1. Insiden 11 Oktober 2026 — rekap singkat

- `php artisan test --filter=QuotationFlowTest` dieksekusi beberapa kali di sesi debug `MoneyInput`.
- `phpunit.xml` memaksa `DB_CONNECTION=sqlite` + `DB_DATABASE=:memory:` via `<php><env .../></php>`, tetapi override ini **hanya efektif ketika Laravel membaca `env()` dari process environment**.
- `bootstrap/cache/config.php` ada (hasil `php artisan config:cache` sebelumnya) yang membekukan `database.default = 'mysql'` + `database = 'sanitra'`. Begitu config di-cache, Laravel mengabaikan env override dari phpunit.xml.
- Akibatnya, `LazilyRefreshDatabase` menjalankan `migrate:fresh` di MySQL `sanitra` live, bukan di SQLite in-memory. **Seluruh row domain hilang** (schema utuh, upload di `storage/app/*` juga utuh).
- Recovery: hapus `bootstrap/cache/config.php` → `php artisan config:clear` → `php artisan db:seed --class=SanitraMasterSeeder`. Master pulih; transaksi manual (quotation + items + termin yang dibuat lewat UI) tidak bisa dipulihkan.

## 1.1 Akar masalah sebenarnya: config cache, bukan PHPUnit

Penting dipahami supaya mitigasinya tidak salah sasaran: **yang menghancurkan data bukan PHPUnit, melainkan `bootstrap/cache/config.php`.** PHPUnit hanya kendaraannya (`LazilyRefreshDatabase` → `migrate:fresh`).

Buktinya ada di repo ini sendiri: **6 November 2026 akar masalah yang sama kambuh di Playwright/E2E**, bukan di `php artisan test`. Config cache basi bikin request HTTP dari browser menulis ke MySQL `sanitra` asli, dan beberapa record `E2E%` sempat terbentuk di sana sebelum ketahuan (rekap lengkap: `docs/work/2026-11-06.md`, `docs/testing/playwright-strategy.md` §2.1).

Kesimpulan yang harus dipegang:

- **Tidak ada jenis test yang otomatis aman.** Yang membuat aman adalah guard-nya, bukan tool-nya.
- Setiap jalur eksekusi test wajib punya guard sendiri: PHPUnit → `tests/TestCase.php::setUp()` (§4.2); E2E → `tests/e2e/start-server.sh` (§4.3).
- Begitu `bootstrap/cache/config.php` ada, SEMUA lapisan `.env*` gugur sekaligus — untuk PHPUnit maupun E2E.

## 1.2 Kebijakan proyek: testing otomatis = E2E saja

**Keputusan pengguna (8 November 2026): proyek ini tidak memakai unit test maupun feature test. Seluruh testing otomatis dilakukan lewat E2E Playwright.** Alasannya insiden §1 — pengguna tidak mau lagi ada jalur eksekusi test yang bisa menyentuh database aplikasi lewat `migrate:fresh`.

Konsekuensi yang harus diikuti:

- **Jangan menulis atau menambah test di `tests/Feature/` dan `tests/Unit/`.** Jangan pula menawarkannya sebagai solusi, termasuk saat ada aturan bisnis yang lebih "natural" diuji di level service.
- **Jangan menjalankan `php artisan test`** — ini sekarang larangan kebijakan, bukan lagi sekadar "perlu izin eksplisit" seperti §2.
- Guard `tests/TestCase.php::setUp()` (§4.2) **tetap tidak boleh dihapus**. Ia jaring pengaman kalau ada yang menjalankan PHPUnit secara tidak sengaja (IDE, `composer test`, kebiasaan lama).
- Semua coverage aturan bisnis dipindahkan ke `tests/e2e/flows/` — lihat `docs/testing/business-flow-strategy.md`.

**Batas jujur dari kebijakan ini — satu aturan jadi tidak tertutup:**

**BR-02** ("`amount` termin dikunci saat SO dibuat dan tidak berubah walau grand total quotation sumber berubah") **tidak bisa diuji lewat E2E sama sekali.** Quotation berstatus `Disetujui` bersifat read-only di UI, jadi tidak ada cara memicu perubahan grand total dari browser. Ini gap yang diterima secara sadar — **jangan ditambal dengan test yang hanya membandingkan nilai statis lalu diberi nama seolah menguji BR-02.** Catat sebagai tidak tercakup, bukan sebagai lulus.

## 2. Aturan mutlak

- **Jangan menjalankan `php artisan test` kecuali diminta pengguna secara eksplisit.** Ini aturan proyek (lihat `docs/work/2026-10-11.md` poin 5). Jika pengguna minta test, ikuti §3 di bawah.
- Jangan asumsikan `phpunit.xml <env>` block cukup mengisolasi database. Ia hanya aman kalau Laravel membaca env runtime, bukan config cache.
- Jangan jalankan `migrate:fresh`, `migrate:refresh`, `db:wipe`, atau `migrate:rollback` di koneksi default lokal tanpa izin pengguna.

## 3. Preflight checklist sebelum run test

Lakukan **berurutan**, berhenti kalau salah satu gagal:

1. `ls bootstrap/cache/config.php` — kalau ada, hapus/clear dulu:
   ```
   php artisan config:clear
   ```
2. Pastikan koneksi test benar-benar SQLite:
   ```
   php artisan tinker --execute 'echo config("database.default");'
   ```
   Jalankan dengan `APP_ENV=testing` di depannya untuk simulasi:
   ```
   APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: \
     php artisan tinker --execute 'echo config("database.default");'
   ```
   Output harus `sqlite`.
3. Baru jalankan test:
   ```
   php artisan test --filter=NamaTest --compact
   ```

Jika langkah 2 masih memperlihatkan `mysql`, **berhenti**. Jangan jalankan test. Laporkan ke pengguna dulu.

### 3.1 Preflight sebelum run E2E (jalur yang dipakai sekarang)

Sejak §1.2 ini jalur yang berlaku. `start-server.sh` sudah melakukan langkah 1–2 sendiri (§4.3), tapi tetap periksa dulu:

1. `ls bootstrap/cache/config.php` — kalau ada, **laporkan ke pengguna** dan minta izin clear (§6). Script akan menghapusnya otomatis, jadi keberadaannya berarti ada yang menjalankan `config:cache` di luar script — itu informasi yang pengguna perlu tahu.
2. Pastikan 4 guard di `start-server.sh` (§4.3) masih utuh, termasuk `--no-reload`.
3. Pastikan tidak ada proses lain di port 8011 (`lsof -i :8011`). `reuseExistingServer: false` mencegah suite menumpang server asing, tapi port bentrok bikin `webServer` gagal start dengan pesan yang menyesatkan.
4. Baru jalankan `npm run test:e2e`.

Setelah run, jangan percaya "passed" begitu saja kalau itu kali pertama test tersebut lulus — verifikasi `storage/logs/laravel.log` tidak menyebut `Connection: mysql`. Teknik diagnosis lengkap ada di `docs/testing/playwright-strategy.md` §2.1.

## 4. Safeguard yang sudah terpasang

Dua lapisan proteksi aktif di repo:

### 4.1 `.env.testing`

Berisi:

```
APP_ENV=testing
APP_DEBUG=true
APP_KEY=... (sinkron dengan .env)
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
BCRYPT_ROUNDS=4
```

Laravel membaca `.env.testing` saat `APP_ENV=testing` (dipaksa oleh `phpunit.xml`), mendahului `.env`. **Catatan penting:** `.env.testing` **tidak** efektif kalau `bootstrap/cache/config.php` ada — config cache dibuat sekali dan Laravel skip loading `.env*` sepenuhnya saat cache dipakai. Jadi `.env.testing` adalah lapis pertama untuk kondisi normal; lapis kedua di §4.2 yang menangkap kondisi config-cached.

### 4.2 Guard di `tests/TestCase.php::setUp()`

```php
protected function setUp(): void
{
    parent::setUp();

    $connection = config('database.default');

    if ($connection !== 'sqlite') {
        throw new RuntimeException(
            "Testing database must be sqlite in-memory, got connection '{$connection}' ..."
        );
    }
}
```

Guard fail-fast: begitu `setUp()` jalan, Laravel app sudah di-boot dan `config()` sudah mencerminkan state sebenarnya (termasuk kalau config di-cache ke MySQL). Kalau koneksi default **bukan** sqlite, test langsung `RuntimeException` sebelum satu pun query DB dieksekusi. Pesan error ngarahkan user hapus `bootstrap/cache/config.php` + `php artisan config:clear`.

Jangan hapus atau skip guard ini. Kalau ada test yang memang perlu DB non-sqlite (misal integrasi khusus), buat `TestCase` turunan tersendiri dan konfirmasi dulu ke pengguna.

Catatan: sejak §1.2, PHPUnit tidak dipakai lagi di proyek ini. Guard ini **tetap dipertahankan** sebagai jaring pengaman kalau ada yang menjalankannya tidak sengaja.

### 4.3 Guard di `tests/e2e/start-server.sh` — lapisan untuk E2E

Karena E2E sekarang satu-satunya jalur testing (§1.2), **script inilah satu-satunya hal yang berdiri antara `php artisan migrate` di dalamnya dan MySQL aplikasi.** Empat baris berikut adalah guard, bukan setup biasa — jangan diubah atau dihapus tanpa izin pengguna:

1. `rm -f bootstrap/cache/config.php` — mematikan akar masalah §1/§1.1 di awal tiap run.
2. `export APP_CONFIG_CACHE="/private/tmp/sanitra-e2e-config-${$}.php"` — config cache per-run, tidak pernah membaca file milik app asli.
3. `export DB_DATABASE="$(mktemp /private/tmp/sanitra-e2e-XXXXXX)"` — database SQLite baru tiap run, di luar project.
4. **Isolation check** (baris `php -r '…'` sebelum `migrate`) — memastikan `app()->environment() === 'testing'`, `config('database.default') === 'sqlite'`, dan path SQLite benar-benar sama dengan `DB_DATABASE`. Gagal → exit 1 **sebelum** satu perintah `migrate` pun jalan.

Ditambah `--no-reload` pada `php artisan serve`, yang bukan soal performa: tanpanya Laravel menghapus semua env var di atas dari proses yang benar-benar menangani HTTP request (lihat §1.1 dan `docs/work/2026-11-06.md` poin 2). **Menghapus `--no-reload` = mengembalikan kondisi insiden 6 November.**

Kalau isolation check gagal, jangan diakali — laporkan ke pengguna sesuai §6.

## 5. Prosedur recovery kalau terjadi lagi

1. **Stop** semua test / command yang masih jalan.
2. Hapus config cache: `php artisan config:clear` lalu `rm -f bootstrap/cache/config.php`.
3. Reseed master data (idempotent):
   ```
   php artisan db:seed --class=SanitraMasterSeeder
   ```
4. Verifikasi: `php artisan tinker --execute 'echo \App\Models\MsStatus::where("active", true)->count();'` harus `31`.
5. Data transaksi manual (quotation/SO/invoice/payment yang dibuat lewat UI) tidak bisa dipulihkan dari seeder. User perlu input ulang. Upload file di `storage/app/public/*` dan `storage/app/private/*` biasanya aman karena di luar database.

## 6. Yang HARUS DILAPORKAN ke pengguna

- Setiap kali hendak menjalankan `php artisan test`, konfirmasi dulu dengan user.
- Setiap kali menemukan `bootstrap/cache/config.php`, informasikan user dan tanyakan apakah boleh di-clear sebelum lanjut.
- Setiap kali `config('database.default')` tidak `sqlite` di preflight, hentikan proses dan tanya user.
- Setiap kali isolation check di `tests/e2e/start-server.sh` gagal (§4.3), atau salah satu guard di script itu ternyata sudah berubah/hilang.
- Setiap kali muncul kebutuhan yang terasa "lebih pas" diuji pakai feature/unit test. Jangan diam-diam membuatnya — §1.2 melarang; sampaikan batasannya ke pengguna dan biarkan pengguna yang memutuskan.

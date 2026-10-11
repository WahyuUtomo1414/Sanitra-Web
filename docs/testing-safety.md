# Testing Safety — Lokal

**Status:** Wajib dibaca sebelum menjalankan test apa pun di mesin lokal.

## 1. Insiden 11 Oktober 2026 — rekap singkat

- `php artisan test --filter=QuotationFlowTest` dieksekusi beberapa kali di sesi debug `MoneyInput`.
- `phpunit.xml` memaksa `DB_CONNECTION=sqlite` + `DB_DATABASE=:memory:` via `<php><env .../></php>`, tetapi override ini **hanya efektif ketika Laravel membaca `env()` dari process environment**.
- `bootstrap/cache/config.php` ada (hasil `php artisan config:cache` sebelumnya) yang membekukan `database.default = 'mysql'` + `database = 'sanitra'`. Begitu config di-cache, Laravel mengabaikan env override dari phpunit.xml.
- Akibatnya, `LazilyRefreshDatabase` menjalankan `migrate:fresh` di MySQL `sanitra` live, bukan di SQLite in-memory. **Seluruh row domain hilang** (schema utuh, upload di `storage/app/*` juga utuh).
- Recovery: hapus `bootstrap/cache/config.php` → `php artisan config:clear` → `php artisan db:seed --class=SanitraMasterSeeder`. Master pulih; transaksi manual (quotation + items + termin yang dibuat lewat UI) tidak bisa dipulihkan.

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

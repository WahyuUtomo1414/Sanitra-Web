# E2E Testing Strategy — Playwright

**Status:** Infra setup selesai (§2.1). Spec untuk seluruh 10 resource master sudah tersedia (§2.2); verifikasi ulang dua skenario relasi khusus dan matriks seluruh role masih diperlukan. Resource transaksi (§2.3) & business flow test (§2.4) belum ditulis.

Dokumen ini adalah index. Detail tiap strategi ada di file terpisah:

- [`resource-testing-strategy.md`](./resource-testing-strategy.md) — strategi testing per halaman resource (master & transaksi).
- [`business-flow-strategy.md`](./business-flow-strategy.md) — strategi testing per alur bisnis (quotation → SO → invoice → payment).

## 0. Environment: Lokal vs Staging

**Keputusan: lokal sebagai target utama.** Project ini jalan di shared hosting (tanpa queue worker, semua proses sync — lihat memory `project_hosting`), dan saat ini belum ada environment staging terpisah. Alasan pakai lokal:

- Iterasi cepat — tidak bergantung network/deploy.
- Kontrol penuh atas state database (seed/reset kapan saja) tanpa resiko mengganggu data orang lain.
- Shared hosting rawan kalau dipakai untuk run test otomatis berulang (resource terbatas, tidak ada isolasi proses).

Staging (kalau nanti ada) dipakai sebagai **smoke test tambahan sebelum deploy**, bukan tempat development test. Konfigurasi saat ini sengaja mengunci suite mutasi data ke server E2E lokal pada `http://127.0.0.1:8011`; smoke test staging perlu konfigurasi terpisah yang hanya membaca data.

**⚠️ Peringatan penting (beda dari PHPUnit):** Playwright menjalankan browser sungguhan terhadap server Laravel. Karena itu `webServer` membuat server `APP_ENV=testing` dan database SQLite sementara sendiri (lihat `docs/testing-safety.md`). Artinya:

- Jangan mengubah `baseURL` ke server aplikasi biasa atau staging untuk suite yang membuat, mengubah, dan menghapus data.
- Insiden MySQL terhapus (`docs/testing-safety.md`) itu soal PHPUnit/`migrate:fresh`; server E2E terpisah mencegah Playwright menyentuh MySQL aplikasi.

## 1. Stack & Flow Testing

- **Library:** `@playwright/test` (TypeScript).
- **Browser:** default pakai Chromium bawaan Playwright (`npx playwright install`). Kalau butuh Chrome asli (bukan Chromium), pakai `channel: 'chrome'` di config + `npx playwright install chrome`.
- **Struktur folder (usulan):**

  ```text
  tests/e2e/
    playwright.config.ts
    fixtures/
      auth.ts          # login helper per role, storageState
    resources/
      ms-item.spec.ts
      trans-sales-quotation-hd.spec.ts
      ...
    flows/
      quotation-to-so.spec.ts
      so-term-to-invoice.spec.ts
      invoice-to-payment.spec.ts
      full-flow.spec.ts
  ```

- **Auth:** Filament panel butuh login per role (`SuperAdmin`/Marketing/Finance/Teknis — enum `App\Enums\RoleName`). Gunakan `storageState` Playwright: login sekali per role lewat setup project, simpan session, dipakai ulang di semua test supaya tidak login berulang-ulang. User test diambil dari `UserSeeder`.
- **Data:** `start-server.sh` menjalankan `SanitraMasterSeeder` (termasuk role dan user) pada SQLite sementara sebelum suite. Test transaksi idealnya self-contained (buat data sendiri di dalam test / `beforeEach`), bukan bergantung urutan test lain.
- **CI:** belum relevan sekarang (belum ada CI pipeline disebut di docs) — jalankan manual dulu lewat `npx playwright test`.

## 2. Roadmap Implementasi

### 2.1 Setup infra — ✅ selesai

- `@playwright/test` ter-install sebagai devDependency, Chromium ter-download (`npx playwright install chromium`).
- `playwright.config.ts` di root project: `testDir: ./tests/e2e`, `baseURL` tetap `http://127.0.0.1:8011`, reporter HTML. `webServer` menjalankan `tests/e2e/start-server.sh` yang membuat database SQLite sementara, memastikan `APP_ENV=testing` dan koneksi SQLite, lalu migrate, seed, dan menjalankan server E2E. `reuseExistingServer: false` mencegah suite menumpang server lain di port itu.
- Project `setup` (`tests/e2e/auth.setup.ts`) login ke `/dashboard/login` untuk 4 role (`super@gmail.com`, `marketing@gmail.com`, `finance@gmail.com`, `teknis@gmail.com` — password `12345678`, sesuai `database/seeders/UserSeeder.php`), simpan `storageState` ke `playwright/.auth/<role>.json`.
- 4 project test terpisah (`chromium-super-admin`, `chromium-marketing`, `chromium-finance`, `chromium-teknis`), masing-masing `dependencies: ['setup']` dan pakai `storageState` role terkait — jadi test apapun yang ditulis di `tests/e2e/resources/` atau `tests/e2e/flows/` otomatis jalan 4x (satu per role) kecuali di-filter.
- Kredensial role: `tests/e2e/fixtures/roles.ts` (single source of truth, dipakai `auth.setup.ts`).
- Smoke test `tests/e2e/smoke.spec.ts` — verifikasi semua role berhasil login & reach `/dashboard` tanpa ke-redirect balik ke login. **8/8 test passed** saat verifikasi (4 auth setup + 4 smoke run per role).
- `package.json` scripts: `npm run test:e2e`, `test:e2e:ui` (mode UI interaktif), `test:e2e:report` (buka HTML report terakhir).
- `.gitignore` ditambah `/playwright/.auth/`, `/playwright-report/`, `/test-results/`, `/blob-report/`.
- `typescript` + `@types/node` (devDependency) dan `tsconfig.json` ditambahkan supaya editor bisa type-check file `.ts` di `tests/e2e/` dan `playwright.config.ts` — tanpa ini editor menampilkan error merah palsu (bukan error Playwright beneran) karena tidak ada TypeScript compiler di project.

**Gotcha ditemukan saat verifikasi:**

- Label form Filament login ini **Bahasa Indonesia** ("Alamat email", "Kata sandi", tombol "Masuk"), bukan default Filament Inggris — karena `App\Filament\Pages\Auth\Login` custom (cek `app/Filament/Pages/Auth/Login.php` kalau ada perubahan copy). Locator `getByLabel('Kata sandi')` juga ambigu (strict mode violation) karena tombol "Tampilkan/Sembunyikan kata sandi" match label yang sama — harus pakai `getByRole('textbox', { name: 'Kata sandi' })`.
- **Rate limit login Filament kena saat 4 role login paralel** ("Terlalu banyak permintaan... coba lagi dalam X detik"). `fullyParallel: true` di config bikin `auth.setup.ts` nembak 4 login nyaris bersamaan, trigger rate limiter bawaan Filament. Fix: `setup.describe.configure({ mode: 'serial' })` di `auth.setup.ts` supaya ke-4 login jalan berurutan, bukan paralel.
- **Rate limiter login itu keras: maksimal 5 percobaan per 60 detik per IP, BUKAN per akun** (`vendor/danharrin/livewire-rate-limiting`, dipakai `Filament\Auth\Pages\Login::authenticate()` via `$this->rateLimit(5)`, key = `sha1("App\Filament\Pages\Auth\Login|authenticate|<ip>")`). Satu kali `npm run test:e2e` sudah memakai 4 dari 5 slot (4 login role). **Dua kali run berturut-turut dalam &lt;60 detik akan kena limit di percobaan login ke-5.** Kalau ini terjadi, bukan bug — tunggu ~60 detik, atau clear manual:
  ```bash
  php artisan tinker --execute='
  foreach (["127.0.0.1", "::1"] as $ip) {
      \Illuminate\Support\Facades\RateLimiter::clear("livewire-rate-limiter:" . sha1("App\\Filament\\Pages\\Auth\\Login|authenticate|" . $ip));
  }
  '
  ```
- **`workers: 1` wajib, bahkan untuk run lokal (bukan cuma CI).** Sempat dicoba `workers: 2` — berhasil di sebagian besar run tapi tetap flaky sesekali (notifikasi toast Livewire hilang/duplikat saat 2 browser context menembak server dev lokal yang sama secara bersamaan). Diverifikasi: `workers: 1` konsisten hijau, `workers: 2` kadang gagal di assertion notifikasi meski `expect.timeout` sudah dinaikkan ke 10 detik. Server dev lokal (`php artisan serve`/Herd) tidak didesain untuk concurrent load testing — trade-off kecepatan demi keandalan. Kalau nanti pindah ke CI dengan environment lebih kuat, boleh dicoba naikkan lagi, tapi uji dulu stabilitasnya sebelum percaya hasilnya.

**Prasyarat sebelum run:**

1. Server dan database E2E dibuat otomatis oleh `webServer` di port 8011. Server aplikasi di port 8000 dan database MySQL aplikasi tidak dipakai oleh test.
2. (Sekali saja) `npx playwright install chromium` kalau browser belum ter-download di mesin tersebut.

**Cara run:**

```bash
npm run test:e2e           # headless, semua project (setup + 4 role)
npm run test:e2e:ui        # mode UI interaktif
npm run test:e2e:report    # buka HTML report terakhir
```

Kalau editor menampilkan error merah di file `.ts` padahal test di atas tetap lulus, itu bukan error Playwright — biasanya TypeScript server editor butuh reload (`Developer: Reload Window`) setelah `typescript`/`@types/node` baru ter-install.

### 2.2 Resource test — Master data — 🔄 dimulai

Selesai: `MsUnit` (`tests/e2e/resources/ms-unit.spec.ts`), `MsItemCategory` (`tests/e2e/resources/ms-item-category.spec.ts`). Keduanya: test permission per role + test CRUD lengkap (super admin only, karena `RoleSeeder` belum ngasih akses Create/Update/Delete ke role manapun untuk 2 resource ini). **18 passed, 0 failed** saat verifikasi akhir (sesudah fix rate limit + `workers: 1`).

Spec untuk delapan master sisanya (`MsItem`, `MsClient`, `MsCompany`, `MsEmployee`, `MsBankAccount`, `MsProjectType`, `MsProjectWorkflow`, `MsStatus`) sudah ditulis. CRUD dasar seluruh delapan master telah lolos sebagai super admin pada run terisolasi; dua test relasi khusus Item dan Klien perlu dijalankan ulang setelah penyesuaian locator, dan matriks akses seluruh role perlu run akhir.

### 2.3 Resource test — Transaksi — belum dikerjakan

Lihat `resource-testing-strategy.md` §3.

### 2.4 Business flow test — belum dikerjakan

Lihat `business-flow-strategy.md`.

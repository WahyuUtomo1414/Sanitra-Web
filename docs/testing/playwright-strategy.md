# E2E Testing Strategy — Playwright

**Status:** Infra setup selesai (lihat §2.1) — `@playwright/test` ter-install, auth fixture 4 role jalan, smoke test lulus. Resource & business flow test (§2.2–2.4) belum ditulis.

Dokumen ini adalah index. Detail tiap strategi ada di file terpisah:

- [`resource-testing-strategy.md`](./resource-testing-strategy.md) — strategi testing per halaman resource (master & transaksi).
- [`business-flow-strategy.md`](./business-flow-strategy.md) — strategi testing per alur bisnis (quotation → SO → invoice → payment).

## 0. Environment: Lokal vs Staging

**Keputusan: lokal sebagai target utama.** Project ini jalan di shared hosting (tanpa queue worker, semua proses sync — lihat memory `project_hosting`), dan saat ini belum ada environment staging terpisah. Alasan pakai lokal:

- Iterasi cepat — tidak bergantung network/deploy.
- Kontrol penuh atas state database (seed/reset kapan saja) tanpa resiko mengganggu data orang lain.
- Shared hosting rawan kalau dipakai untuk run test otomatis berulang (resource terbatas, tidak ada isolasi proses).

Staging (kalau nanti ada) dipakai sebagai **smoke test tambahan sebelum deploy**, bukan tempat development test. Supaya suite yang sama bisa dipakai di kedua environment, `baseURL` di-set lewat env var (`PLAYWRIGHT_BASE_URL`), default ke `http://localhost:8000` (sesuai `APP_URL` lokal).

**⚠️ Peringatan penting (beda dari PHPUnit):** Playwright test menjalankan browser sungguhan yang hit aplikasi Laravel yang benar-benar jalan (`php artisan serve` / Herd), bukan test environment ter-isolasi seperti `phpunit.xml` (lihat `docs/testing-safety.md`). Artinya:

- Jangan jalankan Playwright test terhadap database lokal yang berisi data kerja asli — pakai database lokal terpisah khusus e2e, atau rutin reset-seed sebelum run.
- Insiden MySQL terhapus (`docs/testing-safety.md`) itu soal PHPUnit/`migrate:fresh`, tapi prinsip kehati-hatiannya sama: **jangan pernah arahkan Playwright base URL ke environment yang punya data production/real**.

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
- **Data:** jalankan seeder (`SanitraMasterSeeder`, `RoleSeeder`, `UserSeeder`) sebelum suite jalan supaya master data & role permission konsisten. Test transaksi idealnya self-contained (buat data sendiri di dalam test / `beforeEach`), bukan bergantung urutan test lain.
- **CI:** belum relevan sekarang (belum ada CI pipeline disebut di docs) — jalankan manual dulu lewat `npx playwright test`.

## 2. Roadmap Implementasi

### 2.1 Setup infra — ✅ selesai

- `@playwright/test` ter-install sebagai devDependency, Chromium ter-download (`npx playwright install chromium`).
- `playwright.config.ts` di root project: `testDir: ./tests/e2e`, `baseURL` dari env var `PLAYWRIGHT_BASE_URL` (default `http://localhost:8000`), reporter HTML.
- Project `setup` (`tests/e2e/auth.setup.ts`) login ke `/dashboard/login` untuk 4 role (`super@gmail.com`, `marketing@gmail.com`, `finance@gmail.com`, `teknis@gmail.com` — password `12345678`, sesuai `database/seeders/UserSeeder.php`), simpan `storageState` ke `playwright/.auth/<role>.json`.
- 4 project test terpisah (`chromium-super-admin`, `chromium-marketing`, `chromium-finance`, `chromium-teknis`), masing-masing `dependencies: ['setup']` dan pakai `storageState` role terkait — jadi test apapun yang ditulis di `tests/e2e/resources/` atau `tests/e2e/flows/` otomatis jalan 4x (satu per role) kecuali di-filter.
- Kredensial role: `tests/e2e/fixtures/roles.ts` (single source of truth, dipakai `auth.setup.ts`).
- Smoke test `tests/e2e/smoke.spec.ts` — verifikasi semua role berhasil login & reach `/dashboard` tanpa ke-redirect balik ke login. **8/8 test passed** saat verifikasi (4 auth setup + 4 smoke run per role).
- `package.json` scripts: `npm run test:e2e`, `test:e2e:ui` (mode UI interaktif), `test:e2e:report` (buka HTML report terakhir).
- `.gitignore` ditambah `/playwright/.auth/`, `/playwright-report/`, `/test-results/`, `/blob-report/`.
- `typescript` + `@types/node` (devDependency) dan `tsconfig.json` ditambahkan supaya editor bisa type-check file `.ts` di `tests/e2e/` dan `playwright.config.ts` — tanpa ini editor menampilkan error merah palsu (bukan error Playwright beneran) karena tidak ada TypeScript compiler di project.

**Gotcha ditemukan saat verifikasi:** label form Filament login ini **Bahasa Indonesia** ("Alamat email", "Kata sandi", tombol "Masuk"), bukan default Filament Inggris — karena `App\Filament\Pages\Auth\Login` custom (`docs` lain belum mencatat ini, cek `app/Filament/Pages/Auth/Login.php` kalau ada perubahan copy). Juga locator `getByLabel('Kata sandi')` ambigu (strict mode violation) karena tombol "Tampilkan/Sembunyikan kata sandi" juga match label — harus pakai `getByRole('textbox', { name: 'Kata sandi' })`.

**Prasyarat sebelum run:**

1. Aplikasi Laravel harus jalan di lokal (`php artisan serve` atau Herd) — Playwright hit browser beneran ke `baseURL`, bukan test environment terisolasi. Default `http://localhost:8000`.
2. Database lokal sudah di-seed (`SanitraMasterSeeder`, `RoleSeeder`, `UserSeeder`) supaya 4 akun role (§2.1) bisa login dan master data konsisten.
3. (Sekali saja) `npx playwright install chromium` kalau browser belum ter-download di mesin tersebut.

**Cara run:**

```bash
npm run test:e2e           # headless, semua project (setup + 4 role)
npm run test:e2e:ui        # mode UI interaktif
npm run test:e2e:report    # buka HTML report terakhir
PLAYWRIGHT_BASE_URL=https://staging.example.com npm run test:e2e   # arahkan ke staging
```

Kalau editor menampilkan error merah di file `.ts` padahal test di atas tetap lulus, itu bukan error Playwright — biasanya TypeScript server editor butuh reload (`Developer: Reload Window`) setelah `typescript`/`@types/node` baru ter-install.

### 2.2 Resource test — Master data — belum dikerjakan

Lihat `resource-testing-strategy.md` §2.

### 2.3 Resource test — Transaksi — belum dikerjakan

Lihat `resource-testing-strategy.md` §3.

### 2.4 Business flow test — belum dikerjakan

Lihat `business-flow-strategy.md`.

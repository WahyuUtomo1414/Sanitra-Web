# E2E Testing Strategy — Playwright

**Status:** Draft awal, belum diimplementasikan (belum ada `@playwright/test` ter-install di `package.json`).

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

1. **Setup infra** — install `@playwright/test`, config dasar (`playwright.config.ts`), auth fixture per role, baseURL via env var.
2. **Resource test — Master data** — lihat `resource-testing-strategy.md` §2.
3. **Resource test — Transaksi** — lihat `resource-testing-strategy.md` §3.
4. **Business flow test** — lihat `business-flow-strategy.md`.

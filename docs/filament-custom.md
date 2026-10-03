# Filament Custom Panel Sanitra

**Status:** Konsep awal · **Acuan:** [filament-resource.md](filament-resource.md) · **Panel:** Filament 4

## 1. Tujuan

Dokumen ini mencatat kustomisasi tampilan dan branding panel admin Filament untuk aplikasi Sanitra. Fokusnya adalah tema warna, logo, dan override UI yang tidak bisa dilakukan hanya lewat konfigurasi default Filament.

Scope kustomisasi:

- Tema warna (palette, sidebar, topbar, button).
- Logo panel dan favicon.
- Login page (opsional, jika butuh branding khusus).
- Font (opsional).
- Dark mode policy.

## 2. Palette Warna

Palette memakai nuansa hijau tua (forest green) sebagai primary dan sage (muted gray-green) sebagai secondary. Sage dipilih karena cocok dengan warna sub-text "SAPTA UTAMA" di logo brand, sehingga panel admin tetap selaras dengan identitas visual. Accent amber dipertahankan untuk kontras hangat.

Palette ini **berbeda dari warna web publik sanitra.co.id** yang memakai hijau daun cerah; versi panel internal sengaja lebih tua, muted, dan profesional.

### 2.1 Primary (forest green)

| Nama | Hex | Pemakaian |
| --- | --- | --- |
| `--sanitra-primary-950` | `#052e16` | Background sidebar nav dan footer. |
| `--sanitra-primary-900` | `#14532d` | Hover state sidebar group, border card gelap. |
| `--sanitra-primary-800` | `#166534` | Primary button default. |
| `--sanitra-primary-700` | `#15803d` | Primary button hover, link primary. |
| `--sanitra-primary-600` | `#16a34a` | Badge/chip success, icon aktif. |

### 2.2 Sage (secondary — selaras logo)

| Nama | Hex | Pemakaian |
| --- | --- | --- |
| `--sanitra-sage-700` | `#556b47` | Border sage dark, tag outline. |
| `--sanitra-sage-500` | `#87a96b` | Highlight sekunder, badge info sage, chart sekunder. |
| `--sanitra-sage-300` | `#b7c4a4` | Background pill/tag sage ringan. |

### 2.3 Accent (amber hangat)

| Nama | Hex | Pemakaian |
| --- | --- | --- |
| `--sanitra-accent-500` | `#f59e0b` | Indikator active sidebar item (left border), badge warning. |
| `--sanitra-accent-100` | `#fef3c7` | Background pill/notification ringan. |

### 2.4 Netral

Filament sudah menyediakan skala gray bawaan via Tailwind. Tidak perlu override untuk gray/slate.

## 3. Register Palette di PanelProvider

Filament 4 menyediakan API `colors()` untuk mengganti skala warna sistem. Pakai `Color::Green` sebagai basis sehingga seluruh komponen (badge, action button, filter chip) ikut berubah warna. CSS override di §4 hanya untuk branding sidebar/topbar yang di luar jangkauan API warna.

Di `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Filament\Support\Colors\Color;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->colors([
            'primary' => Color::Green,
            'danger' => Color::Rose,
            'warning' => Color::Amber,
            'success' => Color::Green,
            'info' => Color::Lime,
        ])
        ->renderHook(
            \Filament\View\PanelsRenderHook::HEAD_END,
            fn () => view('filament.hooks.admin-theme'),
        );
}
```

Catatan:

- `primary` dan `success` sengaja sama (Green) karena identitas visual Sanitra dominan hijau.
- `info` memakai Lime supaya badge informatif tetap senada palette sage tanpa butuh custom scale.
- `warning` tetap Amber agar status warning (misal invoice jatuh tempo) tetap kontras dan dikenali universal.
- Variable `--sanitra-sage-*` di §2 hanya dipakai lewat CSS override di §4, bukan via API warna Filament.

## 4. CSS Override via Render Hook

Pola override memakai render hook `HEAD_END` dengan file blade yang berisi `<style>` block. Pola ini terbukti bertahan terhadap update Filament selama selector `.fi-*` tidak berubah.

**Pola layout:**

- **Sidebar header** (area brand logo) → background putih, agar logo asli (teks navy + sage) tetap kebaca.
- **Sidebar nav & footer** → background forest green tua.
- **Topbar** → background putih full, border bawah tipis sebagai pemisah dari content.
- **Active sidebar item** → background semi-transparan putih + left border amber.

File: `resources/views/filament/hooks/admin-theme.blade.php`

```blade
<style>
    :root {
        --sanitra-primary-950: #052e16;
        --sanitra-primary-900: #14532d;
        --sanitra-primary-800: #166534;
        --sanitra-primary-700: #15803d;
        --sanitra-primary-600: #16a34a;
        --sanitra-sage-700: #556b47;
        --sanitra-sage-500: #87a96b;
        --sanitra-sage-300: #b7c4a4;
        --sanitra-accent-500: #f59e0b;
        --sanitra-accent-100: #fef3c7;
    }

    html.fi,
    html.fi.dark {
        color-scheme: light;
    }

    /* Topbar — full putih dengan border bawah tipis */
    .fi-topbar {
        background: #ffffff !important;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    }

    .fi-topbar-start,
    .fi-topbar-start a,
    .fi-topbar .fi-icon-btn,
    .fi-topbar .fi-topbar-open-sidebar-btn,
    .fi-topbar .fi-topbar-close-sidebar-btn {
        color: inherit;
    }

    /* Sidebar nav & footer — forest green tua */
    .fi-sidebar,
    .fi-sidebar-nav,
    .fi-sidebar-footer {
        background: var(--sanitra-primary-950) !important;
    }

    /* Sidebar header (brand container) — strip putih agar logo kebaca */
    .fi-sidebar-header,
    .fi-sidebar-header-ctn {
        background: #ffffff !important;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    }

    .fi-sidebar-header-logo-ctn,
    .fi-sidebar-header-logo-ctn a,
    .fi-sidebar-header-logo-ctn .fi-logo,
    .fi-sidebar-header-logo-ctn .fi-logo * {
        color: inherit !important;
    }

    /* Teks dan ikon di nav — putih di atas forest green */
    .fi-sidebar-group-label,
    .fi-sidebar-item-label,
    .fi-sidebar-item-btn,
    .fi-sidebar-group-btn,
    .fi-sidebar-group-collapse-btn,
    .fi-sidebar-group-dropdown-trigger-btn,
    .fi-sidebar-item-icon,
    .fi-sidebar-group svg,
    .fi-sidebar-item svg,
    .fi-sidebar-open-collapse-sidebar-btn,
    .fi-sidebar-close-collapse-sidebar-btn {
        color: #ffffff !important;
        stroke: currentColor;
    }

    /* Label group pakai sage pale biar hierarki kebaca */
    .fi-sidebar-group-label {
        color: rgba(183, 196, 164, 0.78) !important; /* --sanitra-sage-300 muted */
    }

    .fi-sidebar-group-btn,
    .fi-sidebar-item-btn,
    .fi-sidebar-group-dropdown-trigger-btn {
        border-radius: 0.75rem;
    }

    .fi-sidebar-item-btn:hover,
    .fi-sidebar-group-btn:hover,
    .fi-sidebar-group-dropdown-trigger-btn:hover,
    .fi-sidebar-item.fi-active .fi-sidebar-item-btn,
    .fi-sidebar-item.fi-sidebar-item-has-active-child-items .fi-sidebar-item-btn {
        background: rgba(255, 255, 255, 0.12) !important;
    }

    .fi-sidebar-item.fi-active .fi-sidebar-item-btn,
    .fi-sidebar-item.fi-sidebar-item-has-active-child-items .fi-sidebar-item-btn {
        box-shadow: inset 3px 0 0 var(--sanitra-accent-500);
    }

    /* Primary button — forest green */
    .fi-btn.fi-color-primary:not(.fi-outlined),
    .fi-ac .fi-btn.fi-color-primary:not(.fi-outlined),
    .fi-modal .fi-btn.fi-color-primary:not(.fi-outlined) {
        background: var(--sanitra-primary-800) !important;
        border-color: var(--sanitra-primary-800) !important;
        color: #ffffff !important;
    }

    .fi-btn.fi-color-primary:not(.fi-outlined):hover,
    .fi-ac .fi-btn.fi-color-primary:not(.fi-outlined):hover,
    .fi-modal .fi-btn.fi-color-primary:not(.fi-outlined):hover {
        background: var(--sanitra-primary-700) !important;
        border-color: var(--sanitra-primary-700) !important;
        color: #ffffff !important;
    }

    .fi-btn.fi-color-primary.fi-outlined {
        border-color: var(--sanitra-primary-800) !important;
        color: var(--sanitra-primary-800) !important;
    }

    .fi-icon-btn.fi-color-primary,
    .fi-link.fi-color-primary {
        color: var(--sanitra-primary-800) !important;
    }
</style>
```

Catatan:

- Media query mobile tidak diperlukan karena topbar sudah default putih.
- Border tipis `rgba(15, 23, 42, 0.06)` di sidebar header & topbar memberi pemisah halus tanpa harsh line.
- Jika logo lu nanti punya varian putih (invert), bagian `.fi-sidebar-header` bisa di-swap jadi `var(--sanitra-primary-950)` dan logo diganti ke versi putih — tanpa itu, sekarang strip putih dulu.

## 5. Logo dan Favicon

### 5.1 Logo panel

Logo brand aktif saat ini: **`public/assets/logo.png`**.

Karakteristik logo:

- Teks "SANITRA" biru navy, sub-teks "SAPTA UTAMA" sage green.
- Icon kombinasi gradient hijau-biru.
- Background putih/transparan.
- **Hanya ada satu varian**, dioptimasi untuk background terang.

Karena logo berwarna gelap, area brand container di sidebar dan topbar memakai **background putih** (lihat §4). Dengan pola ini logo bisa dipakai apa adanya tanpa butuh varian putih.

Register di `AdminPanelProvider`:

```php
->brandLogo(asset('assets/logo.png'))
->brandLogoHeight('2rem')
```

Catatan:

- `->darkModeBrandLogo()` tidak dipanggil karena dark mode dimatikan (§6).
- Kalau nanti lu bikin varian logo putih (invert), simpan sebagai `public/assets/logo-light.png`, update `.fi-sidebar-header` di §4 agar background jadi `var(--sanitra-primary-950)`, dan ganti `->brandLogo()` ke varian baru.
- Format PNG saat ini cukup untuk resolusi standar; untuk display retina/4K pertimbangkan menambahkan versi SVG di `public/assets/logo.svg` dan ganti referensi `->brandLogo()`.

### 5.2 Favicon

**Status saat ini:**

- `public/assets/favicon.png` ✓ sudah ada — icon gradient hijau-biru (bagian ikon dari logo).
- `public/favicon.ico` ⚠ masih 0 bytes (placeholder bawaan Laravel). Browser auto-request `/favicon.ico` by default tanpa lewat Filament link, jadi file ini **tetap perlu diisi** atau di-delete.

**Yang masih perlu dibereskan:**

1. **Downsize `favicon.png`.** File saat ini **~1 MB** — kegedean untuk favicon (ideal <50 KB). Downsize ke 256×256 atau 512×512 PNG terkompresi. Bisa pakai [Squoosh](https://squoosh.app/) atau `pngquant`.
2. **Isi `public/favicon.ico`.** Generate `.ico` multi-size (16/32/48) dari `favicon.png` via [RealFaviconGenerator](https://realfavicongenerator.net/) atau tool serupa. Taruh hasilnya di `public/favicon.ico`. Alternatif: delete file 0 bytes itu dan terima 404 di root favicon request (acceptable tapi muncul di dev tools).

Register di `AdminPanelProvider`:

```php
->favicon(asset('assets/favicon.png'))
```

Filament akan inject `<link rel="icon" href="...">` yang override default `/favicon.ico` request **di dalam panel admin**. Halaman non-panel (login screen pertama kali, halaman error) tetap hit `/favicon.ico` langsung.

### 5.3 Nama panel (title tag)

```php
->brandName('Sanitra Admin')
```

## 6. Dark Mode Policy

**Panel force light mode. Dark mode tidak dipakai.** Keputusan final — jangan buat palette dark terpisah, jangan ekspos toggle di user menu, jangan tambahkan styling `.dark:*` di komponen.

Alasan: aplikasi internal, dark mode nambah beban desain (palette dua set, test kontras dua kali) tanpa benefit untuk pengguna.

Implementasi:

- `html.fi.dark { color-scheme: light; }` di CSS override (§4) mencegah class dark Filament mengubah skema warna sistem bila ada komponen yang lolos.
- Di `AdminPanelProvider`, non-aktifkan toggle:

```php
->darkMode(false)
```

## 7. Font

Default Filament memakai Inter. Boleh dibiarkan. Jika brand guideline Sanitra memakai font lain (misal Poppins atau Plus Jakarta Sans), register via `->font('Poppins')` dan tambah `@import` di render hook.

Rekomendasi tahap awal: pakai Inter default agar bundling CSS tetap ringan.

## 8. Login Page

Login page memakai layout bawaan Filament. Karena background default login putih, logo `public/assets/logo.png` otomatis kebaca tanpa override tambahan.

Opsional untuk sentuhan brand:

- Background halaman: gradient sage sangat terang (`#f7faf3` → `#eef3e6`) via CSS override tambahan di render hook. Tidak wajib — polos putih sudah OK.

Jika butuh ilustrasi, tagline, atau split layout (form kiri, visual kanan), buat custom login page extends `Filament\Pages\Auth\Login`. Tahap awal: skip, pakai bawaan.

## 9. Struktur File

```
resources/
└── views/
    └── filament/
        └── hooks/
            └── admin-theme.blade.php      # CSS override (§4)

public/
├── favicon.ico                             # ⚠ 0 bytes, perlu diisi atau di-delete (§5.2)
└── assets/
    ├── logo.png                            # ✓ logo brand aktif (§5.1)
    └── favicon.png                         # ✓ ada; perlu downsize dari ~1 MB (§5.2)
```

## 10. Checklist Setup

Urutan setup kustomisasi:

1. **Bereskan favicon.** `public/assets/favicon.png` sudah ada tapi perlu di-downsize (<50 KB) dan `public/favicon.ico` perlu diisi atau di-delete — detail di §5.2.
2. Buat file `resources/views/filament/hooks/admin-theme.blade.php` dengan isi §4.
3. Update `AdminPanelProvider`:
   - `->colors([...])` sesuai §3.
   - `->renderHook(HEAD_END, ...)` ke blade hook.
   - `->brandLogo(asset('assets/logo.png'))`, `->brandLogoHeight('2rem')`.
   - `->favicon(asset('assets/favicon.png'))`.
   - `->brandName('Sanitra Admin')`.
   - `->darkMode(false)`.
4. Jalankan `php artisan filament:assets` setelah perubahan asset Filament.
5. Buka panel di browser, cek:
   - Sidebar body forest green, sidebar header strip putih dengan logo tampil utuh.
   - Topbar full putih dengan border bawah tipis.
   - Active sidebar item punya background semi-transparan + left border amber.
   - Button primary forest green, hover ke lebih terang.
   - Label group sidebar warna sage pale (bukan putih penuh) agar hierarki kebaca.
   - Tab browser menampilkan favicon icon hijau-biru.
6. Cek di mobile (<1024px): sidebar jadi drawer, logo tetap di strip putih saat drawer dibuka.

## 11. Catatan Pengembangan

- **Selector `.fi-*` bisa berubah antar versi mayor Filament.** Setelah upgrade mayor, verifikasi ulang semua selector di §4.
- **Jangan hardcode warna di komponen resource.** Semua warna harus merujuk ke variable CSS atau Filament color system; override lokal mempersulit tema global.
- **Jangan pakai `!important` di luar file hook.** `!important` di komponen resource atau blade view bakal konflik dengan hook dan sulit debug.
- **Logo wajib dua varian (light/dark).** Logo single-tone sering gagal kontras di salah satu background.
- **Palette hex di §2 bersifat normatif.** Jangan mengubah hex tanpa update dokumen ini; perubahan palette = perubahan branding = butuh persetujuan.

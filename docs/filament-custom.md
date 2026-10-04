# Filament Custom Panel Sanitra

**Status:** Implemented · **Acuan:** [filament-resource.md](filament-resource.md) · **Panel:** Filament 5.9.0

## 1. Tujuan

Dokumen ini mencatat kustomisasi tampilan dan branding panel admin Filament untuk aplikasi Sanitra. Fokusnya adalah tema warna, logo, dan override UI yang tidak bisa dilakukan hanya lewat konfigurasi default Filament.

Scope kustomisasi:

- Tema warna (palette, sidebar, topbar, button).
- Logo pada halaman login, nama aplikasi pada panel, dan favicon.
- Login page dengan layout bawaan Filament.
- Font (opsional).
- Dark mode policy.

## 2. Palette Warna

Palette memakai nuansa putih bersih (clean white) sebagai background sidebar dan topbar, selaras dengan karakter perusahaan pengolahan air & lingkungan hidup. Aksen aktif menggunakan hijau segar (`#16a34a`) berpadu dengan aksen garis amber (`#f59e0b`), serta hover hijau mint lembut (`#f0fdf4`).

### 2.1 Primary & Hover (Fresh Green)

| Nama | Hex | Pemakaian |
| --- | --- | --- |
| `--sanitra-primary-700` | `#15803d` | Hover button & hover text/icon sidebar. |
| `--sanitra-primary-600` | `#16a34a` | Background active menu item & primary button. |
| `--sanitra-primary-50` | `#f0fdf4` | Background hover item sidebar (mint lembut). |

### 2.2 Accent (Amber)

| Nama | Hex | Pemakaian |
| --- | --- | --- |
| `--sanitra-accent-500` | `#f59e0b` | Indikator strip sisi kiri active menu item (left border), badge warning. |

### 2.3 Netral & Border

| Nama | Hex | Pemakaian |
| --- | --- | --- |
| `--sanitra-bg` | `#ffffff` | Background sidebar dan topbar (full white). |
| `--sanitra-border` | `#e2e8f0` | Border kanan sidebar dan border bawah topbar. |
| `--sanitra-slate-700` | `#334155` | Teks menu inaktif (default). |
| `--sanitra-slate-600` | `#475569` | Ikon menu inaktif & kontrol topbar. |
| `--sanitra-slate-500` | `#64748b` | Group label sidebar (uppercase). |

## 3. Register Palette di PanelProvider

Di `app/Providers/Filament/DashboardPanelProvider.php`:

```php
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('dashboard')
        ->path('dashboard')
        ->login()
        ->brandName(config('app.name'))
        ->brandLogo(asset('assets/logo.png'))
        ->brandLogoHeight('3rem')
        ->favicon(asset('assets/favicon1.png'))
        ->darkMode(false)
        ->colors([
            'primary' => Color::Green,
            'danger' => Color::Rose,
            'warning' => Color::Amber,
            'success' => Color::Green,
            'info' => Color::Lime,
        ])
        ->renderHook(
            PanelsRenderHook::HEAD_END,
            fn () => view('filament.hooks.admin-theme'),
        );
}
```

## 4. CSS Override via Render Hook

**Pola layout:**

- **Topbar desktop** → background putih bersih dengan border bawah `#e2e8f0` dan vertical border sejajar sidebar. Logo Sanitra berwarna penuh tampil di kiri atas.
- **Sidebar nav & footer** → background putih bersih dengan border kanan halus `#e2e8f0`.
- **Group label** → teks slate-500 uppercase semi-bold.
- **Menu inaktif** → teks slate-700 dan ikon slate-600 yang tajam dan nyaman dibaca.
- **Menu hover** → background mint lembut `#f0fdf4` dengan teks dan ikon hijau `#15803d`.
- **Menu active (Opsi A)** → background hijau segar `#16a34a`, teks & ikon putih, dengan strip aksen amber `#f59e0b` di sisi kiri.
- **Primary button** → background hijau segar `#16a34a` senada active menu.

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

    .fi-body {
        --topbar-height: 5rem;
    }

    /* Topbar putih dengan bidang brand selebar sidebar */
    .fi-topbar {
        background: #ffffff !important;
        border-bottom: 0;
        box-shadow: none !important;
        position: relative;
    }

    @media (min-width: 64rem) {
        .fi-topbar {
            background-image: linear-gradient(
                to right,
                var(--sanitra-primary-950) 0,
                var(--sanitra-primary-950) var(--sidebar-width),
                #ffffff var(--sidebar-width),
                #ffffff 100%
            ) !important;
        }

        .fi-body-has-topbar .fi-sidebar-header-ctn {
            display: none;
        }
    }

    .fi-topbar-start > a {
        position: absolute;
        inset-inline-start: calc(var(--sidebar-width) / 2);
        top: 50%;
        transform: translate(-50%, -50%);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
        width: var(--sidebar-width);
    }

    .fi-topbar-start .fi-logo,
    .fi-sidebar-header-logo-ctn .fi-logo {
        height: auto !important;
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .fi-topbar-start .fi-logo {
        color: #ffffff;
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

    /* Header sidebar hanya tampil saat sidebar menjadi drawer di layar kecil */
    .fi-sidebar-header,
    .fi-sidebar-header-ctn {
        background: #ffffff !important;
    }

    .fi-body-has-topbar .fi-sidebar-header {
        height: 5rem;
        padding: 0.5rem;
    }

    .fi-sidebar-header-logo-ctn {
        display: flex !important;
        justify-content: center !important;
        width: 100% !important;
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
        color: rgba(183, 196, 164, 0.78) !important;
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

- Bidang hijau pada topbar hanya aktif mulai lebar `64rem` dan mengikuti `--sidebar-width`; topbar mobile tetap putih.
- Header sidebar desktop disembunyikan karena nama aplikasi sudah tampil di kiri topbar. Ini juga menghilangkan strip putih di atas navigasi.
- CSS tidak mengubah warna gambar logo login. Teks nama aplikasi di topbar desktop berwarna putih supaya kontras dengan bidang hijau.

## 5. Logo dan Favicon

### 5.1 Logo login

Logo yang dipakai pada halaman login: **`public/assets/logo.png`**.

Karakteristik logo:

- Teks "SANITRA" biru navy, sub-teks "SAPTA UTAMA" sage green.
- Icon kombinasi gradient hijau-biru.
- Background transparan; halaman login bawaan Filament berwarna putih.

Logo `logo.png` tampil di halaman login maupun topbar dashboard berlatar putih dengan skala proporsional.

Register di `DashboardPanelProvider`:

```php
->brandLogo(asset('assets/logo.png'))
->brandLogoHeight('3rem')
```

Catatan:

- `->darkModeBrandLogo()` tidak dipanggil karena dark mode dimatikan (§6).
- CSS hook memastikan logo berukuran `3.15rem` di topbar dan `2.75rem` di halaman login dengan jarak bawah yang lega.

### 5.2 Favicon

**Status saat ini:**

- `public/assets/favicon1.png` ✓ sudah ada dan sudah dipakai.
- `public/favicon.ico` ⚠ masih 0 bytes (placeholder bawaan Laravel). Browser auto-request `/favicon.ico` by default tanpa lewat Filament link — perlu diisi atau di-delete.

Register di `DashboardPanelProvider`:

```php
->favicon(asset('assets/favicon1.png'))
```

### 5.3 Nama aplikasi pada panel dan title tag

```php
->brandName(config('app.name'))
```

`config('app.name')` membaca `APP_NAME` dari `.env`; nilai saat ini adalah `Sanitra`. Teks ini tampil di bidang kiri topbar desktop dan header sidebar saat drawer mobile dibuka.

## 6. Dark Mode Policy

**Panel force light mode. Dark mode tidak dipakai.** Keputusan final — jangan buat palette dark terpisah, jangan ekspos toggle di user menu, jangan tambahkan styling `.dark:*` di komponen.

Alasan: aplikasi internal, dark mode nambah beban desain (palette dua set, test kontras dua kali) tanpa benefit untuk pengguna.

Implementasi:

- `html.fi.dark { color-scheme: light; }` di CSS override (§4) mencegah class dark Filament mengubah skema warna sistem bila ada komponen yang lolos.
- Di `DashboardPanelProvider`, non-aktifkan toggle:

```php
->darkMode(false)
```

## 7. Font

Default Filament memakai Inter. Boleh dibiarkan. Jika brand guideline Sanitra memakai font lain (misal Poppins atau Plus Jakarta Sans), register via `->font('Poppins')` dan tambah `@import` di render hook.

Rekomendasi tahap awal: pakai Inter default agar bundling CSS tetap ringan.

## 8. Login Page

Login page memakai layout bawaan Filament. Callback `brandLogo()` pada §5.1 hanya mengembalikan `public/assets/logo.png` pada route login, sehingga logo berwarna tampil di atas background putih. Halaman panel lainnya memakai teks nama aplikasi.

Opsional untuk sentuhan brand:

- Background halaman: gradient sage sangat terang (`#f7faf3` → `#eef3e6`) via CSS override tambahan di render hook. Tidak wajib — polos putih sudah OK.

Jika butuh ilustrasi, tagline, atau split layout (form kiri, visual kanan), buat custom login page extends `Filament\Pages\Auth\Login`. Tahap awal: skip, pakai bawaan.

## 9. Avatar Pengguna

Model `User` implements `HasAvatar` dari Filament agar avatar yang diupload tampil di topbar dan AccountWidget bawaan.

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar ? Storage::url($this->avatar) : null;
    }
}
```

Catatan:
- Avatar disimpan via `FileUpload` di `UserResource` ke direktori `users/avatar` (disk `public`).
- Jika `avatar` null, Filament fallback ke avatar initials bawaan (UI Avatars).

## 10. Struktur File

```
resources/
└── views/
    └── filament/
        └── hooks/
            └── admin-theme.blade.php      # CSS override (§4) — sudah dibuat

public/
├── favicon.ico                             # ⚠ 0 bytes, perlu diisi atau di-delete
└── assets/
    ├── logo.png                            # logo halaman login (§5.1)
    ├── logo1.png                           # aset alternatif, tidak dipakai
    ├── favicon1.png                        # ✓ favicon aktif (§5.2)
    └── favicon.png                         # file lama, tidak dipakai
```

## 11. Checklist Setup

Status kustomisasi saat ini:

- [x] `resources/views/filament/hooks/admin-theme.blade.php` dibuat.
- [x] `DashboardPanelProvider` dikonfigurasi: colors, renderHook, brandLogo, favicon, darkMode(false).
- [x] Logo `logo.png` tampil hanya di login dengan height `3.5rem`.
- [x] Halaman panel menampilkan teks dari `APP_NAME` pada bidang kiri topbar desktop; bagian kanan topbar tetap putih.
- [x] Header sidebar desktop disembunyikan agar tidak menyisakan garis putih di atas navigasi.
- [x] Favicon `favicon1.png` aktif.
- [x] `User` model implements `HasAvatar` — avatar tampil di topbar.
- [ ] `public/favicon.ico` masih 0 bytes — perlu diisi atau di-delete.

## 12. Catatan Pengembangan

- **Selector `.fi-*` bisa berubah antar versi mayor Filament.** Setelah upgrade mayor, verifikasi ulang semua selector di §4.
- **Jangan hardcode warna di komponen resource.** Semua warna harus merujuk ke variable CSS atau Filament color system; override lokal mempersulit tema global.
- **Jangan pakai `!important` di luar file hook.** `!important` di komponen resource atau blade view bakal konflik dengan hook dan sulit debug.
- Jika dark mode diaktifkan nanti, siapkan varian logo dengan kontras yang sesuai untuk halaman login.
- **Palette hex di §2 bersifat normatif.** Jangan mengubah hex tanpa update dokumen ini; perubahan palette = perubahan branding = butuh persetujuan.

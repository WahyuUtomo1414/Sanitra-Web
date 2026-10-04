@props([
    'livewire' => null,
])

<x-filament-panels::layout.base :livewire="$livewire">
    <style>
        /* Container Auth Split Layout */
        .fi-auth-split-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
            background-color: #ffffff;
            font-family: inherit;
        }

        /* Kolom Kiri: Visual IPAL (Desktop Only) */
        .fi-auth-visual-col {
            position: relative;
            display: none;
            width: 50%;
            min-height: 100vh;
            flex-direction: column;
            justify-content: flex-end;
            padding: 4.5rem;
            overflow: hidden;
            background-color: #0f172a;
            color: #ffffff;
            box-sizing: border-box;
            user-select: none;
        }

        @media (min-width: 1024px) {
            .fi-auth-visual-col {
                display: flex;
            }
        }

        /* Foto Fasilitas IPAL */
        .fi-auth-bg-cover {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            z-index: 1;
        }

        /* Gradient Overlay Halus */
        .fi-auth-visual-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(2, 6, 23, 0.85) 0%, rgba(2, 6, 23, 0.35) 45%, rgba(2, 6, 23, 0.05) 100%);
            z-index: 2;
        }

        /* Konten Minimalis */
        .fi-auth-hero-block {
            position: relative;
            z-index: 10;
            max-width: 32rem;
        }

        .fi-auth-headline {
            font-size: 2.25rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.25;
            letter-spacing: -0.025em;
            margin: 0;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        /* Kolom Kanan: Form Login */
        .fi-auth-form-col {
            display: flex;
            width: 100%;
            min-height: 100vh;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
            background-color: #ffffff;
            box-sizing: border-box;
            overflow-y: auto;
        }

        @media (min-width: 1024px) {
            .fi-auth-form-col {
                width: 50%;
                padding: 3rem 4.5rem;
            }
        }

        .fi-auth-form-container {
            width: 100%;
            max-width: 26rem;
            margin: auto 0;
        }

        .fi-auth-brand-logo-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .fi-auth-brand-logo-wrap img {
            height: 3rem;
            max-height: 3.25rem;
            width: auto;
            object-fit: contain;
        }

        /* Reset Filament Simple Card inside Form Column */
        .fi-auth-form-col .fi-simple-main-ctn,
        .fi-auth-form-col .fi-simple-main,
        .fi-auth-form-col .fi-simple-page {
            width: 100% !important;
            max-width: 100% !important;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .fi-auth-form-col .fi-simple-header {
            margin-bottom: 1.75rem !important;
            text-align: center !important;
        }

        .fi-auth-form-col .fi-simple-header-heading {
            font-size: 1.65rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            letter-spacing: -0.025em !important;
            margin-top: 0 !important;
            margin-bottom: 0.5rem !important;
        }

        .fi-auth-form-col .fi-simple-header-subheading {
            font-size: 0.875rem !important;
            color: #64748b !important;
            line-height: 1.5 !important;
        }

        /* Form Controls */
        .fi-auth-form-col input[type="email"],
        .fi-auth-form-col input[type="password"] {
            border-radius: 0.625rem !important;
            padding: 0.75rem 1rem !important;
            border-color: #cbd5e1 !important;
            font-size: 0.95rem !important;
        }

        .fi-auth-form-col input[type="email"]:focus,
        .fi-auth-form-col input[type="password"]:focus {
            border-color: #16a34a !important;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.18) !important;
        }

        /* Submit Button */
        .fi-auth-form-col button[type="submit"] {
            border-radius: 0.625rem !important;
            padding: 0.85rem 1.5rem !important;
            font-size: 0.975rem !important;
            font-weight: 700 !important;
            width: 100% !important;
            justify-content: center !important;
            margin-top: 1.25rem !important;
            background-color: #16a34a !important;
            border-color: #16a34a !important;
            color: #ffffff !important;
            box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.25) !important;
            transition: all 0.2s ease !important;
        }

        .fi-auth-form-col button[type="submit"]:hover {
            background-color: #15803d !important;
            border-color: #15803d !important;
            box-shadow: 0 6px 12px -2px rgba(22, 163, 74, 0.35) !important;
            transform: translateY(-1px) !important;
        }

        .fi-auth-helper-note {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
            line-height: 1.5;
        }
    </style>

    <div class="fi-auth-split-wrapper">
        <!-- Kolom Kiri: Visual IPAL (Desktop Only) -->
        <div class="fi-auth-visual-col">
            <!-- Background Image IPAL -->
            <img src="{{ asset('assets/auth-bg.jpg') }}" alt="Fasilitas IPAL PT Sanitra Sapta Utama"
                class="fi-auth-bg-cover" />

            <!-- Dark Gradient Overlay -->
            <div class="fi-auth-visual-overlay"></div>

            {{-- <!-- Konten Minimalis -->
            <div class="fi-auth-hero-block">
                <h1 class="fi-auth-headline">
                    Sistem Informasi Manajemen Proyek & Operasional
                </h1>
            </div> --}}
        </div>

        <!-- Kolom Kanan: Form Login (Full height & Centered) -->
        <div class="fi-auth-form-col">
            <div class="fi-auth-form-container">
                <!-- Brand Logo Sanitra -->
                <div class="fi-auth-brand-logo-wrap">
                    <img src="{{ asset('assets/logo.png') }}" alt="PT Sanitra Sapta Utama" />
                </div>

                <!-- Form Login Filament -->
                <div class="w-full">
                    {{ $slot }}
                </div>

                <!-- Footer Kanan -->
                <div class="fi-auth-helper-note">
                    Sistem Internal PT Sanitra Sapta Utama &bull; Mengalami kendala? Hubungi tim IT/Admin.
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::layout.base>

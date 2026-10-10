@php
    $user = filament()->auth()->user();
@endphp

<x-filament-widgets::widget>
    <div style="position: relative; overflow: hidden; border-radius: 0.75rem; min-height: 230px;">
        <img src="{{ asset('assets/auth-bg.jpg') }}" alt=""
            style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center; display: block;">

        <div style="position: absolute; inset: 0; background: linear-gradient(to right, rgba(20,83,45,0.97) 0%, rgba(22,101,52,0.92) 40%, rgba(21,128,61,0.6) 72%, rgba(0,0,0,0.06) 100%);"></div>

        <div style="position: relative; z-index: 1; padding: 1.75rem 2.25rem 1.6rem;">
            <p style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.13em; color: rgba(255,255,255,0.58); margin: 0 0 0.55rem;">
                PT Sanitra Sapta Utama &nbsp;·&nbsp; {{ now()->translatedFormat('d F Y') }}
            </p>
            <h2 style="margin: 0 0 0.45rem; font-size: 2rem; font-weight: 800; color: #fff; line-height: 1.15;">
                Selamat Datang, {{ $user?->name }}
            </h2>
            <p style="margin: 0 0 1.5rem; font-size: 0.92rem; color: rgba(255,255,255,0.7); max-width: 500px; line-height: 1.55;">
                Sistem Informasi Manajemen Jasa Lingkungan dan IPAL.
            </p>

            <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
                @foreach(['Profesional & Berkualitas', 'Fokus Pelanggan', 'Etika & Kepedulian', 'Keselamatan Kerja'] as $nilai)
                    <span style="display: inline-block; padding: 0.38rem 1.1rem; border-radius: 9999px; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.22); font-size: 0.82rem; color: rgba(255,255,255,0.88); white-space: nowrap;">
                        {{ $nilai }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>

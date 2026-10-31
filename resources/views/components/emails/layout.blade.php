<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{{ $subject ?? 'Notifikasi Sanitra' }}</title>
</head>
<body style="margin:0;padding:0;background:#f0f4ff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:14px;color:#374151;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4ff;padding:32px 16px;">
<tr><td align="center">

<table width="600" cellpadding="0" cellspacing="0"
       style="background:#ffffff;border-radius:10px;overflow:hidden;max-width:600px;
              box-shadow:0 2px 8px rgba(30,58,138,0.10);">

  {{-- ══ HEADER ══ --}}
  <tr>
    <td style="background:#1e3a8a;padding:20px 28px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          {{-- Logo (base64 embedded) --}}
          @if(!empty($logoData))
          <td style="vertical-align:middle;width:48px;padding-right:12px;">
            <img src="{{ $logoData }}" alt="{{ $company->legal_name }}"
                 width="40" height="40"
                 style="width:40px;height:40px;object-fit:contain;display:block;border-radius:4px;">
          </td>
          @endif
          {{-- Nama perusahaan --}}
          <td style="vertical-align:middle;">
            <div style="color:#ffffff;font-size:15px;font-weight:700;letter-spacing:0.2px;line-height:1.2;">
              {{ $company->legal_name }}
            </div>
            <div style="color:#93c5fd;font-size:10px;font-weight:600;letter-spacing:1.5px;
                        text-transform:uppercase;margin-top:3px;">
              Sistem Notifikasi
            </div>
          </td>
          {{-- Badge label dokumen --}}
          @if(!empty($docLabel))
          <td style="vertical-align:middle;text-align:right;padding-left:12px;">
            <div style="display:inline-block;background:rgba(255,255,255,0.15);
                        border:1px solid rgba(255,255,255,0.3);border-radius:6px;
                        padding:6px 12px;text-align:center;min-width:80px;">
              <div style="color:#ffffff;font-size:10px;font-weight:700;
                          letter-spacing:0.6px;text-transform:uppercase;line-height:1.5;">
                {!! $docLabel !!}
              </div>
            </div>
          </td>
          @endif
        </tr>
      </table>
    </td>
  </tr>

  {{-- ══ BODY ══ --}}
  <tr>
    <td style="padding:28px 28px 20px;">
      {{ $slot }}
    </td>
  </tr>

  {{-- ══ FOOTER ══ --}}
  <tr>
    <td style="background:#f8fafc;padding:16px 28px;border-top:1px solid #e2e8f0;text-align:center;">
      <div style="color:#6b7280;font-size:12px;font-weight:600;">
        {{ $company->legal_name }}
      </div>
      <div style="color:#9ca3af;font-size:11px;margin-top:6px;line-height:1.6;">
        Email ini dikirim otomatis oleh sistem. Mohon tidak membalas email ini.
      </div>
      <div style="color:#d1d5db;font-size:10px;margin-top:6px;">
        &copy; {{ date('Y') }} Sanitra. All rights reserved.
      </div>
    </td>
  </tr>

</table>

</td></tr>
</table>

</body>
</html>

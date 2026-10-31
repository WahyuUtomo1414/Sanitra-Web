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
    <td style="background:#1e3a8a;padding:0;">

      {{-- Strip aksen biru di atas --}}
      <div style="background:#3b82f6;height:3px;"></div>

      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          {{-- Kiri: logo (jika ada) + nama perusahaan --}}
          <td style="padding:18px 28px;vertical-align:middle;">
            <table cellpadding="0" cellspacing="0">
              <tr>
                @if(!empty($logoData))
                <td style="vertical-align:middle;padding-right:12px;">
                  <img src="{{ $logoData }}" alt="{{ $company->legal_name }}"
                       width="48" height="48"
                       style="width:48px;height:48px;object-fit:contain;display:block;
                              background:#ffffff;border-radius:6px;padding:4px;">
                </td>
                @endif
                <td style="vertical-align:middle;">
                  <div style="color:#ffffff;font-size:16px;font-weight:700;
                              letter-spacing:0.2px;line-height:1.2;">
                    {{ $company->legal_name }}
                  </div>
                  <div style="color:#93c5fd;font-size:10px;font-weight:600;
                              letter-spacing:2px;text-transform:uppercase;margin-top:3px;">
                    Sistem Notifikasi
                  </div>
                </td>
              </tr>
            </table>
          </td>

          {{-- Kanan: badge label dokumen --}}
          @if(!empty($docLabel))
          <td style="padding:18px 28px;vertical-align:middle;text-align:right;width:1%;white-space:nowrap;">
            <div style="display:inline-block;background:#ffffff;border-radius:6px;
                        padding:8px 14px;text-align:center;">
              <div style="color:#1e3a8a;font-size:10px;font-weight:800;
                          letter-spacing:0.8px;text-transform:uppercase;line-height:1.5;">
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
    <td style="background:#f8fafc;padding:20px 28px;border-top:1px solid #e2e8f0;text-align:center;">
      <div style="color:#374151;font-size:13px;font-weight:700;margin-bottom:8px;">
        {{ $company->legal_name }}
      </div>
      @if($company->phone || $company->email)
      <div style="color:#6b7280;font-size:11px;line-height:1.8;">
        @if($company->phone)
          &#128222;&nbsp;{{ $company->phone }}
        @endif
        @if($company->phone && $company->email)
          &nbsp;&nbsp;|&nbsp;&nbsp;
        @endif
        @if($company->email)
          &#9993;&nbsp;{{ $company->email }}
        @endif
      </div>
      @endif
      <div style="color:#9ca3af;font-size:11px;margin-top:10px;
                  border-top:1px solid #e5e7eb;padding-top:10px;line-height:1.6;">
        Email ini dikirim otomatis oleh sistem. Mohon tidak membalas email ini.<br>
        <span style="color:#d1d5db;font-size:10px;">&copy; {{ date('Y') }} Sanitra. All rights reserved.</span>
      </div>
    </td>
  </tr>

</table>

</td></tr>
</table>

</body>
</html>

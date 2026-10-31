<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{{ $subject ?? 'Notifikasi Sanitra' }}</title>
</head>
<body style="margin:0;padding:0;background:#f0f4ff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:14px;color:#374151;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4ff;padding:32px 0;">
<tr><td align="center">

<table width="600" cellpadding="0" cellspacing="0"
       style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);max-width:600px;">

  {{-- HEADER --}}
  <tr>
    <td style="padding:24px 32px 0;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          @if($company->logo)
          <td style="vertical-align:middle;width:90px;padding-right:14px;">
            <img src="{{ Storage::url($company->logo) }}"
                 alt="{{ $company->legal_name }}"
                 style="max-width:80px;max-height:60px;display:block;">
          </td>
          @endif
          <td style="vertical-align:middle;">
            <div style="color:#1e3a8a;font-size:15px;font-weight:700;line-height:1.2;">
              {{ $company->legal_name }}
            </div>
            <div style="color:#6b7280;font-size:11px;margin-top:4px;line-height:1.6;">
              {{ $company->address }}
              @if($company->postal_code) {{ $company->postal_code }} @endif
              <br>
              Telp: {{ $company->phone }}
              @if($company->website)
                &nbsp;·&nbsp; {{ $company->website }}
              @endif
            </div>
          </td>
          @if(!empty($docLabel))
          <td style="vertical-align:middle;text-align:right;padding-left:14px;">
            <div style="color:#6b7280;font-size:9px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;">
              Sistem Notifikasi
            </div>
            <div style="color:#1e3a8a;font-size:12px;font-weight:700;margin-top:3px;line-height:1.35;text-align:right;">
              {!! $docLabel !!}
            </div>
          </td>
          @endif
        </tr>
      </table>
      <div style="border-top:2.5px solid #1e3a8a;margin-top:16px;"></div>
    </td>
  </tr>

  {{-- BODY --}}
  <tr>
    <td style="padding:28px 32px;">
      {{ $slot }}
    </td>
  </tr>

  {{-- FOOTER --}}
  <tr>
    <td style="padding:0 32px 28px;">
      <div style="border-top:2px solid #1e3a8a;padding-top:16px;text-align:center;">
        <div style="color:#374151;font-size:12px;font-weight:600;">
          {{ $company->legal_name }}
        </div>
        <div style="color:#9ca3af;font-size:11px;margin-top:4px;line-height:1.7;">
          {{ $company->address }}
          @if($company->postal_code) {{ $company->postal_code }} @endif
          <br>
          @if($company->email) {{ $company->email }} @endif
          @if($company->email && $company->website) &nbsp;·&nbsp; @endif
          @if($company->website) {{ $company->website }} @endif
        </div>
        <div style="color:#9ca3af;font-size:11px;margin-top:10px;border-top:1px solid #e5e7eb;padding-top:10px;">
          Email ini dikirim otomatis oleh sistem. Mohon tidak membalas email ini.<br>
          &copy; {{ date('Y') }} Sanitra. All rights reserved.
        </div>
      </div>
    </td>
  </tr>

</table>

</td></tr>
</table>

</body>
</html>

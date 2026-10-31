<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{{ $subject ?? 'Notifikasi Sanitra' }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f6fb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:14px;color:#374151;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f6fb;padding:32px 16px;">
<tr><td align="center">

<table width="600" cellpadding="0" cellspacing="0"
       style="background:#ffffff;border-radius:10px;overflow:hidden;max-width:600px;
              box-shadow:0 4px 20px rgba(24,48,86,0.08);">

  {{-- ══ HEADER ══ --}}
  <tr>
    <td height="4" style="background:#1e3a8a;font-size:0;line-height:0;">&nbsp;</td>
  </tr>
  <tr>
    <td align="center" style="padding:24px 30px 22px;border-bottom:1px solid #e2e8f0;text-align:center;">
      <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
          <td align="center" style="text-align:center;">
            @if(!empty($logoData))
              <img src="{{ $logoData }}" alt="{{ $company->legal_name }}"
                   width="68" height="68" style="width:68px;height:68px;object-fit:contain;display:block;margin:0 auto;">
            @endif
            <div style="color:#1e3a8a;font-size:17px;font-weight:700;line-height:1.3;{{ !empty($logoData) ? 'margin-top:10px;' : '' }}">
              {{ $company->legal_name }}
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  @if(!empty($docLabel))
  <tr>
    <td style="background:#f4f7fc;padding:22px 30px;border-bottom:1px solid #e2e8f0;">
      <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
          <td width="4" style="background:#1e3a8a;font-size:0;line-height:0;">&nbsp;</td>
          <td style="padding-left:18px;">
            <div style="color:#1e3a8a;font-size:22px;font-weight:700;line-height:1.3;">
              {{ $docLabel }}
            </div>
            @if(!empty($docDescription))
            <div style="color:#64748b;font-size:12px;line-height:1.5;margin-top:5px;">
              {{ $docDescription }}
            </div>
            @endif
          </td>
        </tr>
      </table>
    </td>
  </tr>
  @endif

  {{-- ══ BODY ══ --}}
  <tr>
    <td style="padding:30px 30px 26px;">
      {{ $slot }}
    </td>
  </tr>

  {{-- ══ FOOTER ══ --}}
  <tr>
    <td style="background:#f8fafc;padding:24px 30px 20px;border-top:1px solid #e2e8f0;text-align:center;">
      <div style="color:#1e3a8a;font-size:13px;font-weight:700;line-height:1.4;">
        {{ $company->legal_name }}
      </div>
      @if($company->address)
      <div style="color:#64748b;font-size:11px;line-height:1.6;margin-top:5px;">
        {{ $company->address }}
      </div>
      @endif
      @if($company->phone || $company->email || $company->website)
      <div style="color:#475569;font-size:11px;line-height:1.8;margin-top:10px;">
        @if($company->phone)
          {{ $company->phone }}
        @endif
        @if($company->phone && $company->email)
          &nbsp;&nbsp;·&nbsp;&nbsp;
        @endif
        @if($company->email)
          <a href="mailto:{{ $company->email }}" style="color:#1e3a8a;text-decoration:none;">{{ $company->email }}</a>
        @endif
        @if(($company->phone || $company->email) && $company->website)
          &nbsp;&nbsp;·&nbsp;&nbsp;
        @endif
        @if($company->website)
          <a href="{{ $company->website }}" style="color:#1e3a8a;text-decoration:none;">{{ $company->website }}</a>
        @endif
      </div>
      @endif
      <div style="color:#94a3b8;font-size:10px;margin-top:18px;
                  border-top:1px solid #e2e8f0;padding-top:14px;line-height:1.6;">
        Email ini dikirim otomatis oleh sistem. Mohon tidak membalas email ini.<br>
        &copy; {{ date('Y') }} Sanitra. All rights reserved.
      </div>
    </td>
  </tr>

</table>

</td></tr>
</table>

</body>
</html>

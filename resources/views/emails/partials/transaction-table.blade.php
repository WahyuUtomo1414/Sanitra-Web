@props(['rows'])

<table width="100%" cellpadding="0" cellspacing="0"
       style="border-collapse:collapse;margin:20px 0;font-size:13px;">
  @foreach($rows as [$label, $value])
  <tr>
    <td style="padding:9px 14px;background:#f0f4ff;border:1px solid #bfdbfe;
                width:38%;color:#6b7280;font-weight:600;vertical-align:top;">
      {{ $label }}
    </td>
    <td style="padding:9px 14px;border:1px solid #bfdbfe;
                color:#111827;font-weight:700;vertical-align:top;">
      {{ $value ?? '—' }}
    </td>
  </tr>
  @endforeach
</table>

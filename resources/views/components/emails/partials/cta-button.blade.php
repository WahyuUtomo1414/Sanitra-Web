@props(['url', 'label' => 'Lihat Detail'])

<table cellpadding="0" cellspacing="0" style="margin:24px 0 4px;">
  <tr>
    <td style="border-radius:6px;background:#1e3a8a;">
      <a href="{{ $url }}"
         style="display:inline-block;padding:11px 28px;color:#ffffff;font-size:14px;
                font-weight:600;text-decoration:none;border-radius:6px;letter-spacing:0.2px;">
        {{ $label }}
      </a>
    </td>
  </tr>
</table>

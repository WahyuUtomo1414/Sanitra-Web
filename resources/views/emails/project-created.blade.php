<x-emails.layout :company="$company" docLabel="PROJECT<br>BARU">

  <p style="color:#374151;margin:0 0 4px;font-size:14px;line-height:1.6;">
    Project berikut telah dibuat dan siap dikerjakan. Harap cek detail dan mulai persiapan teknis.
  </p>

  <x-emails.partials.transaction-table :rows="[
    ['Kode Project',    $project->code],
    ['Nama Project',    $project->project_name],
    ['Nomor SO',        $order?->code],
    ['Klien',           $order?->client?->name],
    ['Tanggal Dibuat',  $project->created_at?->translatedFormat('d M Y')],
    ['PIC',             $project->picEmployee?->name],
  ]"/>

  <x-emails.partials.cta-button :url="$actionUrl" label="Lihat Sales Order"/>

</x-emails.layout>

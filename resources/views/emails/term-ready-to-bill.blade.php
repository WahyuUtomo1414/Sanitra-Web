<x-emails.layout :company="$company" :logoData="$logoData ?? null" docLabel="TERMIN SIAP<br>DITAGIH">

  <p style="color:#374151;margin:0 0 4px;font-size:14px;line-height:1.6;">
    Termin berikut sudah siap untuk dibuatkan invoice. Harap segera proses di sistem.
  </p>

  <x-emails.partials.transaction-table :rows="[
    ['Nomor SO',        $order?->code],
    ['Klien',           $order?->client?->name],
    ['Nama Termin',     $term->term_name],
    ['Persentase',      $term->percentage . '%'],
    ['Nilai Termin',    'Rp ' . number_format((float) $term->amount, 0, ',', '.')],
    ['Tanggal',         $term->updated_at?->translatedFormat('d M Y')],
  ]"/>

  <x-emails.partials.cta-button :url="$actionUrl" label="Buat Invoice"/>

</x-emails.layout>

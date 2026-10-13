<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>SPH {{ $quotation->code }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm 16mm 18mm 16mm; }
        * { margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1a1a1a; line-height: 1.55; }

        /* ── Tabel header biru ── */
        .th-blue {
            background-color: #2a4f80;
            color: #ffffff;
            padding: 5px 6px;
            border: 1px solid #c8d4e0;
            font-weight: bold;
            text-align: left;
        }
        .th-center { text-align: center; }
        .th-right  { text-align: right; }

        /* ── Tabel item & termin ── */
        .data-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        .data-table td { padding: 5px 6px; border: 1px solid #c8d4e0; vertical-align: top; }
        .td-center { text-align: center; }
        .td-right  { text-align: right; }
        .row-even  { background-color: #f7f9fb; }
        .item-note { font-size: 7.5pt; color: #6b7a8d; }

        /* ── Section header ── */
        .sec-hdr {
            background-color: #1a3557;
            color: #ffffff;
            font-size: 9pt;
            font-weight: bold;
            padding: 5px 8px;
            margin-top: 0;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>

{{-- ── KOP SURAT ── --}}
<table style="width:100%; border-collapse:collapse; border-bottom:3px solid #1a3557; padding-bottom:10px; margin-bottom:14px;">
    <tr>
        <td style="width:130px; vertical-align:middle; padding-bottom:10px;">
            @if ($logoData)
                <img src="{{ $logoData }}" style="max-width:120px; max-height:70px;">
            @endif
        </td>
        <td style="text-align:right; vertical-align:middle; padding-bottom:10px;">
            <div style="font-size:14pt; font-weight:bold; color:#1a3557;">{{ $quotation->company->name }}</div>
            @if ($quotation->company->legal_name && $quotation->company->legal_name !== $quotation->company->name)
                <div style="font-size:8.5pt; color:#6b7a8d;">{{ $quotation->company->legal_name }}</div>
            @endif
            <div style="font-size:8.5pt; color:#444; line-height:1.6;">
                {{ $quotation->company->address }}@if($quotation->company->postal_code), {{ $quotation->company->postal_code }}@endif<br>
                Telp: {{ $quotation->company->phone }} &nbsp;&middot;&nbsp; {{ $quotation->company->email }}
            </div>
        </td>
    </tr>
</table>

{{-- ── JUDUL ── --}}
<div style="text-align:center; margin:14px 0 12px;">
    <div style="font-size:13pt; font-weight:bold; color:#1a3557; letter-spacing:1.5px;">SURAT PENAWARAN HARGA</div>
    <div style="font-size:9pt; color:#6b7a8d; margin-top:2px;">{{ $quotation->code }}</div>
</div>

{{-- ── META & KEPADA ── --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
    <tr>
        <td style="width:48%; vertical-align:top; padding-right:10px;">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="width:120px; color:#6b7a8d; padding:2px 0; font-size:9pt;">Nomor</td>
                    <td style="width:8px; color:#6b7a8d; font-size:9pt;">:</td>
                    <td style="font-size:9pt;">{{ $quotation->code }}</td>
                </tr>
                <tr>
                    <td style="color:#6b7a8d; padding:2px 0; font-size:9pt;">Tanggal</td>
                    <td style="color:#6b7a8d; font-size:9pt;">:</td>
                    <td style="font-size:9pt;">{{ $quotation->transaction_date->locale('id')->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <td style="color:#6b7a8d; padding:2px 0; font-size:9pt;">Jenis Pekerjaan</td>
                    <td style="color:#6b7a8d; font-size:9pt;">:</td>
                    <td style="font-size:9pt;">{{ $quotation->projectType->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="color:#6b7a8d; padding:2px 0; font-size:9pt;">Skema Bayar</td>
                    <td style="color:#6b7a8d; font-size:9pt;">:</td>
                    <td style="font-size:9pt;">{{ $quotation->payment_type }}</td>
                </tr>
            </table>
        </td>
        <td style="width:52%; vertical-align:top;">
            <div style="border-left:3px solid #1a3557; padding:7px 10px; background-color:#f7f9fb;">
                <div style="font-size:8.5pt; color:#6b7a8d; margin-bottom:3px;">Kepada Yth.</div>
                <div style="font-weight:bold; font-size:10.5pt; color:#1a3557;">{{ $quotation->client->name }}</div>
                @if ($quotation->client->address)
                    <div style="font-size:9pt;">{{ $quotation->client->address }}</div>
                @endif
                @if ($quotation->client->picEmployee)
                    <div style="font-size:8.5pt; color:#555; margin-top:3px;">U/p: {{ $quotation->client->picEmployee->name }}</div>
                @endif
            </div>
        </td>
    </tr>
</table>

{{-- ── HAL ── --}}
<div style="margin-bottom:10px; font-size:9.5pt;">
    <strong>Hal:</strong> Penawaran Harga Jasa {{ $quotation->projectType->name ?? 'Pekerjaan' }} untuk {{ $quotation->client->name }}
</div>

{{-- ── PEMBUKA ── --}}
<div style="margin-bottom:14px; font-size:9.5pt; text-align:justify;">
    Dengan hormat,<br><br>
    Sehubungan dengan kebutuhan Bapak/Ibu, bersama surat ini kami dari
    <strong>{{ $quotation->company->legal_name ?? $quotation->company->name }}</strong>
    dengan hormat menyampaikan penawaran harga atas jasa yang tercantum di bawah ini.
    Kami berkomitmen untuk memberikan solusi yang tepat, berkualitas, dan sesuai dengan
    regulasi lingkungan yang berlaku di Indonesia.
</div>

{{-- ── RINCIAN PENAWARAN ── --}}
<div class="sec-hdr" style="margin-top:0;">RINCIAN PENAWARAN</div>
<table class="data-table">
    <thead>
        <tr>
            <th class="th-blue th-center" style="width:22px;">No.</th>
            <th class="th-blue">Uraian Pekerjaan / Jasa</th>
            <th class="th-blue th-center" style="width:28px;">Qty</th>
            <th class="th-blue" style="width:52px;">Satuan</th>
            <th class="th-blue th-right" style="width:100px;">Harga Satuan</th>
            <th class="th-blue th-right" style="width:85px;">Diskon</th>
            <th class="th-blue th-right" style="width:100px;">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($quotation->items as $item)
            <tr @if($loop->even) class="row-even" @endif>
                <td class="td-center">{{ $loop->iteration }}</td>
                <td>
                    {{ $item->item->name }}
                    @if ($item->notes)
                        <br><span class="item-note">{{ $item->notes }}</span>
                    @endif
                </td>
                <td class="td-center">{{ $item->qty }}</td>
                <td>{{ $item->unit->name }}</td>
                <td class="td-right">{{ $rupiah($item->unit_price) }}</td>
                <td class="td-right">
                    @if ((float) $item->discount > 0)
                        {{ $rupiah($item->discount) }}
                    @else
                        <span style="color:#bbb;">—</span>
                    @endif
                </td>
                <td class="td-right">{{ $rupiah($item->total_price) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- ── RINGKASAN BIAYA ── --}}
<table style="width:100%; border-collapse:collapse; font-size:9pt; margin-top:6px;">
    @if ((float) $quotation->discount_total > 0)
        <tr>
            <td style="width:52%;"></td>
            <td style="color:#6b7a8d; padding:2px 5px;">Subtotal sebelum diskon</td>
            <td style="text-align:right; padding:2px 5px; min-width:110px;">{{ $rupiah((float) $quotation->subtotal + (float) $quotation->discount_total) }}</td>
        </tr>
        <tr>
            <td></td>
            <td style="color:#6b7a8d; padding:2px 5px;">Total diskon</td>
            <td style="text-align:right; padding:2px 5px; color:#c0392b;">({{ $rupiah($quotation->discount_total) }})</td>
        </tr>
    @endif
    <tr>
        <td style="width:52%;"></td>
        <td style="color:#6b7a8d; padding:2px 5px;">Subtotal</td>
        <td style="text-align:right; padding:2px 5px;">{{ $rupiah($quotation->subtotal) }}</td>
    </tr>
    @if ($quotation->vat_type->value !== 'none')
        <tr>
            <td></td>
            <td style="color:#6b7a8d; padding:2px 5px;">
                PPN
                @if ($quotation->vat_type->value === 'include')
                    <span style="font-size:7.5pt;">(sudah termasuk)</span>
                @else
                    <span style="font-size:7.5pt;">(ditambahkan)</span>
                @endif
            </td>
            <td style="text-align:right; padding:2px 5px;">{{ $rupiah($quotation->vat_amount) }}</td>
        </tr>
    @endif
    <tr>
        <td></td>
        <td style="font-weight:bold; font-size:10pt; color:#1a3557; border-top:2px solid #1a3557; padding:5px 5px;">GRAND TOTAL</td>
        <td style="text-align:right; font-weight:bold; font-size:10pt; color:#1a3557; border-top:2px solid #1a3557; padding:5px 5px;">{{ $rupiah($quotation->grand_total) }}</td>
    </tr>
</table>
@if ($quotation->vat_type->value === 'include')
    <div style="font-size:7.5pt; color:#6b7a8d; text-align:right; margin-top:3px;">* PPN 11% sudah termasuk dalam harga di atas.</div>
@endif

{{-- ── SKEMA TERMIN ── --}}
@if ($terms->count() > 0)
    <div class="sec-hdr" style="margin-top:14px;">SKEMA PEMBAYARAN (TERMIN)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="th-blue th-center" style="width:22px;">No.</th>
                <th class="th-blue">Nama Termin</th>
                <th class="th-blue th-center" style="width:68px;">Persentase</th>
                <th class="th-blue th-center" style="width:105px;">Syarat Progress</th>
                <th class="th-blue th-right" style="width:110px;">Nilai Termin</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($terms as $term)
                <tr @if($loop->even) class="row-even" @endif>
                    <td class="td-center">{{ $loop->iteration }}</td>
                    <td>{{ $term->term_name }}</td>
                    <td class="td-center">{{ $term->percentage }}%</td>
                    <td class="td-center">
                        @if ((int) $term->progress_threshold === 0)
                            Down Payment
                        @else
                            &ge; {{ $term->progress_threshold }}% progress
                        @endif
                    </td>
                    <td class="td-right">{{ $rupiah($termAmounts[$term->id]) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- ── CATATAN KHUSUS ── --}}
@if ($quotation->notes)
    <div class="sec-hdr" style="margin-top:14px;">CATATAN KHUSUS</div>
    <div style="border:1px solid #c8d4e0; padding:7px 10px; font-size:9pt; background-color:#fafbfc;">{{ $quotation->notes }}</div>
@endif

{{-- ── SYARAT & KETENTUAN ── --}}
<div class="sec-hdr" style="margin-top:14px;">SYARAT DAN KETENTUAN</div>
<div style="font-size:8.5pt; color:#333; margin-top:5px;">
    <ol style="padding-left:16px;">
        <li style="margin-bottom:3px; line-height:1.5;">Penawaran ini berlaku selama <strong>30 (tiga puluh) hari kalender</strong> sejak tanggal surat diterbitkan.</li>
        <li style="margin-bottom:3px; line-height:1.5;">Harga yang tercantum belum termasuk biaya akomodasi dan transportasi di luar wilayah Jabodetabek, kecuali disebutkan secara khusus dalam rincian penawaran.</li>
        <li style="margin-bottom:3px; line-height:1.5;">Pelaksanaan pekerjaan dimulai setelah pembayaran Down Payment (DP) diterima dan Purchase Order (PO) atau Surat Perintah Kerja (SPK) diterbitkan oleh pemberi kerja.</li>
        <li style="margin-bottom:3px; line-height:1.5;">Seluruh dokumen perizinan, data teknis, dan akses lokasi yang diperlukan untuk pelaksanaan pekerjaan menjadi tanggung jawab pemberi kerja.</li>
        <li style="margin-bottom:3px; line-height:1.5;">Perubahan lingkup pekerjaan (change order) yang disepakati kedua belah pihak dapat memengaruhi nilai kontrak dan jadwal pelaksanaan.</li>
        <li style="margin-bottom:3px; line-height:1.5;">Keterlambatan pembayaran termin dapat mengakibatkan penghentian sementara pekerjaan sampai kewajiban pembayaran dipenuhi.</li>
        <li style="margin-bottom:3px; line-height:1.5;">Garansi pekerjaan berlaku sesuai kesepakatan dalam kontrak dan tidak mencakup kerusakan akibat pihak lain atau force majeure.</li>
    </ol>
</div>

{{-- ── PENUTUP ── --}}
<div style="margin-top:12px; font-size:9.5pt; text-align:justify;">
    Demikian penawaran harga ini kami sampaikan. Besar harapan kami untuk dapat menjalin
    kerja sama yang baik dengan <strong>{{ $quotation->client->name }}</strong>.
    Apabila terdapat pertanyaan lebih lanjut, kami siap dihubungi melalui kontak yang tertera di atas.
</div>

{{-- ── TANDA TANGAN ── --}}
<table style="width:100%; border-collapse:collapse; margin-top:24px;">
    <tr>
        <td style="width:50%; vertical-align:top; padding-right:20px;">
            <div style="font-size:8.5pt; color:#6b7a8d; margin-bottom:2px;">Menyetujui,</div>
            <div style="font-weight:bold; color:#1a3557; font-size:10pt; margin-bottom:55px;">{{ $quotation->client->name }}</div>
            <div style="border-top:1px solid #555; padding-top:4px; font-size:8.5pt;">
                (______________________________)<br>
                <span style="font-size:8pt; color:#6b7a8d;">Nama &amp; Jabatan</span>
            </div>
        </td>
        <td style="width:50%; vertical-align:top; text-align:right;">
            <div style="font-size:8.5pt; color:#6b7a8d; margin-bottom:2px;">Hormat kami,</div>
            <div style="font-weight:bold; color:#1a3557; font-size:10pt; margin-bottom:55px;">{{ $quotation->company->legal_name ?? $quotation->company->name }}</div>
            <div style="border-top:1px solid #555; padding-top:4px; font-size:8.5pt;">
                (______________________________)<br>
                <span style="font-size:8pt; color:#6b7a8d;">Penanggung Jawab</span>
            </div>
        </td>
    </tr>
</table>

</body>
</html>

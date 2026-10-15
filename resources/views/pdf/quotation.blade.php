<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Penawaran Harga {{ $quotation->code }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 17mm 17mm 18mm;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: #243247;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5pt;
            line-height: 1.45;
        }

        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }

        .muted { color: #66758a; }
        .brand { color: #166534; }
        .small { font-size: 7.5pt; }
        .strong { font-weight: bold; }
        .right { text-align: right; }
        .center { text-align: center; }
        .nowrap { white-space: nowrap; }

        .letterhead {
            border-bottom: 2px solid #166534;
        }
        .letterhead td { padding-bottom: 12px; }
        .letterhead-logo {
            width: 178px;
            padding-right: 18px;
            vertical-align: middle;
        }
        .letterhead-logo img {
            max-width: 160px;
            max-height: 90px;
        }
        .company-name {
            color: #173c2b;
            font-size: 13pt;
            font-weight: bold;
            line-height: 1.2;
        }
        .company-detail {
            color: #66758a;
            font-size: 7.5pt;
            line-height: 1.4;
            margin-top: 5px;
        }
        .company-contact {
            color: #365747;
            font-size: 7.5pt;
            margin-top: 5px;
        }
        .company-contact-label {
            color: #66758a;
            font-weight: bold;
            margin-right: 3px;
        }
        .company-contact-separator {
            color: #b2c4b7;
            padding: 0 9px;
        }

        .document-header {
            margin-top: 17px;
            margin-bottom: 17px;
        }
        .document-kicker {
            color: #166534;
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .document-title {
            color: #152d22;
            font-size: 16pt;
            font-weight: bold;
            line-height: 1.2;
            margin-top: 4px;
        }
        .document-code {
            color: #243247;
            font-size: 9pt;
            font-weight: bold;
        }

        .information {
            border-top: 1px solid #d9e3dd;
            border-bottom: 1px solid #d9e3dd;
            margin-bottom: 16px;
        }
        .information > tbody > tr > td {
            width: 50%;
            padding: 10px 10px 10px 0;
        }
        .information > tbody > tr > td + td {
            border-left: 1px solid #d9e3dd;
            padding-left: 14px;
            padding-right: 0;
        }
        .eyebrow {
            color: #607267;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .client-name {
            color: #173c2b;
            font-size: 10pt;
            font-weight: bold;
        }
        .meta td { padding: 1px 0; }
        .meta-label { width: 82px; color: #66758a; }

        .subject {
            color: #173c2b;
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 7px;
        }
        .intro { margin-bottom: 18px; }

        .section-title {
            color: #173c2b;
            font-size: 8.5pt;
            font-weight: bold;
            letter-spacing: .7px;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd9cf;
            padding-bottom: 5px;
            margin: 17px 0 7px;
            page-break-after: avoid;
        }
        .section-title:first-child { margin-top: 0; }
        .keep-together { page-break-inside: avoid; }

        .line-table {
            table-layout: fixed;
            font-size: 7.6pt;
        }
        .line-table thead { display: table-header-group; }
        .line-table tr { page-break-inside: avoid; }
        .line-table th {
            background: #eaf2ec;
            border-top: 1px solid #9bb9a4;
            border-bottom: 1px solid #9bb9a4;
            color: #244b33;
            font-size: 7pt;
            font-weight: bold;
            padding: 6px 5px;
        }
        .line-table td {
            border-bottom: 1px solid #e3eae5;
            padding: 6px 5px;
            word-wrap: break-word;
        }
        .line-table tbody tr:nth-child(even) { background: #fafcfb; }
        .item-note {
            color: #66758a;
            display: block;
            font-size: 7pt;
            margin-top: 2px;
        }

        .summary {
            font-size: 8pt;
            margin-top: 8px;
            page-break-inside: avoid;
        }
        .summary td { padding: 3px 6px; }
        .summary .summary-heading td {
            border-bottom: 1px solid #cbd9cf;
            color: #173c2b;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: .6px;
            padding-bottom: 5px;
            text-transform: uppercase;
        }
        .summary .spacer { width: 50%; }
        .summary .amount { width: 27%; text-align: right; white-space: nowrap; }
        .summary .grand td {
            background: #eaf2ec;
            border-top: 1px solid #9bb9a4;
            color: #173c2b;
            font-size: 9.5pt;
            font-weight: bold;
            padding-top: 7px;
            padding-bottom: 7px;
        }
        .tax-note {
            color: #66758a;
            font-size: 7pt;
            margin-top: 4px;
            text-align: right;
        }

        .notes {
            background: #f7f9f8;
            border-left: 2px solid #5a9a6c;
            padding: 7px 9px;
            white-space: pre-line;
        }
        .terms {
            color: #3b4656;
            font-size: 7.5pt;
            line-height: 1.4;
            padding-left: 17px;
        }
        .terms li {
            padding-left: 2px;
            margin-bottom: 4px;
            page-break-inside: avoid;
        }
        .closing { margin-top: 12px; }

        .signatures {
            margin-top: 23px;
            page-break-inside: avoid;
        }
        .signatures td { width: 50%; padding-right: 20px; }
        .signatures td + td { padding-left: 20px; padding-right: 0; }
        .signature-party {
            color: #173c2b;
            font-weight: bold;
            margin-top: 3px;
        }
        .signature-space { height: 48px; }
        .signature-line {
            border-top: 1px solid #89988e;
            padding-top: 4px;
            color: #66758a;
            font-size: 7.5pt;
        }
        .document-footer {
            border-top: 1px solid #d9e3dd;
            color: #849187;
            font-size: 7pt;
            margin-top: 23px;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    @php($hasItemDiscount = $quotation->items->contains(fn ($item): bool => (float) $item->discount > 0))

    <table class="letterhead">
        <tr>
            @if ($logoData)
                <td class="letterhead-logo">
                    <img src="{{ $logoData }}" alt="Logo {{ $quotation->company->name }}">
                </td>
            @endif
            <td>
                <div class="company-name">{{ $quotation->company->legal_name }}</div>
                @if ($quotation->company->address)
                    <div class="company-detail">{{ $quotation->company->address }}@if ($quotation->company->postal_code), {{ $quotation->company->postal_code }}@endif</div>
                @endif
                @if ($quotation->company->phone || $quotation->company->website)
                    <div class="company-contact">
                        @if ($quotation->company->phone)
                            <span class="company-contact-label">Tel.</span>{{ $quotation->company->phone }}
                        @endif
                        @if ($quotation->company->phone && $quotation->company->website)
                            <span class="company-contact-separator">|</span>
                        @endif
                        @if ($quotation->company->website)
                            <span class="company-contact-label">Web.</span>{{ preg_replace('#^https?://#i', '', rtrim($quotation->company->website, '/')) }}
                        @endif
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <table class="document-header">
        <tr>
            <td>
                <div class="document-kicker">Dokumen Penawaran</div>
                <div class="document-title">Surat Penawaran Harga</div>
            </td>
            <td class="right" style="vertical-align: bottom;">
                <div class="eyebrow">Nomor Dokumen</div>
                <div class="document-code">{{ $quotation->code }}</div>
            </td>
        </tr>
    </table>

    <table class="information">
        <tr>
            <td>
                <div class="eyebrow">Ditujukan kepada</div>
                <div class="client-name">{{ $quotation->client->name }}</div>
                @if ($quotation->client->address)
                    <div>{{ $quotation->client->address }}</div>
                @endif
                @if ($quotation->client->picEmployee)
                    <div class="small muted" style="margin-top: 4px;">PIC internal: {{ $quotation->client->picEmployee->name }}</div>
                @endif
            </td>
            <td>
                <div class="eyebrow">Informasi Penawaran</div>
                <table class="meta">
                    <tr>
                        <td class="meta-label">Tanggal</td>
                        <td>{{ $quotation->transaction_date->locale('id')->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Pekerjaan</td>
                        <td>{{ $quotation->projectType->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Pembayaran</td>
                        <td>{{ $quotation->payment_type }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="subject">Perihal: Penawaran {{ $quotation->projectType->name ?? 'Pekerjaan' }}</div>
    <div class="intro">
        Dengan hormat,<br>
        Menindaklanjuti kebutuhan {{ $quotation->client->name }}, kami menyampaikan penawaran pekerjaan
        dengan rincian layanan dan nilai sebagai berikut.
    </div>

    <div class="section-title">Rincian Pekerjaan dan Biaya</div>
    <table class="line-table">
        <colgroup>
            <col style="width: 5%;">
            @if ($hasItemDiscount)
                <col style="width: 36%;">
                <col style="width: 7%;">
                <col style="width: 9%;">
                <col style="width: 15%;">
                <col style="width: 13%;">
                <col style="width: 15%;">
            @else
                <col style="width: 45%;">
                <col style="width: 7%;">
                <col style="width: 9%;">
                <col style="width: 17%;">
                <col style="width: 17%;">
            @endif
        </colgroup>
        <thead>
            <tr>
                <th class="center" style="width: 5%;">No.</th>
                <th style="width: {{ $hasItemDiscount ? '36%' : '45%' }};">Uraian Pekerjaan</th>
                <th class="center" style="width: 7%;">Qty</th>
                <th style="width: 9%;">Satuan</th>
                <th class="right" style="width: {{ $hasItemDiscount ? '15%' : '17%' }};">Harga Satuan (Rp)</th>
                @if ($hasItemDiscount)
                    <th class="right" style="width: 13%;">Diskon (Rp)</th>
                @endif
                <th class="right" style="width: {{ $hasItemDiscount ? '15%' : '17%' }};">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>
                        <span class="strong">{{ $item->item->name }}</span>
                        @if ($item->notes)
                            <span class="item-note">{{ $item->notes }}</span>
                        @endif
                    </td>
                    <td class="center">{{ $item->qty }}</td>
                    <td>{{ $item->unit->name }}</td>
                    <td class="right nowrap">{{ $angka($item->unit_price) }}</td>
                    @if ($hasItemDiscount)
                        <td class="right nowrap">{{ (float) $item->discount > 0 ? $angka($item->discount) : '-' }}</td>
                    @endif
                    <td class="right nowrap strong">{{ $angka($item->total_price) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr class="summary-heading">
            <td colspan="3">Ringkasan Biaya</td>
        </tr>
        @if ((float) $quotation->discount_total > 0)
            <tr>
                <td class="spacer"></td>
                <td class="muted">Nilai sebelum diskon</td>
                <td class="amount">{{ $rupiah((float) $quotation->subtotal + (float) $quotation->discount_total) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="muted">Diskon</td>
                <td class="amount">({{ $rupiah($quotation->discount_total) }})</td>
            </tr>
        @endif
        <tr>
            <td class="spacer"></td>
            <td class="muted">Subtotal</td>
            <td class="amount">{{ $rupiah($quotation->subtotal) }}</td>
        </tr>
        @if ($quotation->vat_type->value !== 'none')
            <tr>
                <td></td>
                <td class="muted">PPN {{ $quotation->vat_type->value === 'include' ? '(termasuk)' : '(ditambahkan)' }}</td>
                <td class="amount">{{ $rupiah($quotation->vat_amount) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td></td>
            <td>Total Penawaran</td>
            <td class="amount">{{ $rupiah($quotation->grand_total) }}</td>
        </tr>
    </table>
    @if ($quotation->vat_type->value === 'include')
        <div class="tax-note">Nilai PPN sudah termasuk dalam total penawaran.</div>
    @endif

    @if ($terms->count() > 0)
        <div class="section-title">Skema Pembayaran</div>
        <table class="line-table">
            <colgroup>
                <col style="width: 5%;">
                <col style="width: 35%;">
                <col style="width: 13%;">
                <col style="width: 24%;">
                <col style="width: 23%;">
            </colgroup>
            <thead>
                <tr>
                    <th class="center">No.</th>
                    <th>Termin</th>
                    <th class="center">Porsi</th>
                    <th>Syarat Penagihan</th>
                    <th class="right">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($terms as $term)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td class="strong">{{ $term->term_name }}</td>
                        <td class="center">{{ $term->percentage }}%</td>
                        <td>{{ (int) $term->progress_threshold === 0 ? 'Down Payment' : 'Progress minimal '.$term->progress_threshold.'%' }}</td>
                        <td class="right nowrap">{{ $rupiah($termAmounts[$term->id]) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($quotation->notes)
        <div class="keep-together">
            <div class="section-title">Catatan Penawaran</div>
            <div class="notes">{{ $quotation->notes }}</div>
        </div>
    @endif

    <div class="section-title">Syarat dan Ketentuan</div>
    <ol class="terms">
        <li>Penawaran ini berlaku selama <strong>30 (tiga puluh) hari kalender</strong> sejak tanggal surat diterbitkan.</li>
        <li>Harga yang tercantum belum termasuk biaya akomodasi dan transportasi di luar wilayah Jabodetabek, kecuali disebutkan secara khusus dalam rincian penawaran.</li>
        <li>Pelaksanaan pekerjaan dimulai setelah pembayaran Down Payment (DP) diterima dan Purchase Order (PO) atau Surat Perintah Kerja (SPK) diterbitkan oleh pemberi kerja.</li>
        <li>Seluruh dokumen perizinan, data teknis, dan akses lokasi yang diperlukan untuk pelaksanaan pekerjaan menjadi tanggung jawab pemberi kerja.</li>
        <li>Perubahan lingkup pekerjaan (change order) yang disepakati kedua belah pihak dapat memengaruhi nilai kontrak dan jadwal pelaksanaan.</li>
        <li>Keterlambatan pembayaran termin dapat mengakibatkan penghentian sementara pekerjaan sampai kewajiban pembayaran dipenuhi.</li>
        <li>Garansi pekerjaan berlaku sesuai kesepakatan dalam kontrak dan tidak mencakup kerusakan akibat pihak lain atau force majeure.</li>
    </ol>

    <div class="closing">
        Demikian penawaran ini kami sampaikan. Kami berharap dapat bekerja sama dengan
        {{ $quotation->client->name }}. Untuk klarifikasi, silakan menghubungi kontak perusahaan di atas.
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="muted small">Persetujuan pemberi kerja</div>
                <div class="signature-party">{{ $quotation->client->name }}</div>
                <div class="signature-space"></div>
                <div class="signature-line">Nama, jabatan, dan tanda tangan</div>
            </td>
            <td>
                <div class="muted small">Hormat kami</div>
                <div class="signature-party">{{ $quotation->company->legal_name ?: $quotation->company->name }}</div>
                <div class="signature-space"></div>
                <div class="signature-line">Nama, jabatan, dan tanda tangan</div>
            </td>
        </tr>
    </table>

    <div class="document-footer">
        {{ $quotation->company->name }} &nbsp; | &nbsp; Penawaran {{ $quotation->code }}
    </div>
</body>
</html>

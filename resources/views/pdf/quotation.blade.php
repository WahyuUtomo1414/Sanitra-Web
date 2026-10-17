<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Penawaran Harga {{ $quotation->code }}</title>
    <style>
        @page { size: A4 portrait; margin: 15mm 15mm 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .right { text-align: right; }
        .center { text-align: center; }
        .nowrap { white-space: nowrap; }
        .bold { font-weight: bold; }
        .muted { color: #6b7280; }
        .small { font-size: 7.5pt; }

        /* ── HEADER ── */
        .hdr-logo { width: 120px; padding-right: 6px; vertical-align: middle; }
        .hdr-logo img { max-width: 115px; max-height: 85px; }
        .hdr-co { vertical-align: middle; }
        .hdr-co-name { color: #1e3a8a; font-size: 12.5pt; font-weight: bold; line-height: 1.2; }
        .hdr-co-detail { color: #6b7280; font-size: 7pt; margin-top: 4px; line-height: 1.55; }
        .hdr-title { text-align: right; vertical-align: middle; width: 175px; }
        .hdr-doc-kicker { color: #6b7280; font-size: 6.5pt; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; }
        .hdr-doc-title { color: #1e3a8a; font-size: 15pt; font-weight: bold; line-height: 1.15; }
        .hdr-divider { border: 0; border-top: 2.5px solid #1e3a8a; margin: 10px 0 0; }

        /* ── META BAND ── */
        .meta-band { border: 1px solid #bfdbfe; margin: 12px 0; }
        .meta-band td { padding: 9px 12px; border-right: 1px solid #bfdbfe; }
        .meta-band td:last-child { border-right: none; }
        .meta-lbl { color: #6b7280; font-size: 6.5pt; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 3px; }
        .meta-val { color: #111827; font-size: 9pt; font-weight: bold; }
        .meta-hi-cell { background: #eff6ff; }
        .meta-hi-cell .meta-val { color: #1e3a8a; }

        /* ── CLIENT ── */
        .block-lbl { color: #6b7280; font-size: 6.5pt; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 4px; }
        .client-name { color: #111827; font-size: 10.5pt; font-weight: bold; }
        .client-addr { color: #374151; font-size: 7.5pt; margin-top: 3px; line-height: 1.55; }
        .client-pic { color: #374151; font-size: 7.5pt; margin-top: 3px; }

        /* ── OPENING TEXT ── */
        .opening { color: #374151; margin: 12px 0; font-size: 8.5pt; text-align: justify; }

        /* ── SECTION LABEL ── */
        .sec-lbl { color: #1e3a8a; font-size: 7.5pt; font-weight: bold; letter-spacing: 0.8px; text-transform: uppercase; border-bottom: 1.5px solid #bfdbfe; padding-bottom: 4px; margin: 14px 0 8px; page-break-after: avoid; }

        /* ── ITEMS TABLE ── */
        .items-tbl { font-size: 8pt; }
        .items-tbl thead { display: table-header-group; }
        .items-tbl tr { page-break-inside: avoid; }
        .items-tbl th { background: #1e3a8a; color: #ffffff; font-size: 7pt; font-weight: bold; padding: 7px 8px; border: 1px solid #1e3a8a; }
        .items-tbl td { border: 1px solid #e5e7eb; padding: 8px 8px; }
        .items-tbl tbody tr:nth-child(even) td { background: #f0f4ff; }
        .item-name { font-weight: bold; color: #111827; }
        .item-note { color: #6b7280; font-size: 7pt; margin-top: 2px; }

        /* ── SUMMARY ── */
        .summary { font-size: 8.5pt; margin-top: 10px; page-break-inside: avoid; }
        .summary td { padding: 3.5px 8px; }
        .summary .spc { width: 58%; }
        .summary .amt { text-align: right; white-space: nowrap; }
        .summary .sep td { border-top: 1px solid #bfdbfe; padding-top: 6px; }
        .summary .grand td { background: #1e3a8a; color: #ffffff; font-size: 10.5pt; font-weight: bold; padding: 9px 8px; }

        /* ── CONTENT BOXES ── */
        .content-box { border: 1px solid #bfdbfe; margin-top: 12px; page-break-inside: avoid; }
        .box-hdr { background: #1e3a8a; color: #ffffff; font-size: 7.5pt; font-weight: bold; padding: 7px 12px; letter-spacing: 0.6px; text-transform: uppercase; }
        .box-body { padding: 10px 12px; }
        .box-body table td { padding: 4px 0; border: none; }

        /* ── TERMS TABLE ── */
        .terms-tbl td { padding: 5px 0; border: none; border-bottom: 1px solid #e5e7eb; font-size: 8pt; }
        .terms-tbl tr:last-child td { border-bottom: none; }
        .term-pct { width: 55px; color: #1e3a8a; font-weight: bold; text-align: center; }
        .term-trigger { width: 140px; color: #6b7280; }
        .term-amount { text-align: right; font-weight: bold; white-space: nowrap; }

        /* ── TNC LIST ── */
        .tnc-list { margin: 0; padding-left: 16px; }
        .tnc-list li { margin-bottom: 4px; font-size: 8pt; color: #374151; }

        /* ── CLOSING ── */
        .closing { color: #374151; font-size: 8.5pt; margin-top: 14px; }

        /* ── SIGNATURE ── */
        .sig-tbl { margin-top: 22px; page-break-inside: avoid; }
        .sig-tbl td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 12px; }
        .sig-tbl td:first-child { padding-left: 0; }
        .sig-tbl td:last-child { padding-right: 0; }
        .sig-box { border: 1px solid #e5e7eb; padding: 10px 10px 0; }
        .sig-lbl { color: #6b7280; font-size: 7.5pt; }
        .sig-party { color: #1e3a8a; font-size: 8.5pt; font-weight: bold; margin-top: 2px; }
        .sig-space { height: 58px; }
        .sig-line { border-top: 1px solid #6b7280; margin: 0 18px; padding-top: 4px; color: #374151; font-size: 7pt; }

        /* ── FOOTER ── */
        .doc-footer { border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 6.5pt; margin-top: 16px; padding-top: 5px; text-align: center; clear: both; }
    </style>
</head>
<body>
    @php($hasDiscount = $quotation->items->contains(fn ($i): bool => (float) $i->discount > 0))

    {{-- HEADER --}}
    <table><tr>
        <td class="hdr-logo">
            @if ($logoData)
                <img src="{{ $logoData }}" alt="Logo {{ $quotation->company->name }}">
            @endif
        </td>
        <td class="hdr-co">
            <div class="hdr-co-name">{{ $quotation->company->legal_name ?: $quotation->company->name }}</div>
            @if ($quotation->company->address)
                <div class="hdr-co-detail">
                    {{ $quotation->company->address }}@if ($quotation->company->postal_code), {{ $quotation->company->postal_code }}@endif<br>
                    @if ($quotation->company->phone)Tel: {{ $quotation->company->phone }}@endif
                    @if ($quotation->company->phone && $quotation->company->website) &nbsp;·&nbsp; @endif
                    @if ($quotation->company->website){{ preg_replace('#^https?://#i', '', rtrim($quotation->company->website, '/')) }}@endif
                </div>
            @endif
        </td>
        <td class="hdr-title">
            <div class="hdr-doc-title">PENAWARAN HARGA</div>
        </td>
    </tr></table>
    <hr class="hdr-divider">

    {{-- META BAND --}}
    <table class="meta-band"><tr>
        <td style="width: 32%;">
            <div class="meta-lbl">Nomor Penawaran</div>
            <div class="meta-val">{{ $quotation->code }}</div>
        </td>
        <td style="width: 24%;">
            <div class="meta-lbl">Tanggal Penawaran</div>
            <div class="meta-val" style="font-size: 8pt;">{{ $quotation->transaction_date->locale('id')->translatedFormat('d F Y') }}</div>
        </td>
        <td style="width: 24%;">
            <div class="meta-lbl">Berlaku Sampai</div>
            <div class="meta-val" style="font-size: 8pt;">{{ $quotation->transaction_date->addDays(30)->locale('id')->translatedFormat('d F Y') }}</div>
        </td>
        <td style="width: 20%;" class="meta-hi-cell">
            <div class="meta-lbl">Jenis Pekerjaan</div>
            <div class="meta-val" style="font-size: 7.5pt;">{{ $quotation->projectType?->name ?? '-' }}</div>
        </td>
    </tr></table>

    {{-- CLIENT --}}
    <div style="margin-bottom: 12px;">
        <div class="block-lbl">Kepada Yang Terhormat</div>
        <div class="client-name">{{ $quotation->client->name }}</div>
        @if ($quotation->client->address)
            <div class="client-addr">{{ $quotation->client->address }}</div>
        @endif
    </div>

    <div class="opening">
        Dengan hormat,<br><br>
        Sehubungan dengan kebutuhan Bapak/Ibu, <strong>{{ $quotation->company->legal_name ?: $quotation->company->name }}</strong>
        dengan hormat mengajukan Penawaran Harga kepada <strong>{{ $quotation->client->name }}</strong>
        untuk pelaksanaan pekerjaan <strong>{{ $quotation->projectType?->name ?? 'yang dimaksud' }}</strong>.<br><br>
        Penawaran ini disusun berdasarkan ruang lingkup kebutuhan yang telah disampaikan, dengan
        mempertimbangkan standar teknis, kualitas pengerjaan, serta kompetensi kami di bidang
        pengolahan air bersih dan pengelolaan lingkungan hidup. Adapun rincian penawaran harga
        adalah sebagai berikut:
    </div>

    {{-- RINCIAN PENAWARAN --}}
    <div class="sec-lbl">Rincian Penawaran</div>
    <table class="items-tbl">
        <colgroup>
            <col style="width: 6%;">
            <col style="width: {{ $hasDiscount ? '36%' : '44%' }};">
            <col style="width: 8%;">
            <col style="width: 10%;">
            <col style="width: {{ $hasDiscount ? '15%' : '0%' }};">
            <col style="width: 17%;">
            <col style="width: 18%;">
        </colgroup>
        <thead>
            <tr>
                <th class="center">No.</th>
                <th>Deskripsi Produk / Jasa</th>
                <th class="center">Qty</th>
                <th class="center">Satuan</th>
                @if ($hasDiscount)
                    <th class="right">Diskon</th>
                @endif
                <th class="right">Harga Satuan</th>
                <th class="right">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>
                        <div class="item-name">{{ $item->item->name }}</div>
                        @if ($item->notes)
                            <div class="item-note">{{ $item->notes }}</div>
                        @endif
                    </td>
                    <td class="center">{{ $item->qty }}</td>
                    <td class="center">{{ $item->unit->name }}</td>
                    @if ($hasDiscount)
                        <td class="right">
                            {{ (float) $item->discount > 0 ? $angka($item->discount) : '-' }}
                        </td>
                    @endif
                    <td class="right nowrap">{{ $angka($item->unit_price) }}</td>
                    <td class="right bold nowrap">{{ $angka($item->total_price) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SUMMARY --}}
    <table class="summary">
        <tr>
            <td class="spc"></td>
            <td class="muted">Subtotal</td>
            <td class="amt">{{ $rupiah($quotation->subtotal) }}</td>
        </tr>
        @if ((float) $quotation->discount_total > 0)
            <tr>
                <td class="spc"></td>
                <td class="muted">Total Diskon</td>
                <td class="amt">- {{ $rupiah($quotation->discount_total) }}</td>
            </tr>
        @endif
        @if ($quotation->vat_type->value !== 'none')
            <tr>
                <td class="spc"></td>
                <td class="muted">
                    PPN {{ $quotation->vat_type->value === 'include' ? '(termasuk)' : '(ditambahkan)' }}
                </td>
                <td class="amt">{{ $rupiah($quotation->vat_amount) }}</td>
            </tr>
        @endif
        <tr class="sep"><td colspan="3"></td></tr>
        <tr class="grand">
            <td class="spc"></td>
            <td>Total Penawaran</td>
            <td class="amt">{{ $rupiah($quotation->grand_total) }}</td>
        </tr>
    </table>

    {{-- SKEMA PEMBAYARAN --}}
    @if ($terms->count() > 0)
        <div class="content-box">
            <div class="box-hdr">Skema Pembayaran</div>
            <div class="box-body">
                <table class="terms-tbl">
                    @foreach ($terms as $term)
                        <tr>
                            <td>{{ $term->term_name }}</td>
                            <td class="term-pct">{{ $term->percentage }}%</td>
                            <td class="term-trigger">
                                {{ (int) $term->progress_threshold === 0 ? 'Down Payment' : 'Progress '.$term->progress_threshold.'%' }}
                            </td>
                            <td class="term-amount">{{ $rupiah($termAmounts[$term->id]) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endif

    {{-- SYARAT & KETENTUAN --}}
    <div class="content-box">
        <div class="box-hdr">Syarat &amp; Ketentuan</div>
        <div class="box-body">
            <ul class="tnc-list">
                <li>Harga penawaran berlaku selama 30 (tiga puluh) hari kalender sejak tanggal surat ini.</li>
                <li>Pembayaran dilakukan melalui transfer bank ke rekening resmi perusahaan.</li>
                <li>Pekerjaan dimulai setelah SPK/PO diterima dan Down Payment dikonfirmasi.</li>
                <li>Waktu pelaksanaan pekerjaan akan disepakati dalam SPK setelah penawaran diterima.</li>
                <li>Harga di luar penawaran ini atau perubahan lingkup pekerjaan akan diatur dalam addendum tersendiri.</li>
            </ul>
        </div>
    </div>

    {{-- CATATAN KHUSUS --}}
    @if ($quotation->notes)
        <div class="content-box">
            <div class="box-hdr">Catatan Khusus</div>
            <div class="box-body small" style="color: #374151; white-space: pre-line;">{{ $quotation->notes }}</div>
        </div>
    @endif

    <div class="closing">
        Demikian penawaran ini kami sampaikan. Besar harapan kami untuk dapat bekerja sama dengan Bapak/Ibu.
        Atas perhatian dan kepercayaannya, kami ucapkan terima kasih.
    </div>

    {{-- TANDA TANGAN --}}
    <table class="sig-tbl"><tr>
        <td>
            <div class="sig-box">
                <div class="sig-lbl">Disetujui oleh,</div>
                <div class="sig-party">{{ $quotation->client->name }}</div>
                <div class="sig-space"></div>
                <div class="sig-line">Nama, Jabatan &amp; Stempel</div>
            </div>
        </td>
        <td>
            <div class="sig-box">
                <div class="sig-lbl">Hormat kami,</div>
                <div class="sig-party">{{ $quotation->company->legal_name ?: $quotation->company->name }}</div>
                <div class="sig-space"></div>
                <div class="sig-line">Nama, Jabatan &amp; Stempel</div>
            </div>
        </td>
    </tr></table>

    <div class="doc-footer">
        {{ $quotation->company->legal_name ?: $quotation->company->name }}
        &nbsp;·&nbsp; Penawaran {{ $quotation->code }}
    </div>

</body>
</html>

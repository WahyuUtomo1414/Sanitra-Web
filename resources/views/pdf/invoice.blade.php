<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->code }}</title>
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
        .hdr-doc-title { color: #1e3a8a; font-size: 26pt; font-weight: bold; line-height: 1; }
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

        /* ── SECTION LABEL ── */
        .sec-lbl { color: #1e3a8a; font-size: 7.5pt; font-weight: bold; letter-spacing: 0.8px; text-transform: uppercase; border-bottom: 1.5px solid #bfdbfe; padding-bottom: 4px; margin: 14px 0 8px; page-break-after: avoid; }

        /* ── ITEMS TABLE ── */
        .items-tbl { font-size: 8pt; }
        .items-tbl thead { display: table-header-group; }
        .items-tbl tr { page-break-inside: avoid; }
        .items-tbl th { background: #1e3a8a; color: #ffffff; font-size: 7pt; font-weight: bold; padding: 7px 8px; border: 1px solid #1e3a8a; }
        .items-tbl td { border: 1px solid #e5e7eb; padding: 8px 8px; }
        .items-tbl tbody tr:nth-child(even) td { background: #f0f4ff; }
        .term-name { font-weight: bold; color: #111827; }
        .term-desc { color: #6b7280; font-size: 7pt; margin-top: 2px; }

        /* ── SUMMARY ── */
        .summary { font-size: 8.5pt; margin-top: 10px; page-break-inside: avoid; }
        .summary td { padding: 3.5px 8px; }
        .summary .spc { width: 56%; }
        .summary .amt { text-align: right; white-space: nowrap; }
        .summary .sep td { border-top: 1px solid #bfdbfe; padding-top: 6px; }
        .summary .grand td { background: #1e3a8a; color: #ffffff; font-size: 10.5pt; font-weight: bold; padding: 9px 8px; margin-top: 4px; }
        .tax-note { color: #6b7280; font-size: 7pt; margin-top: 5px; text-align: right; }

        /* ── PAYMENT BOX ── */
        .pay-box { border: 1px solid #bfdbfe; margin-top: 14px; page-break-inside: avoid; }
        .pay-hdr { background: #1e3a8a; color: #ffffff; font-size: 7.5pt; font-weight: bold; padding: 7px 12px; letter-spacing: 0.6px; text-transform: uppercase; }
        .pay-body { padding: 10px 12px; }
        .pay-bank { color: #1e3a8a; font-size: 9pt; font-weight: bold; }
        .pay-num { color: #111827; font-size: 12.5pt; font-weight: bold; letter-spacing: 0.3px; margin: 3px 0 2px; }
        .pay-name { color: #374151; font-size: 8pt; }
        .pay-ref { color: #6b7280; font-size: 7pt; margin-top: 7px; border-top: 1px solid #dbeafe; padding-top: 6px; }

        /* ── NOTES ── */
        .notes-box { border-left: 3px solid #bfdbfe; padding: 7px 10px; color: #374151; font-size: 8pt; white-space: pre-line; page-break-inside: avoid; margin-top: 12px; text-align: justify; }

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
        .doc-footer { border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 6.5pt; margin-top: 16px; padding-top: 5px; text-align: center; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <table><tr>
        <td class="hdr-logo">
            @if ($logoData)
                <img src="{{ $logoData }}" alt="Logo {{ $invoice->company?->name }}">
            @endif
        </td>
        <td class="hdr-co">
            <div class="hdr-co-name">{{ $invoice->company?->legal_name ?: $invoice->company?->name }}</div>
            @if ($invoice->company?->address)
                <div class="hdr-co-detail">
                    {{ $invoice->company->address }}@if ($invoice->company->postal_code), {{ $invoice->company->postal_code }}@endif<br>
                    @if ($invoice->company->phone)Tel: {{ $invoice->company->phone }}@endif
                    @if ($invoice->company->phone && $invoice->company->website) &nbsp;·&nbsp; @endif
                    @if ($invoice->company->website){{ preg_replace('#^https?://#i', '', rtrim($invoice->company->website, '/')) }}@endif
                </div>
            @endif
        </td>
        <td class="hdr-title">
            <div class="hdr-doc-kicker">Dokumen Penagihan</div>
            <div class="hdr-doc-title">INVOICE</div>
        </td>
    </tr></table>
    <hr class="hdr-divider">

    {{-- META BAND --}}
    <table class="meta-band"><tr>
        <td style="width: 28%;">
            <div class="meta-lbl">Nomor Invoice</div>
            <div class="meta-val">{{ $invoice->code }}</div>
        </td>
        <td style="width: 24%;">
            <div class="meta-lbl">Tanggal Diterbitkan</div>
            <div class="meta-val" style="font-size: 8pt;">{{ $invoice->transaction_date?->locale('id')->translatedFormat('d F Y') }}</div>
        </td>
        <td style="width: 24%;">
            <div class="meta-lbl">Jatuh Tempo</div>
            <div class="meta-val" style="font-size: 8pt;">{{ $invoice->due_date?->locale('id')->translatedFormat('d F Y') }}</div>
        </td>
        <td style="width: 24%;" class="meta-hi-cell">
            <div class="meta-lbl">No. Referensi</div>
            <div class="meta-val" style="font-size: 8pt;">{{ $invoice->order?->code ?? '-' }}</div>
        </td>
    </tr></table>

    {{-- CLIENT --}}
    <div style="margin-bottom: 12px;">
        <div class="block-lbl">Ditagihkan Kepada</div>
        <div class="client-name">{{ $invoice->client?->name }}</div>
        @if ($invoice->client?->address)
            <div class="client-addr">{{ $invoice->client->address }}</div>
        @endif
    </div>

    {{-- RINCIAN TERMIN --}}
    <div class="sec-lbl">Rincian Termin</div>
    <table class="items-tbl">
        <colgroup>
            <col style="width: 7%;">
            <col style="width: 56%;">
            <col style="width: 12%;">
            <col style="width: 25%;">
        </colgroup>
        <thead>
            <tr>
                <th class="center">No.</th>
                <th>Deskripsi Termin</th>
                <th class="center">Persentase</th>
                <th class="right">Nilai (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td class="center">{{ $item->sequence }}</td>
                    <td>
                        <span class="term-name">{{ $item->salesTerm?->term_name ?: $item->description }}</span>
                        @if ($item->description && $item->description !== $item->salesTerm?->term_name)
                            <div class="term-desc">{{ $item->description }}</div>
                        @endif
                        @if ($item->salesTerm?->notes)
                            <div class="term-desc">{{ $item->salesTerm->notes }}</div>
                        @endif
                    </td>
                    <td class="center">{{ $item->percentage }}%</td>
                    <td class="right nowrap">{{ $rupiah($item->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SUMMARY --}}
    <table class="summary">
        @if ($invoice->vat_type?->value !== 'none')
            <tr>
                <td class="spc"></td>
                <td class="muted">Subtotal</td>
                <td class="amt">{{ $rupiah($invoice->subtotal) }}</td>
            </tr>
            <tr>
                <td class="spc"></td>
                <td class="muted">PPN</td>
                <td class="amt">{{ $rupiah($invoice->vat_amount) }}</td>
            </tr>
            <tr class="sep"><td colspan="3"></td></tr>
        @endif
        <tr class="grand">
            <td class="spc"></td>
            <td>Total Tagihan</td>
            <td class="amt">{{ $rupiah($invoice->grand_total) }}</td>
        </tr>
    </table>
    @if ($invoice->vat_type?->value === 'include')
        <div class="tax-note">* PPN sudah termasuk dalam total tagihan.</div>
    @elseif ($invoice->vat_type?->value === 'none')
        <div class="tax-note">* Tagihan tanpa PPN.</div>
    @endif

    {{-- INFORMASI PEMBAYARAN --}}
    <div class="pay-box">
        <div class="pay-hdr">Informasi Pembayaran</div>
        <div class="pay-body">
            <div class="pay-bank">{{ $invoice->bankAccount?->bank_name ?: '-' }}</div>
            <div class="pay-num">{{ $invoice->bankAccount?->account_number ?: '-' }}</div>
            <div class="pay-name">a.n. {{ $invoice->bankAccount?->account_name ?: '-' }}</div>
            <div class="pay-ref">
                Mohon cantumkan nomor invoice <strong>{{ $invoice->code }}</strong> sebagai keterangan transfer.
            </div>
        </div>
    </div>

    @if ($invoice->notes)
        <div class="notes-box">{{ $invoice->notes }}</div>
    @endif

    {{-- TANDA TANGAN --}}
    <table class="sig-tbl"><tr>
        <td>
            <div class="sig-box">
                <div class="sig-lbl">Disetujui oleh,</div>
                <div class="sig-party">{{ $invoice->client?->name }}</div>
                <div class="sig-space"></div>
                <div class="sig-line">Nama, Jabatan &amp; Stempel</div>
            </div>
        </td>
        <td>
            <div class="sig-box">
                <div class="sig-lbl">Hormat kami,</div>
                <div class="sig-party">{{ $invoice->company?->legal_name ?: $invoice->company?->name }}</div>
                <div class="sig-space"></div>
                <div class="sig-line">Nama, Jabatan &amp; Stempel</div>
            </div>
        </td>
    </tr></table>

    <div class="doc-footer">
        {{ $invoice->company?->legal_name ?: $invoice->company?->name }}
        &nbsp;·&nbsp; Invoice {{ $invoice->code }}
    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->code }}</title>
    <style>
        @page { size: A4 portrait; margin: 17mm 17mm 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; color: #243247; font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .muted { color: #66758a; }
        .small { font-size: 7.5pt; }
        .right { text-align: right; }
        .center { text-align: center; }
        .nowrap { white-space: nowrap; }
        .letterhead { border-bottom: 2px solid #166534; }
        .letterhead td { padding-bottom: 12px; }
        .letterhead-logo { width: 178px; padding-right: 18px; vertical-align: middle; }
        .letterhead-logo img { max-width: 160px; max-height: 90px; }
        .company-name { color: #173c2b; font-size: 13pt; font-weight: bold; line-height: 1.2; }
        .company-detail { color: #66758a; font-size: 7.5pt; margin-top: 5px; }
        .company-contact { color: #365747; font-size: 7.5pt; margin-top: 5px; }
        .company-contact-label { color: #66758a; font-weight: bold; margin-right: 3px; }
        .company-contact-separator { color: #b2c4b7; padding: 0 9px; }
        .document-header { margin: 18px 0 17px; }
        .document-kicker { color: #166534; font-size: 7.5pt; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; }
        .document-title { color: #152d22; font-size: 21pt; font-weight: bold; line-height: 1.15; margin-top: 3px; }
        .document-code { color: #243247; font-size: 10pt; font-weight: bold; margin-top: 3px; }
        .eyebrow { color: #607267; font-size: 7pt; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 5px; }
        .information { border-top: 1px solid #d9e3dd; border-bottom: 1px solid #d9e3dd; margin-bottom: 20px; }
        .information > tbody > tr > td { width: 50%; padding: 11px 10px 11px 0; }
        .information > tbody > tr > td + td { border-left: 1px solid #d9e3dd; padding-left: 15px; padding-right: 0; }
        .client-name { color: #173c2b; font-size: 10pt; font-weight: bold; }
        .meta td { padding: 1px 0; }
        .meta-label { width: 100px; color: #66758a; }
        .section-title { color: #173c2b; font-size: 8.5pt; font-weight: bold; letter-spacing: .7px; text-transform: uppercase; border-bottom: 1px solid #cbd9cf; padding-bottom: 5px; margin: 16px 0 8px; page-break-after: avoid; }
        .line-table { table-layout: fixed; font-size: 7.8pt; }
        .line-table thead { display: table-header-group; }
        .line-table tr { page-break-inside: avoid; }
        .line-table th { background: #eaf2ec; border-top: 1px solid #9bb9a4; border-bottom: 1px solid #9bb9a4; color: #244b33; font-size: 7pt; font-weight: bold; padding: 7px 6px; }
        .line-table td { border-bottom: 1px solid #e3eae5; padding: 8px 6px; word-wrap: break-word; }
        .line-table tbody tr:nth-child(even) { background: #fafcfb; }
        .term-name { color: #173c2b; font-weight: bold; }
        .term-description { color: #66758a; display: block; font-size: 7pt; font-weight: normal; margin-top: 2px; }
        .summary { font-size: 8pt; margin-top: 11px; page-break-inside: avoid; }
        .summary td { padding: 4px 7px; }
        .summary .spacer { width: 53%; }
        .summary .amount { width: 25%; text-align: right; white-space: nowrap; }
        .summary .grand td { background: #eaf2ec; border-top: 1px solid #9bb9a4; color: #173c2b; font-size: 10pt; font-weight: bold; padding-top: 8px; padding-bottom: 8px; }
        .tax-note { color: #66758a; font-size: 7pt; margin-top: 5px; text-align: right; }
        .payment { background: #f5f8f6; border-left: 3px solid #166534; padding: 10px 12px; page-break-inside: avoid; }
        .payment-bank { color: #173c2b; font-size: 9pt; font-weight: bold; }
        .payment-number { color: #173c2b; font-size: 11pt; font-weight: bold; letter-spacing: .4px; margin: 4px 0 2px; }
        .notes { border-left: 2px solid #9bb9a4; color: #3b4656; padding: 7px 10px; white-space: pre-line; page-break-inside: avoid; }
        .signature { margin-top: 12px; page-break-inside: avoid; }
        .signature td { width: 50%; }
        .signature .space { height: 30px; }
        .signature .line { border-top: 1px solid #89988e; color: #66758a; font-size: 7.5pt; padding-top: 4px; width: 210px; }
        .footer { border-top: 1px solid #d9e3dd; color: #849187; font-size: 7pt; margin-top: 12px; padding-top: 6px; }
    </style>
</head>
<body>
    <table class="letterhead"><tr>
        @if ($logoData)
            <td class="letterhead-logo"><img src="{{ $logoData }}" alt="Logo {{ $invoice->company?->name }}"></td>
        @endif
        <td>
            <div class="company-name">{{ $invoice->company?->legal_name ?: $invoice->company?->name }}</div>
            @if ($invoice->company?->address)
                <div class="company-detail">{{ $invoice->company->address }}@if ($invoice->company->postal_code), {{ $invoice->company->postal_code }}@endif</div>
            @endif
            @if ($invoice->company?->phone || $invoice->company?->website)
                <div class="company-contact">
                    @if ($invoice->company?->phone)<span class="company-contact-label">Tel.</span>{{ $invoice->company->phone }}@endif
                    @if ($invoice->company?->phone && $invoice->company?->website)<span class="company-contact-separator">|</span>@endif
                    @if ($invoice->company?->website)<span class="company-contact-label">Web.</span>{{ preg_replace('#^https?://#i', '', rtrim($invoice->company->website, '/')) }}@endif
                </div>
            @endif
        </td>
    </tr></table>

    <table class="document-header"><tr>
        <td><div class="document-kicker">Dokumen Penagihan</div><div class="document-title">INVOICE</div></td>
        <td class="right" style="vertical-align: bottom;"><div class="eyebrow">Nomor Invoice</div><div class="document-code">{{ $invoice->code }}</div></td>
    </tr></table>

    <table class="information"><tr>
        <td>
            <div class="eyebrow">Ditagihkan kepada</div>
            <div class="client-name">{{ $invoice->client?->name }}</div>
            @if ($invoice->client?->address)<div>{{ $invoice->client->address }}</div>@endif
        </td>
        <td>
            <div class="eyebrow">Informasi Tagihan</div>
            <table class="meta">
                <tr><td class="meta-label">Tanggal Invoice</td><td>{{ $invoice->transaction_date?->locale('id')->translatedFormat('d F Y') }}</td></tr>
                <tr><td class="meta-label">Jatuh Tempo</td><td><strong>{{ $invoice->due_date?->locale('id')->translatedFormat('d F Y') }}</strong></td></tr>
                <tr><td class="meta-label">Sales Order</td><td>{{ $invoice->order?->code ?: '-' }}</td></tr>
            </table>
        </td>
    </tr></table>

    <div class="section-title">Rincian Termin</div>
    <table class="line-table">
        <colgroup><col style="width: 8%;"><col style="width: 52%;"><col style="width: 15%;"><col style="width: 25%;"></colgroup>
        <thead><tr><th class="center">No.</th><th>Termin</th><th class="center">Persentase</th><th class="right">Nilai (Rp)</th></tr></thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td class="center">{{ $item->sequence }}</td>
                    <td>
                        <span class="term-name">{{ $item->salesTerm?->term_name ?: $item->description }}</span>
                        @if ($item->description && $item->description !== $item->salesTerm?->term_name)
                            <span class="term-description">{{ $item->description }}</span>
                        @endif
                    </td>
                    <td class="center">{{ $item->percentage }}%</td>
                    <td class="right nowrap">{{ $rupiah($item->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        @if ($invoice->vat_type?->value !== 'none')
            <tr><td class="spacer"></td><td>Subtotal</td><td class="amount">{{ $rupiah($invoice->subtotal) }}</td></tr>
            <tr><td></td><td>PPN</td><td class="amount">{{ $rupiah($invoice->vat_amount) }}</td></tr>
        @endif
        <tr class="grand"><td></td><td>Total Tagihan</td><td class="amount">{{ $rupiah($invoice->grand_total) }}</td></tr>
    </table>
    @if ($invoice->vat_type?->value === 'include')
        <div class="tax-note">PPN sudah termasuk dalam total tagihan.</div>
    @elseif ($invoice->vat_type?->value === 'none')
        <div class="tax-note">Tagihan tanpa PPN.</div>
    @endif

    <div class="section-title">Instruksi Pembayaran</div>
    <div class="payment">
        <div class="small muted">Silakan melakukan pembayaran ke rekening berikut:</div>
        <div class="payment-bank">{{ $invoice->bankAccount?->bank_name ?: '-' }}</div>
        <div class="payment-number">{{ $invoice->bankAccount?->account_number ?: '-' }}</div>
        <div>a.n. {{ $invoice->bankAccount?->account_name ?: '-' }}</div>
    </div>

    @if ($invoice->notes)
        <div class="section-title">Catatan</div>
        <div class="notes">{{ $invoice->notes }}</div>
    @endif

    <table class="signature"><tr><td></td><td>
        <div class="small muted">Hormat kami,</div>
        <div class="company-name" style="font-size: 9pt; margin-top: 3px;">{{ $invoice->company?->legal_name ?: $invoice->company?->name }}</div>
        <div class="space"></div>
        <div class="line">Nama, jabatan, dan tanda tangan</div>
    </td></tr></table>

    <div class="footer">{{ $invoice->company?->legal_name ?: $invoice->company?->name }} &nbsp; | &nbsp; Invoice {{ $invoice->code }}</div>
</body>
</html>

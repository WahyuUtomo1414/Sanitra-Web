<div>
    <!-- Because you are alive, everything is possible. - Thich Nhat Hanh -->
</div>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 12px; margin: 22px 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; vertical-align: top; }
        th { background: #eee; text-align: left; }
        .bordered th, .bordered td { border: 1px solid #ccc; }
        .right { text-align: right; }
        .header { border-bottom: 2px solid #333; padding-bottom: 12px; margin-bottom: 18px; }
        .logo { max-width: 110px; max-height: 65px; float: right; }
        .muted { color: #666; }
        .sign { margin-top: 40px; width: 45%; float: right; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        @if ($logoData)
            <img class="logo" src="{{ $logoData }}" alt="Logo">
        @endif
        <h1>{{ $quotation->company->name }}</h1>
        <div>{{ $quotation->company->address }}</div>
        <div>{{ $quotation->company->phone }} · {{ $quotation->company->email }}</div>
    </div>

    <h2>QUOTATION</h2>
    <table>
        <tr><td>Nomor</td><td>{{ $quotation->code }}</td><td>Klien</td><td>{{ $quotation->client->name }}</td></tr>
        <tr><td>Tanggal</td><td>{{ $quotation->transaction_date->locale('id')->translatedFormat('d F Y') }}</td><td>Alamat Klien</td><td>{{ $quotation->client->address }}</td></tr>
        <tr><td>PIC Klien</td><td>{{ $quotation->client->picEmployee?->name ?? '-' }}</td><td>Skema Pembayaran</td><td>{{ $quotation->payment_type }}</td></tr>
    </table>

    <h2>Rincian Item</h2>
    <table class="bordered">
        <thead>
            <tr><th>No.</th><th>Item</th><th>Qty</th><th>Unit</th><th class="right">Harga</th><th class="right">Diskon</th><th class="right">Total</th></tr>
        </thead>
        <tbody>
            @foreach ($quotation->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->item->name }}@if ($item->notes)<br><span class="muted">{{ $item->notes }}</span>@endif</td>
                    <td>{{ $item->qty }}</td>
                    <td>{{ $item->unit->name }}</td>
                    <td class="right">{{ $rupiah($item->unit_price) }}</td>
                    <td class="right">{{ $rupiah($item->discount) }}</td>
                    <td class="right">{{ $rupiah($item->total_price) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 12px;">
        <tr><td style="width: 70%"></td><td>Subtotal</td><td class="right">{{ $rupiah($quotation->subtotal) }}</td></tr>
        <tr><td></td><td>Diskon item</td><td class="right">{{ $rupiah($quotation->discount_total) }}</td></tr>
        <tr><td></td><td>PPN ({{ $quotation->vat_type->label() }})</td><td class="right">{{ $rupiah($quotation->vat_amount) }}</td></tr>
        <tr><td></td><th>Grand Total</th><th class="right">{{ $rupiah($quotation->grand_total) }}</th></tr>
    </table>
    <div class="muted">Subtotal dan total item sudah termasuk potongan diskon baris.</div>

    <h2>Skema Termin</h2>
    <table class="bordered">
        <thead><tr><th>Urutan</th><th>Termin</th><th>Persentase</th><th>Threshold Progres</th><th class="right">Nilai</th></tr></thead>
        <tbody>
            @foreach ($terms as $term)
                <tr>
                    <td>{{ $term->sequence }}</td>
                    <td>{{ $term->term_name }}</td>
                    <td>{{ $term->percentage }}%</td>
                    <td>{{ $term->progress_threshold }}%</td>
                    <td class="right">{{ $rupiah($termAmounts[$term->id]) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($quotation->notes)
        <h2>Syarat & Ketentuan</h2>
        <div>{!! nl2br(e($quotation->notes)) !!}</div>
    @endif
    <div class="sign">
        <div>{{ $quotation->company->name }}</div>
        <div style="height: 65px;"></div>
        <div>________________________</div>
        <div>Penanggung Jawab</div>
    </div>
</body>
</html>

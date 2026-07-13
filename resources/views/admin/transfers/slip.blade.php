<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $transfer->transfer_number }} — Transfer Slip</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #111; padding: 24px; max-width: 640px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { font-size: 18px; letter-spacing: 1px; }
        .header .sub { font-size: 12px; color: #555; margin-top: 2px; }
        .meta { display: flex; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
        .meta .box { flex: 1; border: 1px solid #ddd; border-radius: 6px; padding: 10px 12px; }
        .meta .label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #888; }
        .meta .value { font-weight: 700; font-size: 14px; margin-top: 2px; }
        .meta .small { font-size: 11px; color: #555; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; border-bottom: 1.5px solid #111; padding: 6px 4px; }
        td { padding: 6px 4px; border-bottom: 1px solid #eee; vertical-align: top; }
        .qty { text-align: center; }
        .mono { font-family: Consolas, monospace; font-size: 12px; }
        .totals { display: flex; justify-content: flex-end; gap: 24px; font-size: 13px; margin-bottom: 24px; }
        .totals strong { font-size: 15px; }
        .note { border: 1px dashed #bbb; border-radius: 6px; padding: 8px 12px; font-size: 12px; color: #444; margin-bottom: 24px; }
        .signatures { display: flex; justify-content: space-between; gap: 32px; margin-top: 48px; }
        .sig { flex: 1; text-align: center; }
        .sig .line { border-top: 1px solid #111; margin-bottom: 4px; }
        .sig .who { font-size: 11px; color: #555; }
        .print-btn { position: fixed; top: 16px; right: 16px; background: #111; color: #fff; border: 0; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-size: 13px; }
        @media print { .print-btn { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨 Print</button>

    <div class="header">
        <h1>STOCK TRANSFER SLIP</h1>
        <div class="sub">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</div>
    </div>

    <div class="meta">
        <div class="box">
            <div class="label">Transfer #</div>
            <div class="value mono">{{ $transfer->transfer_number }}</div>
            <div class="small">{{ $transfer->created_at->format('d M Y — h:i A') }}</div>
        </div>
        <div class="box">
            <div class="label">From</div>
            <div class="value">{{ $transfer->from_label }}</div>
            @if($transfer->fromShop?->address)<div class="small">{{ $transfer->fromShop->address }}</div>@endif
        </div>
        <div class="box">
            <div class="label">To</div>
            <div class="value">{{ $transfer->to_label }}</div>
            @if($transfer->toShop?->address)<div class="small">{{ $transfer->toShop->address }}</div>@endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>Product</th>
                <th>Serial / Color</th>
                <th class="qty" style="width: 60px;">Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transfer->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $item->product_name }}</strong></td>
                <td>
                    @if($item->serial_code)
                        <span class="mono">{{ $item->serial_code }}</span>
                    @elseif($item->color_name)
                        {{ $item->color_name }}
                    @else
                        —
                    @endif
                </td>
                <td class="qty">{{ $item->quantity }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <span>Products: <strong>{{ $transfer->total_items }}</strong></span>
        <span>Total Units: <strong>{{ $transfer->total_qty }}</strong></span>
    </div>

    @if($transfer->note)
    <div class="note"><strong>Note:</strong> {{ $transfer->note }}</div>
    @endif

    <div class="signatures">
        <div class="sig">
            <div style="height: 40px;"></div>
            <div class="line"></div>
            <div class="who">Prepared by — {{ $transfer->creator?->name ?? '' }}</div>
        </div>
        <div class="sig">
            <div style="height: 40px;"></div>
            <div class="line"></div>
            <div class="who">Received by</div>
        </div>
    </div>
</body>
</html>

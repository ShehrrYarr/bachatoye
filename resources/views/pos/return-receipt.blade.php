<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Return Receipt — {{ $return->return_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; width: 80mm; max-width: 80mm; margin: 0 auto; padding: 4mm; color: #000; background: #fff; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 4px 0; }
        .divider-solid { border-top: 1px solid #000; margin: 4px 0; }
        .row { display: flex; justify-content: space-between; }
        @media print {
            @page { size: 80mm auto; margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="center" style="margin-bottom: 6px;">
        <div class="bold" style="font-size: 16px;">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</div>
        <div>{{ \App\Models\Setting::get('shop_address') }}</div>
        <div>{{ \App\Models\Setting::get('shop_phone') }}</div>
    </div>

    <div class="divider-solid"></div>

    <div class="center bold" style="font-size: 14px; margin: 4px 0;">*** RETURN RECEIPT ***</div>

    <div class="divider-solid"></div>

    <div style="margin-bottom: 4px;">
        <div class="row"><span>Return #:</span><span class="bold">{{ $return->return_number }}</span></div>
        <div class="row"><span>Original Order:</span><span>{{ $return->order->order_number }}</span></div>
        <div class="row"><span>Date:</span><span>{{ $return->created_at->format('d/m/Y H:i') }}</span></div>
        @if($return->order->customer)
        <div class="row"><span>Customer:</span><span>{{ $return->order->customer->name }}</span></div>
        @endif
        <div class="row"><span>Reason:</span><span>{{ ucfirst(str_replace('_', ' ', $return->reason)) }}</span></div>
    </div>

    <div class="divider"></div>

    <div class="row bold" style="margin-bottom: 2px;">
        <span style="flex:1">Item</span>
        <span style="width:30px;text-align:center">Qty</span>
        <span style="width:60px;text-align:right">Amount</span>
    </div>
    <div class="divider"></div>

    @foreach($return->items as $item)
    <div class="row" style="margin-bottom: 3px;">
        <span style="flex:1;word-break:break-word">{{ $item->product_name }}</span>
        <span style="width:30px;text-align:center">x{{ $item->quantity }}</span>
        <span style="width:60px;text-align:right">Rs.{{ number_format($item->line_total) }}</span>
    </div>
    @endforeach

    <div class="divider-solid"></div>

    <div class="row bold" style="font-size: 14px; margin-bottom: 2px;">
        <span>REFUND</span>
        <span>Rs.{{ number_format($return->refund_amount) }}</span>
    </div>

    <div class="divider"></div>

    <div class="row">
        <span>Refund Method:</span>
        <span class="bold">
            @if($return->refund_method === 'cash') Cash
            @elseif($return->refund_method === 'khata_credit') Khata Credit
            @else Exchange @endif
        </span>
    </div>

    @if($return->restock)
    <div style="margin-top: 2px; font-size: 11px;">Items restocked to inventory.</div>
    @endif

    <div class="divider-solid"></div>

    <div class="center" style="font-size: 11px; margin-top: 4px;">
        <div>Items exchanged/returned within 7-day policy</div>
        <div style="margin-top: 2px;">{{ \App\Models\Setting::get('shop_phone') }}</div>
        <div style="margin-top: 4px; font-size: 10px;">Printed: {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>

    <div class="no-print" style="text-align:center;margin-top:16px;">
        <button onclick="window.print()" style="background:#1d4ed8;color:white;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;font-size:13px;margin-right:8px;">Print</button>
        <button onclick="window.close()" style="background:#6b7280;color:white;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;font-size:13px;">Close</button>
    </div>
    <script>
        if (window.opener) { window.addEventListener('load', () => window.print()); }
    </script>
</body>
</html>

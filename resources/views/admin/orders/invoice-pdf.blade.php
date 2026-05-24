<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; padding: 24px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 24px; }
        .shop-name { font-size: 18px; font-weight: 700; color: #1f2937; }
        .shop-meta { font-size: 10px; color: #6b7280; margin-top: 4px; }
        .invoice-label { font-size: 24px; font-weight: 700; color: #4f46e5; text-align: right; }
        .invoice-num { font-family: monospace; font-size: 10px; color: #6b7280; }
        .grid-2 { display: flex; gap: 48px; margin-bottom: 24px; }
        .grid-2 > div { flex: 1; }
        h3 { font-size: 9px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
        .bill-name { font-size: 13px; font-weight: 600; }
        .bill-meta { font-size: 10px; color: #4b5563; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { text-align: left; padding: 6px 8px; font-size: 9px; font-weight: 600; color: #9ca3af; text-transform: uppercase; border-bottom: 2px solid #e5e7eb; }
        th.right { text-align: right; }
        td { padding: 8px; font-size: 10px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        td.right { text-align: right; }
        .item-name { font-size: 11px; font-weight: 500; }
        .item-barcode { font-family: monospace; font-size: 9px; color: #9ca3af; }
        .totals-row td { border-bottom: none; padding: 3px 8px; }
        .total-final td { border-top: 2px solid #e5e7eb; padding-top: 8px; font-size: 13px; font-weight: 700; }
        .footer { margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 12px; text-align: center; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="shop-name">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</div>
            @if(\App\Models\Setting::get('shop_address'))
            <div class="shop-meta">{{ \App\Models\Setting::get('shop_address') }}</div>
            @endif
            @if(\App\Models\Setting::get('shop_phone'))
            <div class="shop-meta">{{ \App\Models\Setting::get('shop_phone') }}</div>
            @endif
        </div>
        <div style="text-align:right">
            <div class="invoice-label">INVOICE</div>
            <div class="invoice-num">{{ $order->order_number }}</div>
            <div class="invoice-num">{{ $order->created_at->format('d M Y') }}</div>
        </div>
    </div>

    <div class="grid-2">
        <div>
            <h3>Bill To</h3>
            <div class="bill-name">{{ $order->customer_name }}</div>
            <div class="bill-meta">{{ $order->customer_phone }}</div>
            @if($order->customer_email)<div class="bill-meta">{{ $order->customer_email }}</div>@endif
            @if($order->delivery_address)<div class="bill-meta">{{ $order->delivery_address }}</div>@endif
            @if($order->city)<div class="bill-meta">{{ $order->city }}</div>@endif
        </div>
        <div>
            <h3>Order Details</h3>
            <div class="bill-meta">Status: <strong>{{ ucfirst($order->status) }}</strong></div>
            <div class="bill-meta">Payment: <strong>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</strong></div>
            <div class="bill-meta">Payment Status: <strong>{{ ucfirst($order->payment_status) }}</strong></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th>Item</th>
                <th class="right" style="width:80px">Price</th>
                <th class="right" style="width:40px">Qty</th>
                <th class="right" style="width:90px">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    <div class="item-name">{{ $item->product_name }}</div>
                    @if($item->product_barcode)<div class="item-barcode">{{ $item->product_barcode }}</div>@endif
                </td>
                <td class="right">Rs. {{ number_format($item->unit_price, 2) }}</td>
                <td class="right">{{ $item->quantity }}</td>
                <td class="right">Rs. {{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="4" class="right" style="color:#6b7280">Subtotal</td>
                <td class="right">Rs. {{ number_format($order->subtotal, 2) }}</td>
            </tr>
            @if($order->discount_amount > 0)
            <tr class="totals-row">
                <td colspan="4" class="right" style="color:#ef4444">Discount</td>
                <td class="right" style="color:#ef4444">– Rs. {{ number_format($order->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if($order->delivery_charge > 0)
            <tr class="totals-row">
                <td colspan="4" class="right" style="color:#6b7280">Delivery</td>
                <td class="right">Rs. {{ number_format($order->delivery_charge, 2) }}</td>
            </tr>
            @endif
            <tr class="total-final">
                <td colspan="4" class="right">TOTAL</td>
                <td class="right" style="color:#4f46e5">Rs. {{ number_format($order->total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($order->delivery_notes)
    <div style="font-size:10px;color:#6b7280;margin-bottom:12px"><strong>Notes:</strong> {{ $order->delivery_notes }}</div>
    @endif

    <div class="footer">
        Thank you for your business! — {{ \App\Models\Setting::get('shop_name', 'MobileHub') }}
    </div>
</body>
</html>

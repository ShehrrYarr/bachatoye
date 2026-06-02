<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt — {{ $order->order_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Roboto', sans-serif; font-size: 12px; width: 80mm; max-width: 80mm; margin: 0 auto; padding: 4mm; color: #000; background: #fff; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 4px 0; }
        .divider-solid { border-top: 1px solid #000; margin: 4px 0; }
        .row { display: flex; justify-content: space-between; }
        .item-name { flex: 1; word-break: break-word; }
        .item-qty { width: 30px; text-align: center; flex-shrink: 0; }
        .item-price { width: 60px; text-align: right; flex-shrink: 0; }
        .total-row { font-size: 14px; }
        .logo { font-size: 18px; font-weight: bold; letter-spacing: 1px; }
        @media print {
            @page { size: 80mm auto; margin: 0; }
            body { width: 80mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    {{-- Shop Header --}}
    <div class="center" style="margin-bottom: 6px;">
        <div class="logo">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</div>
        <div>{{ \App\Models\Setting::get('shop_address', 'Lahore, Pakistan') }}</div>
        <div>Tel: {{ \App\Models\Setting::get('shop_phone', '03001234567') }}</div>
    </div>

    <div class="divider-solid"></div>

    {{-- Receipt Info --}}
    <div style="margin-bottom: 4px;">
        <div class="row">
            <span>Receipt #:</span>
            <span class="bold">{{ $order->order_number }}</span>
        </div>
        <div class="row">
            <span>Date:</span>
            <span>{{ $order->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="row">
            <span>Served by:</span>
            <span>{{ $order->servedBy?->name ?? 'N/A' }}</span>
        </div>
        @if($order->customer)
        <div class="row">
            <span>Customer:</span>
            <span>{{ $order->customer->name }}</span>
        </div>
        @endif
    </div>

    <div class="divider"></div>

    {{-- Column headers --}}
    <div class="row bold" style="margin-bottom: 2px;">
        <span class="item-name">Item</span>
        <span class="item-qty">Qty</span>
        <span class="item-price">Total</span>
    </div>
    <div class="divider"></div>

    {{-- Items --}}
    @foreach($order->items as $item)
    <div style="margin-bottom: 3px;">
        <div class="item-name" style="font-weight: bold;">{{ $item->product_name }}</div>
        @if($item->color_name)
        <div class="item-name" style="font-size: 11px; color: #555;">Color: {{ $item->color_name }}</div>
        @endif
        <div class="row">
            <span class="item-name" style="font-size: 11px;">Rs.{{ number_format($item->unit_price) }} each</span>
            <span class="item-qty">x{{ $item->quantity }}</span>
            <span class="item-price">Rs.{{ number_format($item->line_total) }}</span>
        </div>
    </div>
    @endforeach

    <div class="divider"></div>

    {{-- Totals --}}
    <div style="margin-bottom: 2px;">
        <div class="row">
            <span>Subtotal</span>
            <span>Rs.{{ number_format($order->subtotal) }}</span>
        </div>
        @if($order->discount_amount > 0)
        <div class="row">
            <span>Discount</span>
            <span>- Rs.{{ number_format($order->discount_amount) }}</span>
        </div>
        @endif
        @if($order->exchange_value > 0)
        <div class="row" style="margin-top: 3px;">
            <span>Exchange Trade-in</span>
            <span>- Rs.{{ number_format($order->exchange_value) }}</span>
        </div>
        <div style="font-size: 10px; color: #555; padding-left: 4px;">{{ $order->exchange_item_name ?? 'Returned Item' }}</div>
        @endif
    </div>

    <div class="divider-solid"></div>

    @if($order->exchange_value > 0)
    <div class="row total-row bold" style="margin-bottom: 2px;">
        <span>PAYABLE AMOUNT</span>
        <span>Rs.{{ number_format($order->total) }}</span>
    </div>
    @else
    <div class="row total-row bold" style="margin-bottom: 2px;">
        <span>TOTAL</span>
        <span>Rs.{{ number_format($order->total) }}</span>
    </div>
    @endif

    <div class="divider"></div>

    {{-- Payment --}}
    <div style="margin-bottom: 2px;">
        @if($order->payment_method === 'partial')
        <div class="row">
            <span>Cash Paid</span>
            <span class="bold">Rs.{{ number_format($order->amount_paid) }}</span>
        </div>
        <div class="row">
            <span>Added to Khata</span>
            <span class="bold">Rs.{{ number_format($order->total - $order->amount_paid) }}</span>
        </div>
        @elseif($order->payment_method === 'split')
        <div class="row">
            <span>Cash</span>
            <span class="bold">Rs.{{ number_format($order->cash_amount) }}</span>
        </div>
        <div class="row">
            <span>Bank{{ $order->bankAccount ? ' ('.$order->bankAccount->label.')' : '' }}</span>
            <span class="bold">Rs.{{ number_format($order->bank_amount) }}</span>
        </div>
        @else
        <div class="row">
            <span>Payment</span>
            <span class="bold">
                @if($order->payment_method === 'cash') Cash
                @elseif($order->payment_method === 'bank_transfer')
                    Bank Transfer{{ $order->bankAccount ? ' — '.$order->bankAccount->label : '' }}
                @else Khata (Credit) @endif
            </span>
        </div>
        @endif
    </div>

    @if($order->payment_method === 'khata')
    <div class="divider"></div>
    <div class="center" style="font-size: 11px;">Full amount added to Khata account</div>
    @elseif($order->payment_method === 'partial')
    <div class="divider"></div>
    <div class="center" style="font-size: 11px;">
        Partial payment — Rs.{{ number_format($order->total - $order->amount_paid) }} on Khata
    </div>
    @elseif($order->payment_method === 'split')
    <div class="divider"></div>
    <div class="center" style="font-size: 11px;">Split: Cash + Bank Transfer</div>
    @endif

    {{-- Order Notes --}}
    @if($order->notes)
    <div class="divider"></div>
    <div style="margin-bottom: 4px;">
        <div class="bold" style="margin-bottom: 2px; font-size: 11px;">Note:</div>
        <div style="font-size: 11px;">{{ $order->notes }}</div>
    </div>
    @endif

    <div class="divider-solid"></div>

    {{-- Barcode --}}
    @if($order->order_number)
    <div class="center" style="margin: 6px 0 2px;">
        <svg id="receiptBarcode" style="max-width:100%;display:block;margin:0 auto;"></svg>
        <div style="font-size: 10px; margin-top: 2px; letter-spacing: 1px;">{{ $order->order_number }}</div>
    </div>
    <div class="divider"></div>
    @endif

    {{-- Footer --}}
    <div class="center" style="font-size: 11px; margin-top: 4px;">
        <div>Thank you for shopping with us!</div>
        <div style="margin-top: 2px;">Exchange within 7 days with receipt</div>
        <div style="margin-top: 2px;">{{ \App\Models\Setting::get('shop_phone') }}</div>
        <div style="margin-top: 4px; font-size: 10px;">Printed: {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>

    {{-- Print actions (hidden on print) --}}
    <div class="no-print" style="text-align: center; margin-top: 16px; padding: 8px;">
        <button onclick="window.print()" style="background: #1d4ed8; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; font-size: 13px; margin-right: 8px;">
            Print Receipt
        </button>
        <button onclick="window.close()" style="background: #6b7280; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; font-size: 13px;">
            Close
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        // Render barcode
        JsBarcode('#receiptBarcode', '{{ $order->order_number }}', {
            format:       'CODE128',
            lineColor:    '#000000',
            background:   '#ffffff',
            width:        2,
            height:       55,
            displayValue: false,
            margin:       0,
        });

        // Auto-print when opened in a popup
        if (window.opener) {
            window.addEventListener('load', () => window.print());
        }
    </script>
</body>
</html>

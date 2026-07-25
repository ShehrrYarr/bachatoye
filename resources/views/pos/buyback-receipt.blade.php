<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buyback Receipt — {{ $buyback->buyback_number }}</title>
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
        @media print {
            @page { size: 80mm auto; margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="center" style="margin-bottom: 6px;">
        <div class="bold" style="font-size: 16px;">{{ $settings['shop_name'] }}</div>
        <div>{{ $settings['shop_address'] }}</div>
        <div>{{ $settings['shop_phone'] }}</div>
    </div>

    <div class="divider-solid"></div>

    <div class="center bold" style="font-size: 14px; margin: 4px 0;">*** BUYBACK RECEIPT ***</div>

    <div class="divider-solid"></div>

    <div style="margin-bottom: 4px;">
        <div class="row"><span>Buyback #:</span><span class="bold">{{ $buyback->buyback_number }}</span></div>
        <div class="row"><span>Date:</span><span>{{ $buyback->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="row"><span>Seller:</span><span>{{ $buyback->seller_name }}</span></div>
        @if($buyback->seller_phone)
        <div class="row"><span>Phone:</span><span>{{ $buyback->seller_phone }}</span></div>
        @endif
        @if($buyback->sellerCustomer || $buyback->sellerVendor)
        <div class="row"><span>Matched:</span><span>{{ $buyback->sellerCustomer?->name ?? $buyback->sellerVendor?->name }} ({{ $buyback->sellerCustomer ? 'Customer' : 'Vendor' }})</span></div>
        @endif
    </div>

    <div class="divider"></div>

    <div class="row bold" style="margin-bottom: 2px;">
        <span style="flex:1">Item</span>
        <span style="width:80px;text-align:right">Amount</span>
    </div>
    <div class="divider"></div>

    @foreach($buyback->items as $item)
    <div class="row" style="margin-bottom: 3px;">
        <span style="flex:1;word-break:break-word">
            {{ $item->product_name }}
            @if($item->color_name) ({{ $item->color_name }}) @endif
            <br><span style="font-size:10px;">SN: {{ $item->serialNumber?->serial_number }}</span>
        </span>
        <span style="width:80px;text-align:right">Rs.{{ number_format($item->price_paid) }}</span>
    </div>
    @endforeach

    <div class="divider-solid"></div>

    <div class="row bold" style="font-size: 14px; margin-bottom: 2px;">
        <span>PAID</span>
        <span>Rs.{{ number_format($buyback->amount_total) }}</span>
    </div>

    <div class="divider"></div>

    <div class="row">
        <span>Payment Method:</span>
        <span class="bold">
            @if($buyback->payment_method === 'cash') Cash
            @else {{ $buyback->bankAccount?->label ?? 'Bank Transfer' }} @endif
        </span>
    </div>

    <div class="divider-solid"></div>

    <div class="center" style="font-size: 11px; margin-top: 4px;">
        @if($settings['footer'])
        <div>{{ $settings['footer'] }}</div>
        @endif
        <div style="margin-top: 2px;">{{ $settings['shop_phone'] }}</div>
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

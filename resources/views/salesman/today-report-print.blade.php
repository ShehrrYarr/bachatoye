<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Report — {{ $todayReport['salesman_name'] }} — {{ $todayReport['date'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1a1a1a; background: #fff; padding: 32px; }

        /* Print toolbar */
        .print-bar {
            position: fixed; top: 0; left: 0; right: 0; background: #1e293b;
            color: #fff; padding: 10px 24px; display: flex;
            align-items: center; justify-content: space-between; z-index: 100;
        }
        .print-bar span { font-size: 13px; opacity: 0.8; }
        .btn-print { background: #2563eb; color: #fff; border: none; padding: 8px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-print:hover { background: #1d4ed8; }
        .btn-back { background: transparent; color: #94a3b8; border: 1px solid #475569; padding: 7px 16px; border-radius: 6px; font-size: 12px; cursor: pointer; text-decoration: none; }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1a1a1a; padding-bottom: 14px; margin-bottom: 20px; }
        .store-name { font-size: 22px; font-weight: 700; }
        .store-meta { font-size: 11px; color: #555; margin-top: 3px; }
        .salesman-badge { display: inline-block; margin-top: 6px; background: #e0e7ff; color: #3730a3; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
        .doc-title { text-align: right; }
        .doc-title h2 { font-size: 18px; font-weight: 700; }
        .doc-title p { font-size: 11px; color: #666; margin-top: 2px; }

        /* Sections */
        .section { border-radius: 8px; padding: 14px 16px; margin-bottom: 12px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .section-title { font-size: 14px; font-weight: 700; }
        .section-total { font-size: 16px; font-weight: 800; }
        .breakdown { display: flex; gap: 24px; font-size: 12px; margin-top: 4px; }
        .breakdown span { display: flex; align-items: center; gap: 4px; }

        .pos    { background: #eff6ff; border: 1px solid #bfdbfe; }
        .pos .section-title, .pos .section-total { color: #1e40af; }
        .pos .breakdown { color: #3b82f6; }

        .khata  { background: #f0fdf4; border: 1px solid #bbf7d0; }
        .khata .section-title, .khata .section-total { color: #15803d; }
        .khata .breakdown { color: #16a34a; }

        .returns { background: #fff7ed; border: 1px solid #fed7aa; }
        .returns .section-title { color: #c2410c; }
        .returns .section-total { color: #c2410c; }
        .returns .breakdown { color: #ea580c; }

        .purchases { background: #faf5ff; border: 1px solid #e9d5ff; }
        .purchases .section-title { color: #6b21a8; }
        .purchases .section-total { color: #6b21a8; }
        .purchases .breakdown { color: #7c3aed; }

        /* Totals */
        .totals { border-top: 2px solid #1a1a1a; padding-top: 14px; margin-top: 4px; }
        .total-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; font-size: 13px; color: #374151; }
        .total-row.grand { font-size: 16px; font-weight: 800; color: #111; border-top: 1px solid #d1d5db; margin-top: 6px; padding-top: 10px; }
        .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; }
        .dot-green { background: #22c55e; }
        .dot-blue  { background: #3b82f6; }
        .dot-orange{ background: #f97316; }
        .dot-purple{ background: #a855f7; }

        .footer { display: flex; justify-content: space-between; border-top: 1px solid #ddd; padding-top: 10px; margin-top: 24px; font-size: 10px; color: #999; }

        @media print {
            .print-bar { display: none !important; }
            body { padding: 16px; }
        }
        @media screen {
            body { padding-top: 68px; }
        }
    </style>
</head>
<body>

    <div class="print-bar">
        <div style="display:flex;align-items:center;gap:12px;">
            <a href="{{ url()->previous() }}" class="btn-back">&#8592; Back</a>
            <span>Today's Report — {{ $todayReport['salesman_name'] }} — {{ $todayReport['date'] }}</span>
        </div>
        <button class="btn-print" onclick="window.print()">&#128438; Print / Save as PDF</button>
    </div>

    <div class="header">
        <div>
            <div class="store-name">{{ $todayReport['store_name'] }}</div>
            @if($todayReport['store_phone'])
            <div class="store-meta">{{ $todayReport['store_phone'] }}</div>
            @endif
            <div class="salesman-badge">&#128100; {{ $todayReport['salesman_name'] }}</div>
        </div>
        <div class="doc-title">
            <h2>TODAY'S REPORT</h2>
            <p>{{ $todayReport['date'] }}</p>
            <p>Generated: {{ now()->format('h:i A') }}</p>
        </div>
    </div>

    {{-- POS Sales --}}
    <div class="section pos">
        <div class="section-header">
            <span class="section-title">&#128179; My POS Sales</span>
            <span class="section-total">Rs. {{ number_format($todayReport['pos_total']) }}</span>
        </div>
        <div class="breakdown">
            <span><span class="dot dot-green"></span>Cash: Rs. {{ number_format($todayReport['pos_cash']) }}</span>
            <span><span class="dot dot-blue"></span>Bank Transfer: Rs. {{ number_format($todayReport['pos_bank']) }}</span>
        </div>
    </div>

    {{-- Khata Received --}}
    <div class="section khata">
        <div class="section-header">
            <span class="section-title">&#129297; Khata Received</span>
            <span class="section-total">Rs. {{ number_format($todayReport['khata_total']) }}</span>
        </div>
        <div class="breakdown">
            @if($todayReport['khata_cash'] > 0)
            <span><span class="dot dot-green"></span>Cash: Rs. {{ number_format($todayReport['khata_cash']) }}</span>
            @endif
            @if($todayReport['khata_bank'] > 0)
            <span><span class="dot dot-blue"></span>Bank Transfer: Rs. {{ number_format($todayReport['khata_bank']) }}</span>
            @endif
        </div>
    </div>

    {{-- Returns --}}
    <div class="section returns">
        <div class="section-header">
            <span class="section-title">&#8617; Returns Processed</span>
            <span class="section-total">– Rs. {{ number_format($todayReport['return_total']) }}</span>
        </div>
        @if($todayReport['return_total'] > 0)
        <div class="breakdown">
            <span><span class="dot dot-green"></span>Cash: Rs. {{ number_format($todayReport['return_cash']) }}</span>
            <span><span class="dot dot-blue"></span>Bank Transfer: Rs. {{ number_format($todayReport['return_bank']) }}</span>
        </div>
        @endif
    </div>

    {{-- Purchases (only if permission was granted / data exists) --}}
    @if($todayReport['purchases_total'] > 0)
    <div class="section purchases">
        <div class="section-header">
            <span class="section-title">&#128666; Purchases (Stock In)</span>
            <span class="section-total">Rs. {{ number_format($todayReport['purchases_total']) }}</span>
        </div>
        <div class="breakdown">
            @if($todayReport['purchases_cash'] > 0)
            <span><span class="dot dot-green"></span>Cash: Rs. {{ number_format($todayReport['purchases_cash']) }}</span>
            @endif
            @if($todayReport['purchases_bank'] > 0)
            <span><span class="dot dot-blue"></span>Bank: Rs. {{ number_format($todayReport['purchases_bank']) }}</span>
            @endif
            @if($todayReport['purchases_due'] > 0)
            <span style="color:#dc2626;">Due: Rs. {{ number_format($todayReport['purchases_due']) }}</span>
            @endif
        </div>
    </div>
    @endif

    {{-- Customer Payouts (manual khata cash-outs; shop-scoped reports only) --}}
    @if(($todayReport['payout_total'] ?? 0) > 0)
    <div class="section returns">
        <div class="section-header">
            <span class="section-title">&#128184; Customer Payouts (Out)</span>
            <span class="section-total">– Rs. {{ number_format($todayReport['payout_total']) }}</span>
        </div>
        <div class="breakdown">
            @if($todayReport['payout_cash'] > 0)
            <span><span class="dot dot-green"></span>Cash: Rs. {{ number_format($todayReport['payout_cash']) }}</span>
            @endif
            @if($todayReport['payout_bank'] > 0)
            <span><span class="dot dot-blue"></span>Bank: Rs. {{ number_format($todayReport['payout_bank']) }}</span>
            @endif
        </div>
    </div>
    @endif

    {{-- Grand Totals --}}
    <div class="totals">
        <div class="total-row">
            <span><span class="dot dot-green"></span>Total Cash In</span>
            <strong>Rs. {{ number_format($todayReport['total_cash']) }}</strong>
        </div>
        <div class="total-row">
            <span><span class="dot dot-blue"></span>Total Bank Transfer In</span>
            <strong>Rs. {{ number_format($todayReport['total_bank']) }}</strong>
        </div>
        @if($todayReport['return_total'] > 0)
        <div class="total-row">
            <span><span class="dot dot-orange"></span>Total Returns (Out)</span>
            <strong style="color:#c2410c;">– Rs. {{ number_format($todayReport['return_total']) }}</strong>
        </div>
        @endif
        @if($todayReport['purchases_paid'] > 0)
        <div class="total-row">
            <span><span class="dot dot-purple"></span>Purchases Paid (Out)</span>
            <strong style="color:#7c3aed;">– Rs. {{ number_format($todayReport['purchases_paid']) }}</strong>
        </div>
        @endif
        @if(($todayReport['payout_total'] ?? 0) > 0)
        <div class="total-row">
            <span><span class="dot dot-purple"></span>Customer Payouts (Out)</span>
            <strong style="color:#6b21a8;">– Rs. {{ number_format($todayReport['payout_total']) }}</strong>
        </div>
        @endif
        <div class="total-row grand">
            <span>Net Total</span>
            <span>Rs. {{ number_format($todayReport['grand_total']) }}</span>
        </div>
    </div>

    <div class="footer">
        <span>{{ $todayReport['store_name'] }} · {{ $todayReport['salesman_name'] }}</span>
        <span>{{ $todayReport['date'] }} · {{ now()->format('h:i A') }}</span>
    </div>

</body>
</html>

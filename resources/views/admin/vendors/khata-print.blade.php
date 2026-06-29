<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Khata — {{ $vendor->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #1a1a1a;
            background: #fff;
            padding: 32px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .store-name  { font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
        .store-meta  { font-size: 11px; color: #555; margin-top: 3px; line-height: 1.5; }
        .doc-title   { text-align: right; }
        .doc-title h2 { font-size: 16px; font-weight: 700; color: #111; }
        .doc-title p  { font-size: 11px; color: #666; margin-top: 2px; }

        .vendor-box {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            background: #f8f8f8;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 18px;
        }
        .vendor-box .label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #888; margin-bottom: 3px; }
        .vendor-box .value { font-size: 14px; font-weight: 600; }
        .balance-chip { padding: 6px 14px; border-radius: 999px; font-size: 13px; font-weight: 700; }
        .balance-owed  { background: #fee2e2; color: #b91c1c; }
        .balance-clear { background: #dcfce7; color: #15803d; }
        .balance-settled { background: #f3f4f6; color: #6b7280; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px; }
        thead tr { background: #1a1a1a; color: #fff; }
        thead th { padding: 8px 10px; text-align: left; font-weight: 600; font-size: 11px; letter-spacing: 0.3px; }
        thead th.right { text-align: right; }
        tbody tr:nth-child(even) { background: #f9f9f9; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #ececec; vertical-align: middle; }
        tbody td.right { text-align: right; }
        tbody td.mono  { font-family: monospace; font-size: 11px; }

        /* vendor: credit = we OWE (red), debit = we PAID (green) */
        .owed   { color: #dc2626; font-weight: 600; }
        .paid   { color: #16a34a; font-weight: 600; }
        .muted  { color: #ccc; }
        .bal-owed    { color: #dc2626; font-weight: 700; }
        .bal-settled { color: #1a1a1a; font-weight: 700; }

        .summary {
            display: flex;
            justify-content: flex-end;
            gap: 32px;
            padding: 12px 16px;
            background: #f3f4f6;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 32px;
        }
        .summary-item { text-align: right; }
        .summary-item .s-label { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 0.4px; }
        .summary-item .s-value { font-size: 14px; font-weight: 700; margin-top: 2px; }

        .footer {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #ddd;
            padding-top: 12px;
            font-size: 10px;
            color: #999;
        }

        .print-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: #1e293b;
            color: #fff;
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 100;
            gap: 12px;
        }
        .print-bar span { font-size: 13px; opacity: 0.8; }
        .btn-print {
            background: #2563eb; color: #fff; border: none;
            padding: 8px 20px; border-radius: 6px; font-size: 13px;
            font-weight: 600; cursor: pointer;
        }
        .btn-print:hover { background: #1d4ed8; }
        .btn-back {
            background: transparent; color: #94a3b8;
            border: 1px solid #475569; padding: 7px 16px;
            border-radius: 6px; font-size: 12px; cursor: pointer; text-decoration: none;
        }
        .btn-back:hover { color: #fff; border-color: #94a3b8; }

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
            @php $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman'; @endphp
            <a href="{{ route("{$rPrefix}.vendors.khata", $vendor) }}" class="btn-back">&#8592; Back</a>
            <span>Vendor Khata — {{ $vendor->name }}</span>
        </div>
        <button class="btn-print" onclick="window.print()">&#128438; Print / Save as PDF</button>
    </div>

    <div class="header">
        <div>
            <div class="store-name">{{ $storeName }}</div>
            <div class="store-meta">
                @if($storePhone) {{ $storePhone }}<br>@endif
                @if($storeAddress) {{ $storeAddress }}@endif
            </div>
        </div>
        <div class="doc-title">
            <h2>VENDOR KHATA</h2>
            @if($dateFrom || $dateTo)
                <p style="font-weight:600;color:#111;">
                    {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : 'Start' }}
                    &mdash;
                    {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : 'Today' }}
                </p>
            @endif
            <p>Generated: {{ now()->format('d M Y, h:i A') }}</p>
            <p>Total Entries: {{ $entries->count() }}</p>
        </div>
    </div>

    <div class="vendor-box">
        <div>
            <div class="label">Vendor</div>
            <div class="value">{{ $vendor->name }}</div>
            @if($vendor->company)
            <div style="font-size:12px;color:#555;margin-top:2px;">{{ $vendor->company }}</div>
            @endif
            @if($vendor->phone)
            <div style="font-size:12px;color:#555;margin-top:2px;">{{ $vendor->phone }}</div>
            @endif
        </div>
        @php
            $isFiltered  = $dateFrom || $dateTo;
            $displayBal  = $isFiltered
                ? ($entries->last()->balance_after ?? $vendor->balance)
                : $vendor->balance;
            $balLabel    = $isFiltered ? 'Balance at End of Period' : 'Current Balance';
        @endphp
        <div style="text-align:right;">
            <div class="label">{{ $balLabel }}</div>
            <div class="balance-chip {{ $displayBal > 0 ? 'balance-owed' : ($displayBal < 0 ? 'balance-clear' : 'balance-settled') }}" style="margin-top:4px;">
                Rs. {{ number_format(abs($displayBal)) }}
                {{ $displayBal > 0 ? 'WE OWE' : ($displayBal < 0 ? 'OVERPAID' : 'SETTLED') }}
            </div>
        </div>
    </div>

    @if($entries->isEmpty())
        <p style="text-align:center;color:#888;padding:40px 0;">No ledger entries found.</p>
    @else
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Description</th>
                <th>Reference</th>
                <th>Method / Bank</th>
                <th class="right">Credit (Owed +)</th>
                <th class="right">Debit (Paid –)</th>
                <th class="right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $i => $entry)
            <tr>
                <td style="color:#aaa;font-size:11px;">{{ $i + 1 }}</td>
                <td style="white-space:nowrap;font-size:11px;color:#555;">
                    {{ $entry->created_at->format('d M Y') }}<br>
                    <span style="color:#aaa;">{{ $entry->created_at->format('h:i A') }}</span>
                </td>
                <td>{{ $entry->description }}</td>
                <td class="mono">{{ $entry->reference ?? '—' }}</td>
                <td style="font-size:11px;color:#555;">
                    @if($entry->payment_method === 'cash')
                        Cash
                    @elseif($entry->payment_method === 'bank_transfer')
                        {{ $entry->bankAccount?->label ?? 'Bank' }}
                        @if($entry->bankAccount?->account_number)
                            <br><span style="color:#aaa;font-size:10px;">{{ $entry->bankAccount->account_number }}</span>
                        @endif
                    @else
                        <span style="color:#ccc;">—</span>
                    @endif
                </td>
                <td class="right {{ $entry->type === 'credit' ? 'owed' : 'muted' }}">
                    {{ $entry->type === 'credit' ? 'Rs. '.number_format($entry->amount) : '—' }}
                </td>
                <td class="right {{ $entry->type === 'debit' ? 'paid' : 'muted' }}">
                    {{ $entry->type === 'debit' ? 'Rs. '.number_format($entry->amount) : '—' }}
                </td>
                <td class="right {{ $entry->balance_after > 0 ? 'bal-owed' : 'bal-settled' }}">
                    Rs. {{ number_format($entry->balance_after) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $totalCredit  = $entries->where('type', 'credit')->sum('amount');
        $totalDebit   = $entries->where('type', 'debit')->sum('amount');
        $cashPaid     = $entries->where('type', 'debit')->where('payment_method', 'cash')->sum('amount');
        $bankBreakdown = $entries
            ->where('type', 'debit')
            ->where('payment_method', 'bank_transfer')
            ->filter(fn($e) => $e->bankAccount)
            ->groupBy('bank_account_id')
            ->map(fn($g) => ['account' => $g->first()->bankAccount, 'total' => $g->sum('amount')]);
    @endphp
    <div class="summary">
        <div class="summary-item">
            <div class="s-label">Total Credit (Owed)</div>
            <div class="s-value owed">Rs. {{ number_format($totalCredit) }}</div>
        </div>
        <div class="summary-item">
            <div class="s-label">Total Paid</div>
            <div class="s-value paid">Rs. {{ number_format($totalDebit) }}</div>
        </div>
        @if($cashPaid > 0)
        <div class="summary-item">
            <div class="s-label">Cash Paid</div>
            <div class="s-value paid">Rs. {{ number_format($cashPaid) }}</div>
        </div>
        @endif
        @foreach($bankBreakdown as $row)
        <div class="summary-item">
            <div class="s-label">{{ $row['account']->label }}</div>
            <div class="s-value" style="color:#1d4ed8;">Rs. {{ number_format($row['total']) }}</div>
        </div>
        @endforeach
        <div class="summary-item">
            <div class="s-label">{{ $isFiltered ? 'Balance at End of Period' : 'Net Balance' }}</div>
            <div class="s-value {{ $displayBal > 0 ? 'bal-owed' : 'bal-settled' }}">
                Rs. {{ number_format($displayBal) }}
            </div>
        </div>
    </div>
    @endif

    <div class="footer">
        <span>{{ $storeName }} &mdash; Vendor Khata</span>
        <span>{{ $vendor->name }} &mdash; {{ now()->format('d M Y') }}</span>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcode — {{ $product->name }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Arial', sans-serif;
            background: #f3f4f6;
            padding: 24px;
        }

        /* Controls — hidden when printing */
        .controls {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
        }
        .controls h1 {
            font-size: 16px;
            font-weight: 700;
            color: #111;
            flex: 1;
        }
        .controls label { font-size: 13px; color: #374151; font-weight: 500; }
        .controls input[type=number] {
            width: 72px;
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            text-align: center;
        }
        .btn-print {
            padding: 8px 22px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%);
        }
        .btn-print:hover { background: linear-gradient(135deg, #e11d48 0%, #9f1239 100%); }
        .btn-back {
            padding: 8px 18px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #fff;
            cursor: pointer;
            font-size: 14px;
            color: #374151;
        }

        /* Label grid */
        #label-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        /* Single label */
        .label-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            width: 200px;
            padding: 10px 10px 8px;
            text-align: center;
            page-break-inside: avoid;
        }
        .label-card .product-name {
            font-size: 10px;
            font-weight: 700;
            color: #111;
            margin-bottom: 4px;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .label-card svg {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto 2px;
        }
        .label-card .barcode-text {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            color: #111;
            margin-top: 2px;
        }
        .label-card .price {
            font-size: 12px;
            font-weight: 700;
            color: #be123c;
            margin-top: 3px;
        }

        /* Print styles */
        @media print {
            body { background: #fff; padding: 0; }
            .controls { display: none !important; }
            #label-grid {
                gap: 6px;
            }
            .label-card {
                border: 1px solid #ccc;
                border-radius: 4px;
            }
        }
    </style>
</head>
<body>

{{-- Controls bar --}}
<div class="controls">
    <h1>🏷 Print Barcode — {{ $product->name }}</h1>
    <label>Labels:
        <input type="number" id="qty-input" value="1" min="1" max="200">
    </label>
    <button class="btn-print" onclick="generateLabels(); window.print();">
        🖨 Print
    </button>
    <button class="btn-back" onclick="window.close()">✕ Close</button>
</div>

{{-- Label output --}}
<div id="label-grid"></div>

<script>
    const product = {
        name:    @json($product->name),
        barcode: @json($product->barcode ?? ''),
        price:   @json('Rs. ' . number_format($product->price)),
    };

    function generateLabels() {
        const qty  = parseInt(document.getElementById('qty-input').value) || 1;
        const grid = document.getElementById('label-grid');
        grid.innerHTML = '';

        for (let i = 0; i < qty; i++) {
            const card = document.createElement('div');
            card.className = 'label-card';

            card.innerHTML = `
                <div class="product-name">${product.name}</div>
                <svg class="barcode-svg-${i}"></svg>
                <div class="price">${product.price}</div>
            `;
            grid.appendChild(card);

            JsBarcode(`.barcode-svg-${i}`, product.barcode, {
                format:      'CODE128',
                width:       1.5,
                height:      40,
                displayValue: true,
                fontSize:    11,
                margin:      4,
                background:  '#ffffff',
                lineColor:   '#000000',
            });
        }
    }

    // Auto-generate on load + rerun when qty changes
    document.addEventListener('DOMContentLoaded', () => {
        generateLabels();
        document.getElementById('qty-input').addEventListener('input', generateLabels);
    });
</script>
</body>
</html>

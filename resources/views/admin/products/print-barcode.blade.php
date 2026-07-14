<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcode — {{ $product->name }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

    {{-- @page size injected by JS from the saved label template --}}
    <style id="page-size-style"></style>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
        }

        /* ── Controls bar (hidden on print) ───────────────────────────── */
        .controls {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            box-shadow: 0 1px 4px rgba(0,0,0,.07);
        }
        .controls h1 {
            font-size: 15px;
            font-weight: 700;
            color: #111;
            flex: 1;
            min-width: 160px;
        }
        .label-size-info {
            font-size: 12px;
            color: #6b7280;
            white-space: nowrap;
        }
        .field-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .field-group label {
            font-size: 12px;
            color: #374151;
            font-weight: 600;
            white-space: nowrap;
        }
        .field-group input[type=number] {
            width: 64px;
            padding: 5px 8px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 13px;
            text-align: center;
            color: #111;
        }
        .divider { width: 1px; height: 28px; background: #e5e7eb; }
        .btn-print {
            padding: 8px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            white-space: nowrap;
        }
        .btn-print:hover { background: linear-gradient(135deg, #4f46e5, #4338ca); }
        .btn-back, .btn-design {
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #fff;
            cursor: pointer;
            font-size: 13px;
            color: #374151;
            text-decoration: none;
            display: inline-block;
        }

        /* ── Label preview area ────────────────────────────────────────── */
        #preview-area {
            padding: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-start;
        }

        .label-card {
            background: #fff;
            border: 1.5px dashed #9ca3af;
            border-radius: 5px;
            position: relative;
            overflow: hidden;
            /* width / height set by JS from template */
        }
        .label-card .lbl-text { position: absolute; white-space: nowrap; color: #111; }
        .label-card .lbl-price { color: #be123c; }
        .label-card .lbl-barcode { position: absolute; }
        .label-card .lbl-barcode svg { display: block; }

        /* ── Print ─────────────────────────────────────────────────────── */
        @media print {
            body { background: #fff; }
            .controls { display: none !important; }

            #preview-area { padding: 0; gap: 0; display: block; }

            .label-card {
                border: none !important;
                border-radius: 0 !important;
                page-break-after: always;
                break-after: page;
            }
            .label-card:last-child {
                page-break-after: avoid;
                break-after: avoid;
            }
        }
    </style>
</head>
<body>

<div class="controls">
    <h1>🏷 {{ $product->name }}</h1>

    <span class="label-size-info" id="size-info"></span>

    <div class="divider"></div>

    <div class="field-group">
        <label>Copies</label>
        <input type="number" id="input-qty" min="1" max="200" step="1" value="1">
    </div>

    <div class="divider"></div>

    @if(auth()->user()->hasRole('admin'))
    <a class="btn-design" href="{{ route('admin.settings.barcode-canvas') }}">✏️ Edit Design</a>
    @endif
    <button class="btn-print" onclick="doPrint()">🖨 Print</button>
    <button class="btn-back" onclick="window.close()">✕ Close</button>
</div>

<div id="preview-area"></div>

<script>
const TPL = @json($template);

const VALUES = {
    shop_name:    @json(\App\Models\Setting::get('shop_name', 'MobileHub')),
    product_name: @json($product->name),
    price:        @json('Rs. ' . number_format($product->price)),
    sku:          @json($product->sku ?? ''),
};
const BARCODE = @json($product->barcode ?? '');

const DPI = 96; // CSS resolution: 1in = 96px
const COPIES_KEY = 'barcode_print_copies';

function esc(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function getQty() {
    return Math.max(1, parseInt(document.getElementById('input-qty').value) || 1);
}

function anchorTransform(align) {
    return align === 'center' ? 'translateX(-50%)'
         : align === 'right'  ? 'translateX(-100%)' : 'none';
}

function render() {
    const w = TPL.w, h = TPL.h;

    document.getElementById('page-size-style').textContent =
        `@page { size: ${w}in ${h}in; margin: 0; }`;
    document.getElementById('size-info').textContent = `Label: ${w}″ × ${h}″`;

    const qty  = getQty();
    const area = document.getElementById('preview-area');
    area.innerHTML = '';

    for (let i = 0; i < qty; i++) {
        const card = document.createElement('div');
        card.className    = 'label-card';
        card.style.width  = (w * DPI) + 'px';
        card.style.height = (h * DPI) + 'px';

        let html = '';
        for (const key of ['shop_name', 'product_name', 'price', 'sku']) {
            const e = TPL.elements[key];
            if (!e || !e.visible || !VALUES[key]) continue;
            html += `<div class="lbl-text ${key === 'price' ? 'lbl-price' : ''}"
                          style="left:${e.x}%; top:${e.y}%; transform:${anchorTransform(e.align)};
                                 font-size:${e.size}px; font-weight:${e.bold ? 700 : 400};">
                        ${esc(VALUES[key])}
                     </div>`;
        }

        const b = TPL.elements.barcode;
        if (b && b.visible && BARCODE) {
            // Position the wrapper div, not the svg — JsBarcode rewrites the
            // svg's attributes when rendering, which would wipe inline styles.
            html += `<div class="lbl-barcode"
                          style="left:${b.x}%; top:${b.y}%; transform:${anchorTransform(b.align)};">
                        <svg id="bc-svg-${i}"></svg>
                     </div>`;
        }

        card.innerHTML = html;
        area.appendChild(card);

        if (b && b.visible && BARCODE) {
            JsBarcode('#bc-svg-' + i, BARCODE, {
                format:       'CODE128',
                width:        b.barWidth,
                height:       b.barHeight,
                displayValue: b.showText,
                fontSize:     b.textSize,
                margin:       0,
                background:   'transparent',
                lineColor:    '#000000',
            });
        }
    }
}

function doPrint() {
    localStorage.setItem(COPIES_KEY, String(getQty()));
    window.print();
}

document.getElementById('input-qty').addEventListener('input', render);

// Restore last-used copies count
const savedQty = parseInt(localStorage.getItem(COPIES_KEY));
if (savedQty > 0) document.getElementById('input-qty').value = savedQty;

render();
</script>
</body>
</html>

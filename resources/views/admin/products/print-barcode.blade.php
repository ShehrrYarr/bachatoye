<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcode — {{ $product->name }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

    {{-- @page size is injected dynamically by JS so it matches the chosen label dimensions --}}
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
        .field-group input[type=number]:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99,102,241,.15);
        }
        .field-group .unit {
            font-size: 11px;
            color: #6b7280;
        }
        .divider {
            width: 1px;
            height: 28px;
            background: #e5e7eb;
        }
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
        .btn-back {
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #fff;
            cursor: pointer;
            font-size: 13px;
            color: #374151;
        }
        .saved-badge {
            font-size: 11px;
            color: #16a34a;
            font-weight: 500;
            opacity: 0;
            transition: opacity .4s;
        }
        .saved-badge.show { opacity: 1; }

        /* ── Label preview area ────────────────────────────────────────── */
        #preview-area {
            padding: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-start;
        }

        /* ── Single label card ─────────────────────────────────────────── */
        .label-card {
            background: #fff;
            border: 1.5px dashed #9ca3af;
            border-radius: 5px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 5px 6px;
            text-align: center;
            overflow: hidden;
            /* width / height set by JS */
        }
        .label-card .lbl-name {
            font-weight: 700;
            color: #111;
            line-height: 1.2;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-bottom: 2px;
            /* font-size set by JS */
        }
        .label-card svg {
            max-width: 100%;
            height: auto;
            display: block;
        }
        .label-card .lbl-price {
            font-weight: 700;
            color: #be123c;
            margin-top: 2px;
            /* font-size set by JS */
        }

        /* ── Print ─────────────────────────────────────────────────────── */
        @media print {
            body { background: #fff; }
            .controls { display: none !important; }

            #preview-area {
                padding: 0;
                gap: 0;
                display: block;
            }

            .label-card {
                /* width / height still set by JS — these are physical inches */
                border: none !important;
                border-radius: 0 !important;
                page-break-after: always;
                break-after: page;
                padding: 3px 4px;
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

    <div class="field-group">
        <label>Width</label>
        <input type="number" id="input-w" min="0.5" max="12" step="0.1" value="2.5">
        <span class="unit">in</span>
    </div>

    <div class="field-group">
        <label>Height</label>
        <input type="number" id="input-h" min="0.3" max="12" step="0.1" value="1.5">
        <span class="unit">in</span>
    </div>

    <div class="divider"></div>

    <div class="field-group">
        <label>Copies</label>
        <input type="number" id="input-qty" min="1" max="200" step="1" value="1">
    </div>

    <span class="saved-badge" id="saved-badge">✓ Saved</span>

    <div class="divider"></div>

    <button class="btn-print" onclick="doPrint()">🖨 Print</button>
    <button class="btn-back" onclick="window.close()">✕ Close</button>
</div>

<div id="preview-area"></div>

<script>
const product = {
    name:    @json($product->name),
    barcode: @json($product->barcode ?? ''),
    price:   @json('Rs. ' . number_format($product->price)),
};

const STORAGE_KEY = 'barcode_label_size_v1';
const DPI = 96; // CSS resolution: 1in = 96px

// ── Persist / restore settings ────────────────────────────────────────────
function loadSettings() {
    try {
        const s = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
        if (s.w) document.getElementById('input-w').value   = s.w;
        if (s.h) document.getElementById('input-h').value   = s.h;
        if (s.qty) document.getElementById('input-qty').value = s.qty;
    } catch(e) {}
}

function saveSettings() {
    const s = { w: getW(), h: getH(), qty: getQty() };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(s));
    const badge = document.getElementById('saved-badge');
    badge.classList.add('show');
    setTimeout(() => badge.classList.remove('show'), 1400);
}

function getW()   { return Math.max(0.5, parseFloat(document.getElementById('input-w').value)   || 2.5); }
function getH()   { return Math.max(0.3, parseFloat(document.getElementById('input-h').value)   || 1.5); }
function getQty() { return Math.max(1,   parseInt(document.getElementById('input-qty').value)   || 1);   }

// ── Update @page size (must be injected as a real <style> rule) ───────────
function updatePageRule(w, h) {
    document.getElementById('page-size-style').textContent =
        `@page { size: ${w}in ${h}in; margin: 0; }`;
}

// ── Generate / refresh labels ─────────────────────────────────────────────
function render() {
    const w   = getW();
    const h   = getH();
    const qty = getQty();

    updatePageRule(w, h);

    const wPx = w * DPI;
    const hPx = h * DPI;

    // Proportional sizes
    const nameFontPx  = Math.round(Math.min(hPx * 0.10, wPx * 0.055, 14));
    const priceFontPx = Math.round(Math.min(hPx * 0.11, wPx * 0.060, 15));
    const bcHeight    = Math.round(hPx * 0.44);
    const barWidth    = Math.max(1, Math.min(2.5, Math.round(wPx / 100)));

    const area = document.getElementById('preview-area');
    area.innerHTML = '';

    for (let i = 0; i < qty; i++) {
        const card = document.createElement('div');
        card.className = 'label-card';
        card.style.width  = wPx + 'px';
        card.style.height = hPx + 'px';

        const svgId = 'bc-svg-' + i;
        card.innerHTML =
            `<div class="lbl-name" style="font-size:${nameFontPx}px">${product.name}</div>` +
            `<svg id="${svgId}"></svg>` +
            `<div class="lbl-price" style="font-size:${priceFontPx}px">${product.price}</div>`;

        area.appendChild(card);

        if (product.barcode) {
            JsBarcode('#' + svgId, product.barcode, {
                format:       'CODE128',
                width:        barWidth,
                height:       bcHeight,
                displayValue: true,
                fontSize:     Math.round(nameFontPx * 0.9),
                margin:       2,
                background:   '#ffffff',
                lineColor:    '#000000',
            });
        }
    }
}

function doPrint() {
    saveSettings();
    window.print();
}

// ── Wire up inputs ────────────────────────────────────────────────────────
['input-w', 'input-h', 'input-qty'].forEach(id => {
    document.getElementById(id).addEventListener('input', render);
    document.getElementById(id).addEventListener('change', saveSettings);
});

// ── Init ──────────────────────────────────────────────────────────────────
loadSettings();
render();
</script>
</body>
</html>

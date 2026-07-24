@php
    use App\Support\ColorPalette;
    use App\Support\EcomTheme;

    $tSlug   = $ecomTheme       ?? EcomTheme::CLASSIC;
    $tMeta   = $ecomThemeMeta   ?? EcomTheme::meta($tSlug);
    $tColors = $ecomThemeColors ?? EcomTheme::colors($tSlug);
    $tFont   = $ecomThemeFont   ?? EcomTheme::fontUrl($tSlug);

    $tDark    = (bool) ($tMeta['dark'] ?? false);
    $tTokens  = $tMeta['tokens'] ?? [];
    $tPrimary = $tColors['primary'];
    $tSecond  = $tColors['secondary'];

    $tPalette = $tDark
        ? ColorPalette::generateForDark($tPrimary)
        : ColorPalette::generate($tPrimary);

    $tGradient = ($tColors['gradient'] ?? true)
        ? "linear-gradient(135deg, {$tPrimary} 0%, {$tSecond} 100%)"
        : $tPrimary;

    $tGradientHover = ($tColors['gradient'] ?? true)
        ? "linear-gradient(135deg, {$tPalette[500]} 0%, {$tPalette[800]} 100%)"
        : $tPalette[700];

    // rgb triplet for translucent accent washes
    $tRgb = implode(' ', sscanf($tPrimary, '#%02x%02x%02x'));
@endphp
@if($tFont)
<link href="{{ $tFont }}" rel="stylesheet">
@endif
<style>
    :root {
        @foreach($tPalette as $shade => $hex)
        --color-primary-{{ $shade }}: {{ $hex }};
        @endforeach

        --app-primary:        {{ $tPrimary }};
        --app-secondary:      {{ $tSecond }};
        --app-gradient:       {{ $tGradient }};
        --app-gradient-hover: {{ $tGradientHover }};
        --t-accent:           {{ $tPrimary }};
        --t-accent-rgb:       {{ $tRgb }};

        --t-bg:        {{ $tTokens['bg']        ?? '#f9fafb' }};
        --t-surface:   {{ $tTokens['surface']   ?? '#ffffff' }};
        --t-surface-2: {{ $tTokens['surface-2'] ?? '#f3f4f6' }};
        --t-border:    {{ $tTokens['border']    ?? '#e5e7eb' }};
        --t-text:      {{ $tTokens['text']      ?? '#111827' }};
        --t-muted:     {{ $tTokens['muted']     ?? '#6b7280' }};
        --t-radius:    {{ $tTokens['radius']    ?? '12px' }};
        --t-radius-sm: {{ $tTokens['radius-sm'] ?? '8px' }};
        --t-shadow:    {{ $tTokens['shadow']    ?? '0 1px 3px rgba(0,0,0,.08)' }};
        --t-shadow-lg: {{ $tTokens['shadow-lg'] ?? '0 12px 32px -8px rgba(0,0,0,.18)' }};

        --t-font-body:    {{ $tMeta['body_font']    ?? "'Inter'" }}, ui-sans-serif, system-ui, sans-serif;
        --t-font-heading: {{ $tMeta['heading_font'] ?? "'Inter'" }}, ui-serif, Georgia, serif;
    }

    /* ── Base ─────────────────────────────────────────────────────────── */
    body {
        background: var(--t-bg);
        color: var(--t-text);
        font-family: var(--t-font-body);
    }
    h1, h2, h3, h4, .t-heading { font-family: var(--t-font-heading); }

    ::selection { background: rgb(var(--t-accent-rgb) / .22); }

    /* ── Shared components ────────────────────────────────────────────── */
    .t-container { max-width: 80rem; margin-inline: auto; padding-inline: 1rem; }

    .t-surface {
        background: var(--t-surface);
        border: 1px solid var(--t-border);
        border-radius: var(--t-radius);
    }
    .t-card {
        background: var(--t-surface);
        border: 1px solid var(--t-border);
        border-radius: var(--t-radius);
        box-shadow: var(--t-shadow);
        transition: box-shadow .25s ease, transform .25s ease, border-color .25s ease;
    }
    .t-card:hover { box-shadow: var(--t-shadow-lg); }

    .t-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
        border-radius: var(--t-radius-sm);
        font-weight: 600; line-height: 1;
        padding: .75rem 1.25rem;
        transition: all .2s ease;
        cursor: pointer;
    }
    .t-btn-primary { background: var(--app-gradient); color: #fff; border: 0; }
    .t-btn-primary:hover:not(:disabled) { background: var(--app-gradient-hover); }
    .t-btn-primary:disabled { opacity: .45; cursor: not-allowed; }
    .t-btn-outline {
        background: transparent; color: var(--t-text);
        border: 1px solid var(--t-border);
    }
    .t-btn-outline:hover { border-color: var(--t-accent); color: var(--t-accent); }
    .t-btn-ghost { background: var(--t-surface-2); color: var(--t-text); border: 0; }
    .t-btn-ghost:hover { background: rgb(var(--t-accent-rgb) / .12); color: var(--t-accent); }

    .t-input {
        width: 100%;
        background: var(--t-surface);
        color: var(--t-text);
        border: 1px solid var(--t-border);
        border-radius: var(--t-radius-sm);
        padding: .625rem .875rem;
        font-size: .875rem;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .t-input::placeholder { color: var(--t-muted); }
    .t-input:focus {
        outline: none;
        border-color: var(--t-accent);
        box-shadow: 0 0 0 3px rgb(var(--t-accent-rgb) / .16);
    }

    .t-muted { color: var(--t-muted); }
    .t-accent { color: var(--t-accent); }
    .t-divider { border-color: var(--t-border); }

    .t-chip {
        display: inline-flex; align-items: center; gap: .375rem;
        padding: .3rem .7rem;
        border-radius: 999px;
        font-size: .75rem; font-weight: 600;
        background: var(--t-surface-2);
        color: var(--t-muted);
        border: 1px solid var(--t-border);
    }
    .t-chip-accent {
        background: rgb(var(--t-accent-rgb) / .12);
        color: var(--t-accent);
        border-color: rgb(var(--t-accent-rgb) / .25);
    }

    .t-price { color: var(--t-accent); font-weight: 800; }
    .t-strike { color: var(--t-muted); text-decoration: line-through; font-weight: 500; }

    .t-skeleton {
        background: linear-gradient(90deg, var(--t-surface-2) 25%, var(--t-border) 50%, var(--t-surface-2) 75%);
        background-size: 200% 100%;
        animation: tShimmer 1.4s infinite;
    }
    @keyframes tShimmer { 0% { background-position: 200% 0 } 100% { background-position: -200% 0 } }

    /* ── Keep un-themed shared views on-brand ─────────────────────────── */
    .btn-primary { background: var(--app-gradient) !important; }
    .btn-primary:hover { background: var(--app-gradient-hover) !important; }
    .card { background: var(--t-surface); border-color: var(--t-border); border-radius: var(--t-radius); }
    .ecom-product-card {
        background: var(--t-surface);
        border-color: var(--t-border);
        border-radius: var(--t-radius);
    }
    .form-input, .form-select, .form-textarea {
        background: var(--t-surface);
        color: var(--t-text);
        border-color: var(--t-border);
        border-radius: var(--t-radius-sm);
    }

@if($tDark)
    /* ── Dark safety net ──────────────────────────────────────────────────
       Any shared markup that still uses light utility classes is remapped so
       nothing can render unreadable on a near-black page. Themed templates
       opt out with the .t-keep-light escape hatch.               */
    html[data-theme="dark"] .bg-white:not(.t-keep-light) { background-color: var(--t-surface) !important; }
    html[data-theme="dark"] .bg-gray-50:not(.t-keep-light),
    html[data-theme="dark"] .bg-gray-100:not(.t-keep-light) { background-color: var(--t-surface-2) !important; }
    html[data-theme="dark"] .text-gray-900:not(.t-keep-light),
    html[data-theme="dark"] .text-gray-800:not(.t-keep-light),
    html[data-theme="dark"] .text-gray-700:not(.t-keep-light) { color: var(--t-text) !important; }
    html[data-theme="dark"] .text-gray-600:not(.t-keep-light),
    html[data-theme="dark"] .text-gray-500:not(.t-keep-light),
    html[data-theme="dark"] .text-gray-400:not(.t-keep-light) { color: var(--t-muted) !important; }
    html[data-theme="dark"] .border-gray-100:not(.t-keep-light),
    html[data-theme="dark"] .border-gray-200:not(.t-keep-light),
    html[data-theme="dark"] .border-gray-300:not(.t-keep-light) { border-color: var(--t-border) !important; }
    html[data-theme="dark"] input,
    html[data-theme="dark"] select,
    html[data-theme="dark"] textarea { color-scheme: dark; }
@endif
</style>

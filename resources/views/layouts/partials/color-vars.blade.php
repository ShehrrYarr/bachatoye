@php
    // ── Color helper functions (guarded so re-includes don't break) ──────────
    if (!function_exists('_hexToHsl')) {
        function _hexToHsl(string $hex): array {
            $hex = ltrim($hex, '#');
            $r   = hexdec(substr($hex, 0, 2)) / 255;
            $g   = hexdec(substr($hex, 2, 2)) / 255;
            $b   = hexdec(substr($hex, 4, 2)) / 255;
            $max = max($r, $g, $b);
            $min = min($r, $g, $b);
            $l   = ($max + $min) / 2;
            if ($max === $min) {
                $h = $s = 0.0;
            } else {
                $d = $max - $min;
                $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
                if ($max === $r)      $h = fmod(($g - $b) / $d, 6.0);
                elseif ($max === $g)  $h = ($b - $r) / $d + 2;
                else                  $h = ($r - $g) / $d + 4;
                $h /= 6;
                if ($h < 0) $h += 1;
            }
            return [$h * 360, $s * 100, $l * 100];
        }
    }

    if (!function_exists('_hslToHex')) {
        function _hslToHex(float $h, float $s, float $l): string {
            $h /= 360; $s /= 100; $l /= 100;
            if ($s == 0) {
                $r = $g = $b = $l;
            } else {
                $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
                $p = 2 * $l - $q;
                $fn = function (float $p, float $q, float $t) use (&$fn): float {
                    if ($t < 0) $t += 1; if ($t > 1) $t -= 1;
                    if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
                    if ($t < 1/2) return $q;
                    if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
                    return $p;
                };
                $r = $fn($p, $q, $h + 1/3);
                $g = $fn($p, $q, $h);
                $b = $fn($p, $q, $h - 1/3);
            }
            return '#' . sprintf('%02x%02x%02x',
                (int) round(max(0, min(255, $r * 255))),
                (int) round(max(0, min(255, $g * 255))),
                (int) round(max(0, min(255, $b * 255)))
            );
        }
    }

    if (!function_exists('_generatePalette')) {
        function _generatePalette(string $hex): array {
            [$h, $s, $l] = _hexToHsl($hex);
            // shade 600 = the chosen color; lighter/darker shades derived from it
            $map = [
                50  => [97,               $s * 0.12],
                100 => [94,               $s * 0.22],
                200 => [87,               $s * 0.40],
                300 => [77,               $s * 0.60],
                400 => [66,               $s * 0.80],
                500 => [55,               $s * 0.94],
                600 => [$l,               $s       ],
                700 => [max(5, $l - 9),   $s       ],
                800 => [max(5, $l - 17),  $s * 0.96],
                900 => [max(5, $l - 23),  $s * 0.88],
            ];
            $palette = [];
            foreach ($map as $shade => [$newL, $newS]) {
                $palette[$shade] = _hslToHex($h, min(100, max(0, $newS)), $newL);
            }
            $palette[600] = $hex; // exact match
            return $palette;
        }
    }

    // ── Read settings ────────────────────────────────────────────────────────
    $appPrimary   = \App\Models\Setting::get('primary_color',   '#e11d48');
    $appSecondary = \App\Models\Setting::get('secondary_color', '#be123c');
    $appGradient  = \App\Models\Setting::get('use_gradient',    '1') === '1';

    // Validate — fall back to defaults if tampered
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $appPrimary))   $appPrimary   = '#e11d48';
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $appSecondary)) $appSecondary = '#be123c';

    $palette    = _generatePalette($appPrimary);
    $gradCss    = $appGradient
        ? "linear-gradient(135deg, {$appPrimary} 0%, {$appSecondary} 100%)"
        : $appPrimary;
    $gradHover  = $appGradient
        ? "linear-gradient(135deg, {$palette[500]} 0%, {$palette[800]} 100%)"
        : $palette[700];
@endphp
<style>
    :root {
        --color-primary-50:  {{ $palette[50]  }};
        --color-primary-100: {{ $palette[100] }};
        --color-primary-200: {{ $palette[200] }};
        --color-primary-300: {{ $palette[300] }};
        --color-primary-400: {{ $palette[400] }};
        --color-primary-500: {{ $palette[500] }};
        --color-primary-600: {{ $palette[600] }};
        --color-primary-700: {{ $palette[700] }};
        --color-primary-800: {{ $palette[800] }};
        --color-primary-900: {{ $palette[900] }};
        --app-primary:       {{ $appPrimary   }};
        --app-secondary:     {{ $appSecondary }};
        --app-gradient:      {{ $gradCss      }};
        --app-gradient-hover:{{ $gradHover    }};
    }
    .btn-primary {
        background: var(--app-gradient) !important;
    }
    .btn-primary:hover {
        background: var(--app-gradient-hover) !important;
    }
    .sidebar-link.active {
        background: var(--app-gradient) !important;
    }
</style>

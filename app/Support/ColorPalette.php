<?php

namespace App\Support;

/**
 * Builds a 50–900 Tailwind-style shade ramp from a single hex colour.
 *
 * Mirrors the maths already used by layouts/partials/color-vars.blade.php so
 * themed and non-themed pages generate identical shades for the same input.
 */
class ColorPalette
{
    /** @return array{0:float,1:float,2:float} [h, s, l] */
    public static function hexToHsl(string $hex): array
    {
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

            if ($max === $r)     $h = fmod(($g - $b) / $d, 6.0);
            elseif ($max === $g) $h = ($b - $r) / $d + 2;
            else                 $h = ($r - $g) / $d + 4;

            $h /= 6;
            if ($h < 0) $h += 1;
        }

        return [$h * 360, $s * 100, $l * 100];
    }

    public static function hslToHex(float $h, float $s, float $l): string
    {
        $h /= 360; $s /= 100; $l /= 100;

        if ($s == 0) {
            $r = $g = $b = $l;
        } else {
            $q  = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p  = 2 * $l - $q;
            $fn = function (float $p, float $q, float $t): float {
                if ($t < 0) $t += 1;
                if ($t > 1) $t -= 1;
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

    /** @return array<int,string> shades keyed 50…900, with 600 the exact input */
    public static function generate(string $hex): array
    {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
            $hex = '#e11d48';
        }

        [$h, $s, $l] = self::hexToHsl($hex);

        $map = [
            50  => [97,              $s * 0.12],
            100 => [94,              $s * 0.22],
            200 => [87,              $s * 0.40],
            300 => [77,              $s * 0.60],
            400 => [66,              $s * 0.80],
            500 => [55,              $s * 0.94],
            600 => [$l,              $s       ],
            700 => [max(5, $l - 9),  $s       ],
            800 => [max(5, $l - 17), $s * 0.96],
            900 => [max(5, $l - 23), $s * 0.88],
        ];

        $palette = [];
        foreach ($map as $shade => [$newL, $newS]) {
            $palette[$shade] = self::hslToHex($h, min(100, max(0, $newS)), $newL);
        }
        $palette[600] = $hex;

        return $palette;
    }

    /**
     * Dark themes need the ramp inverted: the light shades must be the dark
     * ones so `primary-50` backgrounds don't blow out a near-black page.
     *
     * @return array<int,string>
     */
    public static function generateForDark(string $hex): array
    {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
            $hex = '#7c3aed';
        }

        [$h, $s, $l] = self::hexToHsl($hex);

        // Semantics are inverted: the "light" shades become dark tinted
        // surfaces and the "dark" shades become bright text, so utilities like
        // `bg-primary-50` and `text-primary-700` still read correctly.
        $map = [
            50  => [13,                    $s * 0.35],
            100 => [17,                    $s * 0.45],
            200 => [23,                    $s * 0.55],
            300 => [32,                    $s * 0.70],
            400 => [45,                    $s * 0.85],
            500 => [max(5, $l - 8),        $s * 0.95],
            600 => [$l,                    $s       ],
            700 => [min(88, $l + 10),      $s * 0.92],
            800 => [min(93, $l + 18),      $s * 0.80],
            900 => [min(97, $l + 25),      $s * 0.60],
        ];

        $palette = [];
        foreach ($map as $shade => [$newL, $newS]) {
            $palette[$shade] = self::hslToHex($h, min(100, max(0, $newS)), $newL);
        }
        $palette[600] = $hex;

        return $palette;
    }
}

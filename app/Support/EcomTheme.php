<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Storefront theme registry.
 *
 * A theme is a folder under resources/views/themes/{slug} whose templates are
 * prepended to the view finder for storefront requests only — any view a theme
 * does not override falls back to the original one, so nothing can break by
 * omission. The admin panel and POS never see a theme.
 *
 * "classic" is not a real folder: it means the storefront the shop had before
 * themes existed, and it is what the Reset button restores.
 */
class EcomTheme
{
    /** Setting key holding the applied theme slug. */
    public const SETTING_KEY = 'ecom_theme';

    /** Session key holding an admin-only preview slug. */
    public const PREVIEW_KEY = 'ecom_theme_preview';

    /** Query parameter that starts a preview. */
    public const PREVIEW_PARAM = '_view';

    /** The original storefront — no template overrides, no theme palette. */
    public const CLASSIC = 'classic';

    /**
     * The four selectable views.
     *
     * primary/secondary are the designed defaults; the admin may override them
     * per theme from settings without losing the theme's other design choices.
     * Fonts are Bunny Fonts families (the CDN the storefront already uses).
     */
    public static function all(): array
    {
        return [
            'marketplace' => [
                'name'     => 'Modern Marketplace',
                'tagline'  => 'Dense and commercial — category strip, deal countdowns, tight product grid.',
                'like'     => 'priceoye.com',
                'primary'  => '#2563eb',
                'secondary'=> '#1d4ed8',
                'gradient' => true,
                'dark'     => false,
                'fonts'    => ['plus-jakarta-sans:400,500,600,700,800'],
                'body_font'    => "'Plus Jakarta Sans'",
                'heading_font' => "'Plus Jakarta Sans'",
                'swatch'   => ['#2563eb', '#eff6ff', '#0f172a'],
                'tokens'   => [
                    'bg'        => '#f1f5f9',
                    'surface'   => '#ffffff',
                    'surface-2' => '#f8fafc',
                    'border'    => '#e2e8f0',
                    'text'      => '#0f172a',
                    'muted'     => '#64748b',
                    'radius'    => '12px',
                    'radius-sm' => '8px',
                    'shadow'    => '0 1px 3px rgba(15,23,42,.08)',
                    'shadow-lg' => '0 12px 32px -8px rgba(15,23,42,.18)',
                ],
            ],
            'boutique' => [
                'name'     => 'Minimal Boutique',
                'tagline'  => 'Airy and premium — big hero, serif headings, generous whitespace.',
                'like'     => 'Shopify DTC stores',
                'primary'  => '#1c1917',
                'secondary'=> '#57534e',
                'gradient' => false,
                'dark'     => false,
                'fonts'    => ['fraunces:400,500,600,700', 'inter:400,500,600,700'],
                'body_font'    => "'Inter'",
                'heading_font' => "'Fraunces'",
                'swatch'   => ['#1c1917', '#faf9f7', '#a8a29e'],
                'tokens'   => [
                    'bg'        => '#faf9f7',
                    'surface'   => '#ffffff',
                    'surface-2' => '#f5f4f1',
                    'border'    => '#e7e5e4',
                    'text'      => '#1c1917',
                    'muted'     => '#78716c',
                    'radius'    => '4px',
                    'radius-sm' => '2px',
                    'shadow'    => 'none',
                    'shadow-lg' => '0 20px 50px -20px rgba(28,25,23,.22)',
                ],
            ],
            'dark' => [
                'name'     => 'Dark Premium',
                'tagline'  => 'Near-black surfaces with a glowing accent — built for phones and gadgets.',
                'like'     => 'high-end tech stores',
                'primary'  => '#7c3aed',
                'secondary'=> '#4c1d95',
                'gradient' => true,
                'dark'     => true,
                'fonts'    => ['space-grotesk:400,500,600,700'],
                'body_font'    => "'Space Grotesk'",
                'heading_font' => "'Space Grotesk'",
                'swatch'   => ['#7c3aed', '#0b0b12', '#e5e7eb'],
                'tokens'   => [
                    'bg'        => '#08080d',
                    'surface'   => '#12121b',
                    'surface-2' => '#1a1a26',
                    'border'    => '#262636',
                    'text'      => '#e8e8f0',
                    'muted'     => '#9494a8',
                    'radius'    => '16px',
                    'radius-sm' => '10px',
                    'shadow'    => '0 1px 2px rgba(0,0,0,.6)',
                    'shadow-lg' => '0 20px 48px -12px rgba(0,0,0,.8)',
                ],
            ],
            'bold' => [
                'name'     => 'Bold Deals',
                'tagline'  => 'High energy — gradient hero, flash-sale countdown, chunky discount ribbons.',
                'like'     => 'offer-driven stores',
                'primary'  => '#f97316',
                'secondary'=> '#dc2626',
                'gradient' => true,
                'dark'     => false,
                'fonts'    => ['outfit:400,500,600,700,800,900'],
                'body_font'    => "'Outfit'",
                'heading_font' => "'Outfit'",
                'swatch'   => ['#f97316', '#fff7ed', '#dc2626'],
                'tokens'   => [
                    'bg'        => '#fffbf5',
                    'surface'   => '#ffffff',
                    'surface-2' => '#fff7ed',
                    'border'    => '#fed7aa',
                    'text'      => '#1c1917',
                    'muted'     => '#78716c',
                    'radius'    => '20px',
                    'radius-sm' => '12px',
                    'shadow'    => '0 2px 8px rgba(249,115,22,.10)',
                    'shadow-lg' => '0 18px 40px -10px rgba(249,115,22,.28)',
                ],
            ],
        ];
    }

    public static function exists(string $slug): bool
    {
        return array_key_exists($slug, self::all());
    }

    /** Theme definition, or null for classic/unknown slugs. */
    public static function meta(?string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    /** The slug saved in settings — what customers currently see. */
    public static function applied(): string
    {
        $slug = Setting::get(self::SETTING_KEY);
        return self::exists((string) $slug) ? (string) $slug : self::CLASSIC;
    }

    /** The slug to render for this request: an admin preview wins over the applied one. */
    public static function active(): string
    {
        $preview = self::previewing();
        return $preview ?: self::applied();
    }

    /**
     * The previewed slug, or null when not previewing. A preview only ever
     * renders for a signed-in admin, so a stale session on a customer's browser
     * can never show them an unpublished view.
     */
    public static function previewing(): ?string
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return null;
        }

        $slug = Session::get(self::PREVIEW_KEY);

        if ($slug === self::CLASSIC) {
            return self::CLASSIC;
        }

        return self::exists((string) $slug) ? (string) $slug : null;
    }

    public static function startPreview(string $slug): void
    {
        if (self::exists($slug) || $slug === self::CLASSIC) {
            Session::put(self::PREVIEW_KEY, $slug);
        }
    }

    public static function stopPreview(): void
    {
        Session::forget(self::PREVIEW_KEY);
    }

    /** Make a theme live for every customer. */
    public static function apply(string $slug): void
    {
        if (!self::exists($slug)) {
            return;
        }

        Setting::set(self::SETTING_KEY, $slug);
        self::stopPreview();
    }

    /** Restore the original storefront. */
    public static function reset(): void
    {
        Setting::set(self::SETTING_KEY, null);
        self::stopPreview();
    }

    /** Per-theme setting key for an admin colour override. */
    public static function colorKey(string $slug, string $which): string
    {
        return "ecom_theme_{$slug}_{$which}";
    }

    /**
     * Effective storefront colours for a theme: the designed defaults unless
     * the admin has overridden them. Classic keeps using the global shop
     * colours, exactly as the storefront does today.
     */
    public static function colors(string $slug): array
    {
        $meta = self::meta($slug);

        if (!$meta) {
            return [
                'primary'   => Setting::get('primary_color',   '#e11d48'),
                'secondary' => Setting::get('secondary_color', '#be123c'),
                'gradient'  => Setting::get('use_gradient', '1') === '1',
            ];
        }

        $primary   = Setting::get(self::colorKey($slug, 'primary'))   ?: $meta['primary'];
        $secondary = Setting::get(self::colorKey($slug, 'secondary')) ?: $meta['secondary'];

        // Guard against a tampered or half-saved value.
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string) $primary))   $primary   = $meta['primary'];
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string) $secondary)) $secondary = $meta['secondary'];

        return [
            'primary'   => $primary,
            'secondary' => $secondary,
            'gradient'  => (bool) $meta['gradient'],
        ];
    }

    /** True when the admin has changed a theme's colours away from its design. */
    public static function colorsOverridden(string $slug): bool
    {
        return (bool) Setting::get(self::colorKey($slug, 'primary'))
            || (bool) Setting::get(self::colorKey($slug, 'secondary'));
    }

    public static function resetColors(string $slug): void
    {
        Setting::set(self::colorKey($slug, 'primary'), null);
        Setting::set(self::colorKey($slug, 'secondary'), null);
    }

    /** Bunny Fonts stylesheet URL for a theme, or null for classic. */
    public static function fontUrl(?string $slug): ?string
    {
        $meta = self::meta($slug);

        if (!$meta || empty($meta['fonts'])) {
            return null;
        }

        return 'https://fonts.bunny.net/css?family=' . implode('|', $meta['fonts']) . '&display=swap';
    }
}

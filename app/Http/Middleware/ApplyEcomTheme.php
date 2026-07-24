<?php

namespace App\Http\Middleware;

use App\Support\EcomTheme;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the storefront theme for this request and points the view finder at
 * its templates. Runs on customer-facing routes only — admin, POS, salesman and
 * shop panels are never touched.
 */
class ApplyEcomTheme
{
    /** Route-name prefixes that render the customer-facing storefront. */
    private const STOREFRONT_ROUTES = [
        'home',
        'products.',
        'category.',
        'brand.',
        'deals.',
        'cart.',
        'checkout.',
        'order.track',
        'account.',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isStorefront($request)) {
            return $next($request);
        }

        if ($redirect = $this->handlePreviewToggle($request)) {
            return $redirect;
        }

        $slug = EcomTheme::active();

        if (EcomTheme::exists($slug)) {
            // Order matters: the shared themed layer goes on first so the
            // theme's own templates, prepended after it, win. Anything neither
            // provides still falls back to the original storefront view.
            View::getFinder()->prependLocation(resource_path('views/themes/_shared'));
            View::getFinder()->prependLocation(resource_path("views/themes/{$slug}"));

            // The finder memoises name → path. Anything resolved before these
            // locations were added would keep pointing at the default template,
            // so drop the cache and let every name resolve against the theme.
            View::getFinder()->flush();
        }

        View::share([
            'ecomTheme'        => $slug,
            'ecomThemeMeta'    => EcomTheme::meta($slug),
            'ecomThemeColors'  => EcomTheme::colors($slug),
            'ecomThemeFont'    => EcomTheme::fontUrl($slug),
            'ecomThemePreview' => EcomTheme::previewing(),
        ]);

        return $next($request);
    }

    private function isStorefront(Request $request): bool
    {
        $name = $request->route()?->getName();

        if (!$name) {
            return false;
        }

        foreach (self::STOREFRONT_ROUTES as $prefix) {
            if ($name === $prefix || str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * `?_view=<slug>` starts an admin-only preview, `?_view=off` ends it.
     * The parameter is stripped with a redirect so previewed pages keep clean
     * URLs and the customer can never land on one by copying a link.
     */
    private function handlePreviewToggle(Request $request): ?Response
    {
        if (!$request->has(EcomTheme::PREVIEW_PARAM)) {
            return null;
        }

        $requested = (string) $request->query(EcomTheme::PREVIEW_PARAM);

        if ($requested === 'off') {
            EcomTheme::stopPreview();
        } elseif (Auth::check() && Auth::user()->isAdmin()) {
            EcomTheme::startPreview($requested);
        }

        return redirect()->to($request->fullUrlWithoutQuery([EcomTheme::PREVIEW_PARAM]));
    }
}

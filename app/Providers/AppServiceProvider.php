<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // @base → the path the app is served under: '' at a domain root,
        // '/alzaitoontraders' behind an Apache Alias. Prefix hand-written
        // URLs in views/JS with it (fetch('@base/pos/order')) so they work
        // in both setups; route()/url()/asset() already handle this.
        Blade::directive('base', fn () => '<?php echo e(request()->getBaseUrl()); ?>');
    }
}

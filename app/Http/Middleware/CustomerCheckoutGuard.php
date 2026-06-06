<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerCheckoutGuard
{
    public function handle(Request $request, Closure $next)
    {
        if (Setting::get('customer_login_required', '0') == '1' && !Auth::guard('customer')->check()) {
            session(['url.intended.customer' => route('checkout.index')]);
            return redirect()->route('account.login')->with('error', 'Please log in to your account to continue checkout.');
        }

        return $next($request);
    }
}

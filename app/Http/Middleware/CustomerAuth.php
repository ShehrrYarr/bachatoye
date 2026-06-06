<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('customer')->check()) {
            session(['url.intended.customer' => $request->fullUrl()]);
            return redirect()->route('account.login');
        }

        $account = Auth::guard('customer')->user();
        if (!$account->is_active) {
            Auth::guard('customer')->logout();
            return redirect()->route('account.login')->with('error', 'Your account has been disabled. Please contact support.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosSession;
use App\Models\Shop;
use Illuminate\Http\Request;

class PosSessionController extends Controller
{
    public function index(Request $request)
    {
        $sessions = PosSession::with(['user', 'shop'])
            ->forShopFilter($request->input('shop', ''))
            ->latest('opened_at')
            ->paginate(30)
            ->withQueryString();

        $shops = Shop::orderBy('name')->get();

        return view('admin.pos-sessions.index', compact('sessions', 'shops'));
    }
}

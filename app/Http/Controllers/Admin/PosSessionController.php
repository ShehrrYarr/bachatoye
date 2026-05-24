<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosSession;

class PosSessionController extends Controller
{
    public function index()
    {
        $sessions = PosSession::with('user')->latest('opened_at')->paginate(30);
        return view('admin.pos-sessions.index', compact('sessions'));
    }
}

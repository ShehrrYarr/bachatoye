<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnOrder;
use Illuminate\Support\Facades\Auth;

class ReturnController extends Controller
{
    public function index()
    {
        $returns = ReturnOrder::with(['order', 'customer', 'processedBy'])->latest()->paginate(25);
        return view('admin.returns.index', compact('returns'));
    }

    public function show(ReturnOrder $return)
    {
        $return->load(['order.items', 'items.product', 'customer', 'processedBy']);
        return view('admin.returns.show', compact('return'));
    }

    public function approve(ReturnOrder $return)
    {
        if ($return->status !== 'pending') {
            return back()->withErrors(['error' => 'Return already processed.']);
        }

        $return->update([
            'status'       => 'completed',
            'processed_by' => Auth::id(),
        ]);

        return back()->with('success', 'Return approved and completed.');
    }

    public function reject(ReturnOrder $return)
    {
        $return->update([
            'status'       => 'rejected',
            'processed_by' => Auth::id(),
        ]);
        return back()->with('success', 'Return rejected.');
    }
}

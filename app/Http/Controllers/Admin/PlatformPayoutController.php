<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\DeliveryPlatform;
use App\Models\Order;
use App\Models\PlatformPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformPayoutController extends Controller
{
    public function index()
    {
        $platforms = DeliveryPlatform::with(['orders' => function ($q) {
            $q->where('cod_status', 'pending')->where('status', 'delivered');
        }])->active()->latest()->get();

        $payouts = PlatformPayout::with(['platform', 'bankAccount', 'orders'])
            ->latest('payout_date')
            ->paginate(20);

        return view('admin.platform-payouts.index', compact('platforms', 'payouts'));
    }

    public function create(Request $request)
    {
        $platforms    = DeliveryPlatform::active()->orderBy('name')->get();
        $bankAccounts = BankAccount::active()->orderBy('sort_order')->orderBy('id')->get();
        $platform     = null;
        $pendingOrders = collect();

        if ($request->filled('platform_id')) {
            $platform = DeliveryPlatform::findOrFail($request->platform_id);
            $pendingOrders = Order::where('delivery_platform_id', $platform->id)
                ->where('cod_status', 'pending')
                ->where('status', 'delivered')
                ->with('customer')
                ->latest()
                ->get();
        }

        return view('admin.platform-payouts.create', compact(
            'platforms', 'bankAccounts', 'platform', 'pendingOrders'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'delivery_platform_id' => 'required|exists:delivery_platforms,id',
            'bank_account_id'      => 'required|exists:bank_accounts,id',
            'amount'               => 'required|numeric|min:0.01',
            'payout_date'          => 'required|date',
            'reference'            => 'nullable|string|max:100',
            'notes'                => 'nullable|string|max:500',
            'order_ids'            => 'required|array|min:1',
            'order_ids.*'          => 'exists:orders,id',
        ]);

        // Verify all selected orders belong to this platform and are pending COD
        $orders = Order::whereIn('id', $data['order_ids'])
            ->where('delivery_platform_id', $data['delivery_platform_id'])
            ->where('cod_status', 'pending')
            ->where('status', 'delivered')
            ->get();

        if ($orders->count() !== count($data['order_ids'])) {
            return back()->withErrors(['order_ids' => 'Some selected orders are invalid or already settled.'])->withInput();
        }

        DB::transaction(function () use ($data, $orders) {
            $payout = PlatformPayout::create([
                'delivery_platform_id' => $data['delivery_platform_id'],
                'bank_account_id'      => $data['bank_account_id'],
                'amount'               => $data['amount'],
                'payout_date'          => $data['payout_date'],
                'reference'            => $data['reference'] ?? null,
                'notes'                => $data['notes'] ?? null,
                'created_by'           => auth()->id(),
            ]);

            $pivot = $orders->mapWithKeys(fn($o) => [
                $o->id => ['order_amount' => $o->total],
            ])->toArray();

            $payout->orders()->attach($pivot);

            Order::whereIn('id', $orders->pluck('id'))->update(['cod_status' => 'settled']);
        });

        return redirect()->route('admin.platform-payouts.index')
                         ->with('success', 'Payout recorded. ' . $orders->count() . ' orders marked as settled.');
    }

    public function destroy(PlatformPayout $platformPayout)
    {
        DB::transaction(function () use ($platformPayout) {
            // Revert orders to pending
            $platformPayout->orders()->update(['cod_status' => 'pending']);
            $platformPayout->orders()->detach();
            $platformPayout->delete();
        });

        return back()->with('success', 'Payout deleted and orders reverted to pending.');
    }
}

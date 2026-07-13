<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SerialNumber;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function index()
    {
        $shops = Shop::with('loginUser')
            ->withCount('customers')
            ->orderBy('name')
            ->paginate(20);

        $todaySales = Order::whereIn('shop_id', $shops->pluck('id'))
            ->whereDate('created_at', today())
            ->where('status', 'delivered')
            ->groupBy('shop_id')
            ->selectRaw('shop_id, SUM(total) as total')
            ->pluck('total', 'shop_id');

        return view('admin.shops.index', compact('shops', 'todaySales'));
    }

    public function create()
    {
        return view('admin.shops.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:100',
            'code'                 => 'nullable|string|max:10|unique:shops,code',
            'address'              => 'nullable|string',
            'phone'                => 'nullable|string|max:30',
            'receipt_header'       => 'nullable|string',
            'receipt_footer'       => 'nullable|string',
            'cash_opening_balance' => 'nullable|numeric|min:0',
            'login_name'           => 'required|string|max:100',
            'login_email'          => 'required|email|unique:users,email',
            'login_password'       => 'required|min:8',
        ]);

        DB::transaction(function () use ($data) {
            $shop = Shop::create([
                'name'                 => $data['name'],
                'code'                 => $data['code'] ?: $this->generateCode($data['name']),
                'address'              => $data['address'] ?? null,
                'phone'                => $data['phone'] ?? null,
                'receipt_header'       => $data['receipt_header'] ?? null,
                'receipt_footer'       => $data['receipt_footer'] ?? null,
                'cash_opening_balance' => $data['cash_opening_balance'] ?? 0,
                'is_active'            => true,
            ]);

            $user = User::create([
                'name'           => $data['login_name'],
                'email'          => $data['login_email'],
                'password'       => Hash::make($data['login_password']),
                'password_plain' => $data['login_password'],
                'is_active'      => true,
                'shop_id'        => $shop->id,
            ]);
            $user->assignRole('subshop');
        });

        return redirect()->route('admin.shops.index')->with('success', 'Shop created with its login account.');
    }

    public function show(Shop $shop)
    {
        $shop->load('loginUser');

        $stats = [
            'today_sales'  => Order::where('shop_id', $shop->id)->whereDate('created_at', today())
                ->where('status', 'delivered')->sum('total'),
            'month_sales'  => Order::where('shop_id', $shop->id)
                ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
                ->where('status', 'delivered')->sum('total'),
            'total_orders' => Order::where('shop_id', $shop->id)->count(),
            'customers'    => $shop->customers()->count(),
            'stock_units'  => (int) ShopStock::where('shop_id', $shop->id)->sum('quantity')
                + SerialNumber::where('shop_id', $shop->id)->where('status', 'in_stock')->count(),
        ];

        $recentTransfers = StockTransfer::where('from_shop_id', $shop->id)
            ->orWhere('to_shop_id', $shop->id)
            ->latest()
            ->take(10)
            ->get();

        $recentOrders = Order::where('shop_id', $shop->id)->latest()->take(10)->get();

        return view('admin.shops.show', compact('shop', 'stats', 'recentTransfers', 'recentOrders'));
    }

    public function edit(Shop $shop)
    {
        $shop->load('loginUser');
        return view('admin.shops.edit', compact('shop'));
    }

    public function update(Request $request, Shop $shop)
    {
        $loginUser = $shop->loginUser;

        $data = $request->validate([
            'name'                 => 'required|string|max:100',
            'code'                 => 'required|string|max:10|unique:shops,code,' . $shop->id,
            'address'              => 'nullable|string',
            'phone'                => 'nullable|string|max:30',
            'receipt_header'       => 'nullable|string',
            'receipt_footer'       => 'nullable|string',
            'cash_opening_balance' => 'nullable|numeric|min:0',
            'login_name'           => 'required|string|max:100',
            'login_email'          => 'required|email|unique:users,email,' . ($loginUser?->id ?? 'NULL'),
            'login_password'       => 'nullable|min:8',
        ]);

        DB::transaction(function () use ($data, $shop, $loginUser) {
            $shop->update([
                'name'                 => $data['name'],
                'code'                 => $data['code'],
                'address'              => $data['address'] ?? null,
                'phone'                => $data['phone'] ?? null,
                'receipt_header'       => $data['receipt_header'] ?? null,
                'receipt_footer'       => $data['receipt_footer'] ?? null,
                'cash_opening_balance' => $data['cash_opening_balance'] ?? 0,
            ]);

            $userData = ['name' => $data['login_name'], 'email' => $data['login_email']];
            if (!empty($data['login_password'])) {
                $userData['password']       = Hash::make($data['login_password']);
                $userData['password_plain'] = $data['login_password'];
            }

            if ($loginUser) {
                $loginUser->update($userData);
            } else {
                $user = User::create($userData + [
                    'password'       => Hash::make($data['login_password'] ?? Str::random(16)),
                    'password_plain' => $data['login_password'] ?? null,
                    'is_active'      => true,
                    'shop_id'        => $shop->id,
                ]);
                $user->assignRole('subshop');
            }
        });

        return redirect()->route('admin.shops.index')->with('success', 'Shop updated.');
    }

    public function toggleActive(Shop $shop)
    {
        $shop->update(['is_active' => !$shop->is_active]);
        $shop->loginUser?->update(['is_active' => $shop->is_active]);

        return back()->with('success', $shop->is_active ? 'Shop activated.' : 'Shop deactivated — its login is blocked.');
    }

    private function generateCode(string $name): string
    {
        $base = strtoupper(Str::substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3)) ?: 'SHP';
        $code = $base;
        $i    = 1;
        while (Shop::where('code', $code)->exists()) {
            $code = $base . ++$i;
        }

        return $code;
    }
}

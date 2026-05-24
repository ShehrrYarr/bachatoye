<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class SalesmanController extends Controller
{
    public function index()
    {
        $salesmen = User::role('salesman')->with('loginLogs')->latest()->paginate(20);
        return view('admin.salesmen.index', compact('salesmen'));
    }

    public function create()
    {
        $permissions = Permission::all();
        $sections    = Section::orderBy('sort_order')->get();
        return view('admin.salesmen.create', compact('permissions', 'sections'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = User::create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'phone'          => $data['phone'],
            'password'       => Hash::make($data['password']),
            'password_plain' => $data['password'],
            'is_active'      => true,
        ]);

        $user->assignRole('salesman');

        if ($request->has('permissions')) {
            $user->syncPermissions($request->input('permissions', []));
        }

        // Sync section access
        $user->sections()->sync($request->input('section_ids', []));

        return redirect()->route('admin.salesmen.index')->with('success', 'Salesman created successfully.');
    }

    public function show(User $user)
    {
        $salesman = $user;
        $salesman->load(['loginLogs', 'orders']);
        $stats = [
            'total_orders'    => $salesman->orders->count(),
            'total_sales'     => $salesman->orders->where('status', 'delivered')->sum('total'),
            'today_orders'    => $salesman->orders->filter(fn($o) => $o->created_at->isToday())->count(),
            'month_sales'     => $salesman->orders->where('status', 'delivered')
                                          ->filter(fn($o) => $o->created_at->isCurrentMonth())->sum('total'),
        ];
        $recentOrders = $salesman->orders->sortByDesc('created_at')->take(10);
        return view('admin.salesmen.show', compact('salesman', 'stats', 'recentOrders'));
    }

    public function edit(User $user)
    {
        $salesman     = $user;
        $permissions  = Permission::all();
        $sections     = Section::orderBy('sort_order')->get();
        $userPerms    = $salesman->getDirectPermissions()->pluck('name')->toArray();
        $userSections = $salesman->sections->pluck('id')->toArray();
        return view('admin.salesmen.edit', compact('salesman', 'permissions', 'sections', 'userPerms', 'userSections'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'phone'    => 'nullable|string|max:20',
            'password' => 'nullable|confirmed|min:8',
        ]);

        $update = ['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone']];
        if (!empty($data['password'])) {
            $update['password']       = Hash::make($data['password']);
            $update['password_plain'] = $data['password'];
        }

        $user->update($update);

        // Sync permissions
        $user->syncPermissions($request->input('permissions', []));

        // Sync section access
        $user->sections()->sync($request->input('section_ids', []));

        return redirect()->route('admin.salesmen.index')->with('success', 'Salesman updated.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.salesmen.index')->with('success', 'Salesman removed.');
    }

    public function updatePermissions(Request $request, User $user)
    {
        $request->validate(['permissions' => 'array']);
        $user->syncPermissions($request->input('permissions', []));
        return back()->with('success', 'Permissions updated.');
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', $user->is_active ? 'Account activated.' : 'Account deactivated.');
    }

    public function loginLog(User $user)
    {
        $salesman = $user;
        $logs = LoginLog::where('user_id', $salesman->id)->latest('logged_in_at')->paginate(30);
        return view('admin.salesmen.login-log', compact('salesman', 'logs'));
    }
}

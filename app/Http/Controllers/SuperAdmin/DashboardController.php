<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    /** Staff monitored here — never the super-admin account itself. */
    private function staffQuery()
    {
        return User::role(['admin', 'salesman', 'subshop'])->with('latestLogin');
    }

    public function index()
    {
        $staff = $this->staffQuery()->orderBy('name')->get();
        return view('superadmin.dashboard', compact('staff'));
    }

    public function history(User $user)
    {
        abort_if($user->isSuperAdmin(), 404);
        abort_unless($user->hasAnyRole(['admin', 'salesman', 'subshop']), 404);

        $logs = LoginLog::where('user_id', $user->id)->latest('logged_in_at')->paginate(30);
        return view('superadmin.history', compact('user', 'logs'));
    }

    public function resetPassword(Request $request, User $user)
    {
        abort_if($user->isSuperAdmin(), 404);
        abort_unless($user->hasAnyRole(['admin', 'salesman', 'subshop']), 404);

        $data = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password'       => Hash::make($data['password']),
            'password_plain' => $data['password'],
        ]);

        return back()->with('success', "Password reset for {$user->name}.");
    }

    public function editAccount()
    {
        return view('superadmin.account', ['account' => Auth::user()]);
    }

    public function updateAccount(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $update = ['name' => $data['name'], 'email' => $data['email']];
        if (! empty($data['password'])) {
            $update['password']       = Hash::make($data['password']);
            $update['password_plain'] = $data['password'];
        }

        $user->update($update);

        return back()->with('success', 'Account updated.');
    }
}

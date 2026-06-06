<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function showLogin()
    {
        return view('account.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $account = CustomerAccount::where('email', $credentials['email'])->first();

        if (!$account || !Hash::check($credentials['password'], $account->password)) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->withInput();
        }

        if (!$account->is_active) {
            return back()->withErrors(['email' => 'Your account has been disabled. Please contact support.'])->withInput();
        }

        Auth::guard('customer')->login($account, $request->boolean('remember'));
        $account->update(['last_login_at' => now()]);

        $intended = session()->pull('url.intended.customer', route('account.dashboard'));
        return redirect($intended);
    }

    public function showRegister()
    {
        return view('account.register', [
            'questions' => CustomerAccount::SECURITY_QUESTIONS,
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'email'             => 'required|email|max:150|unique:customer_accounts,email',
            'phone'             => 'required|string|max:20',
            'password'          => 'required|string|min:6|confirmed',
            'security_question' => 'required|string|in:' . implode(',', CustomerAccount::SECURITY_QUESTIONS),
            'security_answer'   => 'required|string|max:255',
        ], [
            'email.unique'   => 'An account with this email already exists.',
            'phone.required' => 'Phone number is required to link your account.',
        ]);

        // Find or create the customer record by phone
        $customer = Customer::where('phone', $data['phone'])->first();
        if (!$customer) {
            $customer = Customer::create([
                'name'          => $data['name'],
                'phone'         => $data['phone'],
                'email'         => $data['email'],
                'source'        => 'online',
                'khata_enabled' => false,
            ]);
        } else {
            // Update name/email from registration if blank
            if (!$customer->email) {
                $customer->update(['email' => $data['email']]);
            }
        }

        $account = CustomerAccount::create([
            'customer_id'       => $customer->id,
            'email'             => $data['email'],
            'password'          => $data['password'],
            'security_question' => $data['security_question'],
            'security_answer'   => Hash::make(strtolower(trim($data['security_answer']))),
        ]);

        Auth::guard('customer')->login($account);

        $intended = session()->pull('url.intended.customer', route('account.dashboard'));
        return redirect($intended)->with('success', 'Welcome! Your account has been created.');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home')->with('success', 'You have been logged out.');
    }
}

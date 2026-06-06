<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerPasswordController extends Controller
{
    public function showForgot()
    {
        return view('account.forgot-password');
    }

    // AJAX: given an email, return the security question
    public function getQuestion(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $account = CustomerAccount::where('email', $request->email)->first();
        if (!$account) {
            return response()->json(['error' => 'No account found with that email address.'], 404);
        }

        session(['_reset_question' => $account->security_question]);

        return response()->json(['question' => $account->security_question]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'email'                 => 'required|email',
            'security_answer'       => 'required|string',
            'password'              => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'Passwords do not match.',
        ]);

        $account = CustomerAccount::where('email', $data['email'])->first();

        if (!$account) {
            return back()->withErrors(['email' => 'No account found with that email address.'])->withInput();
        }

        if (!Hash::check(strtolower(trim($data['security_answer'])), $account->security_answer)) {
            return back()->withErrors(['security_answer' => 'Incorrect answer. Please try again.'])->withInput();
        }

        $account->update(['password' => $data['password']]);
        session()->forget('_reset_question');

        return redirect()->route('account.login')->with('success', 'Password reset successfully. You can now log in.');
    }
}

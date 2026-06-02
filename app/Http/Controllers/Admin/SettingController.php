<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'shop_name'           => Setting::get('shop_name', 'MobileHub'),
            'shop_tagline'        => Setting::get('shop_tagline'),
            'shop_phone'          => Setting::get('shop_phone'),
            'shop_email'          => Setting::get('shop_email'),
            'shop_address'        => Setting::get('shop_address'),
            'receipt_header'      => Setting::get('receipt_header'),
            'receipt_footer'      => Setting::get('receipt_footer'),
            'delivery_charge'     => Setting::get('delivery_charge', 150),
            'free_delivery_above' => Setting::get('free_delivery_above', 5000),
            'low_stock_threshold' => Setting::get('low_stock_threshold', 5),
            'logo'                => Setting::get('logo'),
            'primary_color'       => Setting::get('primary_color',   '#e11d48'),
            'secondary_color'     => Setting::get('secondary_color', '#be123c'),
            'use_gradient'        => Setting::get('use_gradient',    '1'),
        ];
        $sections = Section::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.settings.index', compact('settings', 'sections'));
    }

    public function updateSectionPermissions(Request $request)
    {
        $sections = Section::all();
        foreach ($sections as $section) {
            $section->update([
                'exchange_enabled' => $request->boolean("exchange_{$section->id}"),
            ]);
        }
        return back()->with('success', 'Section permissions saved.');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'shop_name'           => 'required|string|max:100',
            'shop_tagline'        => 'nullable|string|max:200',
            'shop_phone'          => 'nullable|string|max:30',
            'shop_email'          => 'nullable|email',
            'shop_address'        => 'nullable|string|max:300',
            'receipt_header'      => 'nullable|string|max:255',
            'receipt_footer'      => 'nullable|string|max:255',
            'delivery_charge'     => 'required|numeric|min:0',
            'free_delivery_above' => 'required|numeric|min:0',
            'low_stock_threshold'     => 'required|integer|min:1',
            'banner_slider_interval'   => 'required|integer|min:2|max:15',
            'product_image_interval'     => 'required|integer|min:2|max:10',
            'announcement_enabled'       => 'nullable|in:0,1',
            'announcement_title'         => 'nullable|string|max:150',
            'announcement_message'       => 'nullable|string|max:1000',
            'bank_details'            => 'nullable|string|max:1000',
            'social_facebook'     => 'nullable|url|max:255',
            'social_instagram'    => 'nullable|url|max:255',
            'social_tiktok'       => 'nullable|url|max:255',
            'whatsapp_number'     => 'nullable|string|max:20',
            'whatsapp_message'    => 'nullable|string|max:300',
            'logo'                => 'nullable|image|max:2048',
            'primary_color'       => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'secondary_color'     => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'use_gradient'        => 'nullable|in:0,1',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('settings', 'public');
        } else {
            unset($data['logo']);
        }

        // Handle logo removal
        if ($request->boolean('remove_logo')) {
            Setting::set('logo', null);
        }

        // Normalise checkboxes (unchecked = not sent = '0')
        $data['use_gradient']          = $request->input('use_gradient', '0');
        $data['announcement_enabled']  = $request->input('announcement_enabled', '0');

        Setting::setMany($data);
        return back()->with('success', 'Settings saved.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate(['logo' => 'required|image|max:2048']);
        $path = $request->file('logo')->store('settings', 'public');
        Setting::set('logo', $path);
        return back()->with('success', 'Logo updated.');
    }

    public function updateAccount(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'             => 'required|string|max:100',
            'email'            => 'required|email|max:150|unique:users,email,' . $user->id,
            'current_password' => 'required|string',
            'new_password'     => ['nullable', 'confirmed', Password::min(6)],
        ], [
            'current_password.required' => 'Please enter your current password to confirm changes.',
            'new_password.confirmed'    => 'New passwords do not match.',
            'email.unique'              => 'That email is already used by another account.',
        ]);

        // Verify current password before allowing any change
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
        }

        $user->name  = $request->name;
        $user->email = $request->email;

        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return back()->with('account_success', 'Account updated successfully.');
    }
}

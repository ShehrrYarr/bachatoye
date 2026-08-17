<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Setting;
use App\Support\EcomTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingController extends Controller
{
    /**
     * Default barcode label design. Positions are % of the label,
     * font sizes are px at print resolution (96 dpi), w/h are inches.
     */
    public const DEFAULT_BARCODE_TEMPLATE = [
        'w' => 2.5,
        'h' => 1.5,
        'elements' => [
            'shop_name'    => ['visible' => false, 'x' => 50, 'y' => 2,  'align' => 'center', 'size' => 10, 'bold' => true],
            'product_name' => ['visible' => true,  'x' => 50, 'y' => 6,  'align' => 'center', 'size' => 11, 'bold' => true],
            'barcode'      => ['visible' => true,  'x' => 50, 'y' => 24, 'align' => 'center', 'barHeight' => 42, 'barWidth' => 1.5],
            'barcode_text' => ['visible' => true,  'x' => 50, 'y' => 56, 'align' => 'center', 'size' => 10, 'bold' => false],
            'price'        => ['visible' => true,  'x' => 50, 'y' => 80, 'align' => 'center', 'size' => 12, 'bold' => true],
            'sku'          => ['visible' => false, 'x' => 50, 'y' => 92, 'align' => 'center', 'size' => 8,  'bold' => false],
        ],
    ];

    /** Load the saved template, falling back to the default design. */
    public static function barcodeTemplate(): array
    {
        $saved    = Setting::get('barcode_label_template');
        $template = $saved ? json_decode($saved, true) : null;

        if (!is_array($template) || empty($template['elements'])) {
            return self::DEFAULT_BARCODE_TEMPLATE;
        }

        // Migrate old templates where the digits were part of the barcode
        // element (showText/textSize): spawn a standalone barcode_text element
        // right under the barcode so the printed label looks unchanged.
        if (!isset($template['elements']['barcode_text']) && isset($template['elements']['barcode'])) {
            $b      = $template['elements']['barcode'];
            $hPx    = max(0.3, (float) ($template['h'] ?? 1.5)) * 96;
            $barPct = (($b['barHeight'] ?? 42) / $hPx) * 100;

            $template['elements']['barcode_text'] = [
                'visible' => (bool) ($b['showText'] ?? true),
                'x'       => $b['x'] ?? 50,
                'y'       => min(95, round(($b['y'] ?? 24) + $barPct + 1, 1)),
                'align'   => $b['align'] ?? 'center',
                'size'    => $b['textSize'] ?? 10,
                'bold'    => false,
            ];
        }

        // Merge with defaults so newly added elements get sane values
        $template['elements'] += self::DEFAULT_BARCODE_TEMPLATE['elements'];
        return $template;
    }

    public function barcodeCanvas()
    {
        $template = self::barcodeTemplate();
        return view('admin.settings.barcode-canvas', compact('template'));
    }

    public function saveBarcodeCanvas(Request $request)
    {
        $data = $request->validate([
            'w'        => 'required|numeric|min:0.5|max:12',
            'h'        => 'required|numeric|min:0.3|max:12',
            'elements' => 'required|array',
        ]);

        Setting::set('barcode_label_template', json_encode([
            'w'        => (float) $data['w'],
            'h'        => (float) $data['h'],
            'elements' => $data['elements'],
        ]));

        return response()->json(['success' => true]);
    }

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
            'low_stock_threshold'       => Setting::get('low_stock_threshold', 5),
            'stock_adjustment_enabled'  => Setting::get('stock_adjustment_enabled', '1'),
            'logo'                      => Setting::get('logo'),
            'primary_color'       => Setting::get('primary_color',   '#e11d48'),
            'secondary_color'     => Setting::get('secondary_color', '#be123c'),
            'use_gradient'        => Setting::get('use_gradient',    '1'),
        ];
        $sections = Section::orderBy('sort_order')->orderBy('name')->get();

        $themes       = EcomTheme::all();
        $activeTheme  = EcomTheme::applied();
        $themeColors  = [];
        foreach ($themes as $slug => $meta) {
            $themeColors[$slug] = EcomTheme::colors($slug) + [
                'overridden' => EcomTheme::colorsOverridden($slug),
            ];
        }

        return view('admin.settings.index', compact(
            'settings', 'sections', 'themes', 'activeTheme', 'themeColors'
        ));
    }

    /* ===================== Storefront views (themes) ===================== */

    /** Make a view live for every customer. */
    public function applyTheme(Request $request)
    {
        $data = $request->validate([
            'theme' => 'required|string|in:' . implode(',', array_keys(EcomTheme::all())),
        ]);

        EcomTheme::apply($data['theme']);

        return redirect()
            ->route('admin.settings.index')
            ->withFragment('storefront-view')
            ->with('success', EcomTheme::meta($data['theme'])['name'] . ' is now live on your store.');
    }

    /** Restore the storefront the shop had before views existed. */
    public function resetTheme()
    {
        EcomTheme::reset();

        return redirect()
            ->route('admin.settings.index')
            ->withFragment('storefront-view')
            ->with('success', 'Storefront reset to the default view.');
    }

    /** Override (or clear) a single view's colours without changing its design. */
    public function updateThemeColors(Request $request)
    {
        $data = $request->validate([
            'theme'     => 'required|string|in:' . implode(',', array_keys(EcomTheme::all())),
            'primary'   => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'secondary' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        if ($request->boolean('restore_defaults')) {
            EcomTheme::resetColors($data['theme']);
            $message = 'Colours restored to the view\'s original design.';
        } else {
            Setting::set(EcomTheme::colorKey($data['theme'], 'primary'),   $data['primary']   ?? null);
            Setting::set(EcomTheme::colorKey($data['theme'], 'secondary'), $data['secondary'] ?? null);
            $message = 'Storefront colours updated.';
        }

        return redirect()
            ->route('admin.settings.index')
            ->withFragment('storefront-view')
            ->with('success', $message);
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
            'low_stock_threshold'      => 'required|integer|min:1',
            'stock_adjustment_enabled'  => 'nullable|in:0,1',
            'customer_login_required'   => 'nullable|in:0,1',
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
            'whatsapp_greeting'   => 'nullable|string|max:300',
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
        $data['use_gradient']              = $request->input('use_gradient', '0');
        $data['announcement_enabled']      = $request->input('announcement_enabled', '0');
        $data['stock_adjustment_enabled']  = $request->input('stock_adjustment_enabled', '0');
        $data['customer_login_required']   = $request->input('customer_login_required', '0');

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

    public function runMigrate()
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            return response()->json([
                'success' => true,
                'output'  => trim($output) ?: 'Nothing to migrate — all migrations are already up to date.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'output'  => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function runGitPull()
    {
        try {
            $cwd    = base_path();
            $cmd    = 'git -C ' . escapeshellarg($cwd) . ' pull origin master 2>&1';
            $output = [];
            $code   = 0;
            exec($cmd, $output, $code);
            $text = implode("\n", $output);
            return response()->json([
                'success' => $code === 0,
                'output'  => trim($text) ?: '(no output)',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'output'  => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function runOptimize()
    {
        try {
            Artisan::call('optimize');
            $output = Artisan::output();
            return response()->json([
                'success' => true,
                'output'  => trim($output) ?: 'Optimization complete.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'output'  => 'Error: ' . $e->getMessage(),
            ], 500);
        }
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
            $user->password       = Hash::make($request->new_password);
            $user->password_plain = $request->new_password;
        }

        $user->save();

        return back()->with('account_success', 'Account updated successfully.');
    }
}

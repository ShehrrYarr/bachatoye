@extends('layouts.admin')
@section('title', 'Settings')

@section('content')
<h1 class="text-xl font-bold text-gray-900 mb-6">Store Settings</h1>

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- Shop Identity --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Shop Identity</h2></div>
            <div class="card-body space-y-4">
                <div>
                    <label class="form-label">Shop Name</label>
                    <input type="text" name="shop_name" value="{{ old('shop_name', \App\Models\Setting::get('shop_name')) }}"
                           class="form-input" placeholder="MobileHub">
                </div>
                <div>
                    <label class="form-label">Tagline</label>
                    <input type="text" name="shop_tagline" value="{{ old('shop_tagline', \App\Models\Setting::get('shop_tagline')) }}"
                           class="form-input" placeholder="Your One-Stop Mobile Store">
                </div>
                <div>
                    <label class="form-label">Logo</label>
                    @if(\App\Models\Setting::get('logo'))
                    <div class="flex items-center gap-3 mb-2">
                        <img src="{{ asset('storage/'.\App\Models\Setting::get('logo')) }}" class="h-12 rounded-lg bg-gray-100 p-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remove_logo" value="1" class="w-4 h-4 text-red-500 rounded">
                            <span class="text-sm text-red-500">Remove logo</span>
                        </label>
                    </div>
                    @endif
                    <input type="file" name="logo" accept="image/*" class="form-input">
                    <p class="form-hint">Recommended: 180×60px transparent PNG</p>
                </div>
            </div>
        </div>

        {{-- Contact Info --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Contact Information</h2></div>
            <div class="card-body space-y-4">
                <div>
                    <label class="form-label">Phone</label>
                    <input type="text" name="shop_phone" value="{{ old('shop_phone', \App\Models\Setting::get('shop_phone')) }}"
                           class="form-input" placeholder="03001234567">
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="shop_email" value="{{ old('shop_email', \App\Models\Setting::get('shop_email')) }}"
                           class="form-input" placeholder="info@mobilehub.com">
                </div>
                <div>
                    <label class="form-label">Address</label>
                    <textarea name="shop_address" rows="2" class="form-textarea"
                              placeholder="123 Main Street, Lahore">{{ old('shop_address', \App\Models\Setting::get('shop_address')) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Delivery --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Delivery Settings</h2></div>
            <div class="card-body space-y-4">
                <div>
                    <label class="form-label">Delivery Charge (Rs.)</label>
                    <input type="number" name="delivery_charge"
                           value="{{ old('delivery_charge', \App\Models\Setting::get('delivery_charge', 150)) }}"
                           min="0" class="form-input w-36">
                </div>
                <div>
                    <label class="form-label">Free Delivery Above (Rs.)</label>
                    <input type="number" name="free_delivery_above"
                           value="{{ old('free_delivery_above', \App\Models\Setting::get('free_delivery_above', 5000)) }}"
                           min="0" class="form-input w-36">
                    <p class="form-hint">Set to 0 to disable free delivery threshold</p>
                </div>
                <div>
                    <label class="form-label">Low Stock Alert Threshold</label>
                    <input type="number" name="low_stock_threshold"
                           value="{{ old('low_stock_threshold', \App\Models\Setting::get('low_stock_threshold', 5)) }}"
                           min="1" class="form-input w-36">
                    <p class="form-hint">Products at or below this quantity will appear in low stock alerts</p>
                </div>
            </div>
        </div>

        {{-- Banner Slider --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Banner Slider</h2></div>
            <div class="card-body space-y-4">
                <div>
                    <label class="form-label">Auto-Slide Interval (seconds)</label>
                    <div class="flex items-center gap-4">
                        <input type="range" name="banner_slider_interval"
                               min="2" max="15" step="1"
                               value="{{ old('banner_slider_interval', \App\Models\Setting::get('banner_slider_interval', 5)) }}"
                               class="w-48 accent-primary-600"
                               oninput="document.getElementById('slider_interval_val').textContent = this.value">
                        <span class="text-lg font-bold text-primary-600 w-12" id="slider_interval_val">
                            {{ \App\Models\Setting::get('banner_slider_interval', 5) }}
                        </span>
                        <span class="text-sm text-gray-500">seconds per slide</span>
                    </div>
                    <p class="form-hint mt-2">Controls how long each banner is shown before auto-advancing (2 – 15 s)</p>
                </div>
            </div>
        </div>

        {{-- Payment --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Payment & Bank</h2></div>
            <div class="card-body space-y-4">
                <div>
                    <label class="form-label">Bank Account Details</label>
                    <textarea name="bank_details" rows="4" class="form-textarea font-mono text-sm"
                              placeholder="Bank: HBL&#10;Account Title: MobileHub&#10;Account No: 0001-1234567&#10;IBAN: PK36HABB0000000123456702">{{ old('bank_details', \App\Models\Setting::get('bank_details')) }}</textarea>
                    <p class="form-hint">Shown to customers on checkout when they select Bank Transfer</p>
                </div>
            </div>
        </div>

        {{-- POS Receipt --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">POS Receipt</h2></div>
            <div class="card-body space-y-4">
                <div>
                    <label class="form-label">Receipt Header Text</label>
                    <textarea name="receipt_header" rows="2" class="form-textarea"
                              placeholder="Thank you for shopping with us!">{{ old('receipt_header', \App\Models\Setting::get('receipt_header')) }}</textarea>
                </div>
                <div>
                    <label class="form-label">Receipt Footer Text</label>
                    <textarea name="receipt_footer" rows="2" class="form-textarea"
                              placeholder="Exchange within 7 days with receipt">{{ old('receipt_footer', \App\Models\Setting::get('receipt_footer')) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Social Media --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Social Media</h2></div>
            <div class="card-body space-y-4">
                <div>
                    <label class="form-label">Facebook URL</label>
                    <input type="url" name="social_facebook" value="{{ old('social_facebook', \App\Models\Setting::get('social_facebook')) }}"
                           class="form-input" placeholder="https://facebook.com/...">
                </div>
                <div>
                    <label class="form-label">Instagram URL</label>
                    <input type="url" name="social_instagram" value="{{ old('social_instagram', \App\Models\Setting::get('social_instagram')) }}"
                           class="form-input" placeholder="https://instagram.com/...">
                </div>
                <div>
                    <label class="form-label">TikTok URL</label>
                    <input type="url" name="social_tiktok" value="{{ old('social_tiktok', \App\Models\Setting::get('social_tiktok')) }}"
                           class="form-input" placeholder="https://tiktok.com/@...">
                </div>
                <div>
                    <label class="form-label">WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', \App\Models\Setting::get('whatsapp_number')) }}"
                           class="form-input" placeholder="923001234567 (with country code, no +)">
                </div>
            </div>
        </div>

        {{-- Colour Customization --}}
        <div class="card xl:col-span-2"
             x-data="{
                 primary:   '{{ old('primary_color',   $settings['primary_color']   ?? '#e11d48') }}',
                 secondary: '{{ old('secondary_color', $settings['secondary_color'] ?? '#be123c') }}',
                 gradient:  {{ (old('use_gradient', $settings['use_gradient'] ?? '1') === '1') ? 'true' : 'false' }},
                 get gradStyle() {
                     return this.gradient
                         ? `linear-gradient(135deg, ${this.primary} 0%, ${this.secondary} 100%)`
                         : this.primary;
                 },
                 reset() {
                     this.primary   = '#e11d48';
                     this.secondary = '#be123c';
                     this.gradient  = true;
                 }
             }">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">
                    <i class="fas fa-palette mr-1.5 text-purple-500"></i> Colour Customization
                </h2>
                <button type="button" @click="reset()"
                        class="btn-outline btn-sm text-gray-500 hover:text-red-600">
                    <i class="fas fa-undo mr-1"></i> Reset to Default
                </button>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- Left: controls --}}
                    <div class="space-y-5">

                        {{-- Primary colour --}}
                        <div>
                            <label class="form-label">Primary Colour</label>
                            <p class="form-hint mb-2">Used on buttons, links, active sidebar items and highlights throughout the admin and store.</p>
                            <div class="flex items-center gap-3">
                                {{-- Colour picker updates `primary` via x-model; hidden input submits the value --}}
                                <input type="color" x-model="primary"
                                       class="h-10 w-16 rounded-lg border border-gray-300 cursor-pointer p-0.5">
                                <input type="text" x-model="primary"
                                       pattern="^#[0-9a-fA-F]{6}$"
                                       placeholder="#e11d48"
                                       class="form-input w-32 font-mono text-sm uppercase">
                            </div>
                            <input type="hidden" name="primary_color" :value="primary">
                        </div>

                        {{-- Secondary colour (visible only when gradient is on) --}}
                        <div x-show="gradient" x-transition>
                            <label class="form-label">Secondary Colour <span class="text-xs text-gray-400">(gradient end)</span></label>
                            <p class="form-hint mb-2">The gradient fades from primary to this colour on buttons, headers, and the store topbar/footer.</p>
                            <div class="flex items-center gap-3">
                                <input type="color" x-model="secondary"
                                       class="h-10 w-16 rounded-lg border border-gray-300 cursor-pointer p-0.5">
                                <input type="text" x-model="secondary"
                                       pattern="^#[0-9a-fA-F]{6}$"
                                       placeholder="#be123c"
                                       class="form-input w-32 font-mono text-sm uppercase">
                            </div>
                        </div>
                        {{-- Single hidden input always submits secondary_color --}}
                        <input type="hidden" name="secondary_color" :value="secondary">

                        {{-- Gradient toggle --}}
                        <div class="flex items-center gap-3">
                            <div class="relative cursor-pointer rounded-full shrink-0"
                                 style="width:44px;height:24px;transition:background-color 0.2s"
                                 :style="{ backgroundColor: gradient ? 'var(--app-primary, #e11d48)' : '#d1d5db' }"
                                 @click="gradient = !gradient">
                                <div class="absolute bg-white rounded-full shadow-sm"
                                     style="top:2px;left:2px;width:20px;height:20px;transition:transform 0.2s"
                                     :style="{ transform: gradient ? 'translateX(20px)' : 'translateX(0)' }"></div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-700">Use Gradient</div>
                                <div class="text-xs text-gray-400">Blend primary → secondary on buttons &amp; headers</div>
                            </div>
                        </div>
                        {{-- Hidden checkbox value --}}
                        <input type="hidden" name="use_gradient" :value="gradient ? '1' : '0'">
                    </div>

                    {{-- Right: live preview --}}
                    <div>
                        <label class="form-label mb-3">Live Preview</label>
                        <div class="rounded-xl border border-gray-200 overflow-hidden shadow-sm">

                            {{-- Preview sidebar header --}}
                            <div class="flex items-center gap-2.5 px-4 py-3 text-white text-sm font-semibold"
                                 :style="{ background: gradStyle }">
                                <div class="w-6 h-6 bg-white/20 rounded flex items-center justify-center">
                                    <i class="fas fa-mobile-alt text-white text-xs"></i>
                                </div>
                                <span>Admin Panel</span>
                            </div>

                            {{-- Preview body --}}
                            <div class="bg-gray-50 p-4 space-y-3">
                                {{-- Active nav item --}}
                                <div class="flex items-center gap-2 text-xs text-white rounded-lg px-3 py-2 font-medium"
                                     :style="{ background: gradStyle }">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </div>

                                {{-- Primary button --}}
                                <div>
                                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold text-white shadow-sm cursor-default"
                                         :style="{ background: gradStyle }">
                                        <i class="fas fa-save"></i> Save Settings
                                    </div>
                                </div>

                                {{-- Colour swatches --}}
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full border border-white shadow"
                                         :style="{ background: primary }"></div>
                                    <div class="text-xs text-gray-500" x-text="primary"></div>
                                    <template x-if="gradient">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-arrow-right text-gray-300 text-xs"></i>
                                            <div class="w-6 h-6 rounded-full border border-white shadow"
                                                 :style="{ background: secondary }"></div>
                                            <div class="text-xs text-gray-500" x-text="secondary"></div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Ecom topbar preview --}}
                                <div class="rounded-lg overflow-hidden border border-gray-200 text-xs text-white text-center py-1.5"
                                     :style="{ background: gradStyle }">
                                    Store Topbar / Footer
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex gap-3 mt-6">
        <button type="submit" class="btn-primary btn-lg">
            <i class="fas fa-save mr-2"></i> Save All Settings
        </button>
    </div>
</form>

{{-- ===== Section Permissions (separate form) ===== --}}
<h2 class="text-lg font-bold text-gray-900 mt-10 mb-4">Section Permissions</h2>
<p class="text-sm text-gray-500 mb-4">Control which sections allow the Exchange / Trade-in feature in the POS. When enabled, the cashier will see an exchange panel whenever a product from that section is added to the cart.</p>

<form method="POST" action="{{ route('admin.settings.sections') }}" class="max-w-2xl">
    @csrf
    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">POS Exchange Feature — by Section</h2></div>
        <div class="divide-y divide-gray-100">
            @forelse($sections as $section)
            <div class="flex items-center justify-between px-5 py-4"
                 x-data="{ on: {{ $section->exchange_enabled ? 'true' : 'false' }} }">
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-gray-800">{{ $section->name }}</div>
                    <div class="text-xs mt-0.5"
                         :class="on ? 'text-primary-600' : 'text-gray-400'"
                         x-text="on ? 'Exchange enabled — cashier will see trade-in panel' : 'No exchange for this section'"></div>
                </div>

                {{-- Hidden checkbox for form submission --}}
                <input type="checkbox" name="exchange_{{ $section->id }}" value="1" class="sr-only" x-model="on">

                {{-- Visual toggle — use :style so colors render regardless of Tailwind compilation --}}
                <div class="relative ml-4 shrink-0 cursor-pointer rounded-full"
                     style="width:44px;height:24px;transition:background-color 0.2s;"
                     :style="{ backgroundColor: on ? '#e11d48' : '#d1d5db' }"
                     @click="on = !on">
                    <div class="absolute bg-white rounded-full shadow-sm"
                         style="top:2px;left:2px;width:20px;height:20px;transition:transform 0.2s;"
                         :style="{ transform: on ? 'translateX(20px)' : 'translateX(0)' }"></div>
                </div>
            </div>
            @empty
            <div class="px-5 py-6 text-center text-sm text-gray-400">
                <i class="fas fa-layer-group text-2xl mb-2 block"></i>
                No sections created yet. Go to <a href="{{ route('admin.sections.index') }}" class="text-primary-600 hover:underline">Sections</a> to create some.
            </div>
            @endforelse
        </div>
    </div>
    @if($sections->count())
    <div class="flex gap-3 mt-4">
        <button type="submit" class="btn-primary btn-lg">
            <i class="fas fa-save mr-2"></i> Save Section Permissions
        </button>
    </div>
    @endif
</form>

{{-- ===== Account Settings (separate form) ===== --}}
<h2 class="text-lg font-bold text-gray-900 mt-10 mb-4">My Account</h2>

@if(session('account_success'))
<div class="alert-success mb-4" x-data x-init="setTimeout(() => $el.remove(), 4000)">
    <i class="fas fa-check-circle shrink-0"></i>
    <span>{{ session('account_success') }}</span>
</div>
@endif

<form method="POST" action="{{ route('admin.settings.account') }}"
      x-data="{ showPass: false }" class="max-w-2xl">
    @csrf

    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">Account Credentials</h2></div>
        <div class="card-body space-y-4">

            <div>
                <label class="form-label">Full Name</label>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}"
                       class="form-input @error('name') border-red-500 @enderror" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}"
                       class="form-input @error('email') border-red-500 @enderror" required>
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-gray-100 pt-4">
                <p class="text-sm text-gray-500 mb-3">Leave the fields below blank if you don't want to change your password.</p>

                <div class="space-y-3">
                    <div>
                        <label class="form-label">New Password</label>
                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" name="new_password"
                                   class="form-input pr-10 @error('new_password') border-red-500 @enderror"
                                   placeholder="Leave blank to keep current password">
                            <button type="button" @click="showPass = !showPass"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i :class="showPass ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                        @error('new_password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Confirm New Password</label>
                        <input :type="showPass ? 'text' : 'password'" name="new_password_confirmation"
                               class="form-input" placeholder="Repeat new password">
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <label class="form-label">Current Password <span class="text-red-500">*</span></label>
                <p class="text-xs text-gray-400 mb-2">Required to confirm any changes above.</p>
                <input type="password" name="current_password"
                       class="form-input @error('current_password') border-red-500 @enderror"
                       placeholder="Enter your current password" required>
                @error('current_password') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="flex gap-3 mt-4">
        <button type="submit" class="btn-primary btn-lg">
            <i class="fas fa-user-shield mr-2"></i> Update Account
        </button>
    </div>
</form>
@endsection

@extends('layouts.ecom')
@section('title', 'Checkout')

@section('content')
<div class="t-container py-6 md:py-8" style="max-width: 68rem;">

    @include('theme.breadcrumb', ['crumbs' => [
        ['Cart', route('cart.index')],
        ['Checkout', null],
    ]])

    {{-- Step indicator --}}
    <div class="flex items-center gap-2 md:gap-3 mb-8">
        @foreach([['Cart', true], ['Details & Payment', true], ['Confirmation', false]] as $i => [$label, $done])
        <div class="flex items-center gap-2 {{ $i === 2 ? 'opacity-45' : '' }}">
            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                  style="{{ $done ? 'background: var(--app-gradient); color:#fff;' : 'background: var(--t-surface-2); color: var(--t-muted); border:1px solid var(--t-border);' }}">
                {{ $i + 1 }}
            </span>
            <span class="text-xs md:text-sm font-semibold whitespace-nowrap {{ $i === 1 ? '' : 't-muted' }}">{{ $label }}</span>
        </div>
        @if($i < 2)
        <span class="flex-1 h-px" style="background: var(--t-border);"></span>
        @endif
        @endforeach
    </div>

    <h1 class="text-2xl md:text-3xl font-extrabold mb-6 t-heading">Checkout</h1>

    @php $authCustomer = Auth::guard('customer')->check() ? Auth::guard('customer')->user()->customer : null; @endphp

    @if($errors->has('error'))
    <div class="mb-6 px-4 py-3 text-sm" style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; border-radius: var(--t-radius-sm);">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first('error') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8"
         x-data="{
             paymentMethod: '{{ old('payment_method', $codAvailable ? 'cash' : 'bank_transfer') }}',
             allBankFreeDelivery: {{ $allBankFreeDelivery ? 'true' : 'false' }},
             baseDelivery: {{ $deliveryCharge }},
             get effectiveDelivery() {
                 return this.allBankFreeDelivery && this.paymentMethod === 'bank_transfer' ? 0 : this.baseDelivery;
             },
             get effectiveTotal() {
                 return Math.max(0, {{ $subtotal - $couponDiscount }} + this.effectiveDelivery);
             }
         }">

        {{-- ── The checkout form ───────────────────────────────────────── --}}
        <form id="checkout-form" method="POST" action="{{ route('checkout.store') }}" enctype="multipart/form-data"
              class="lg:col-span-2 space-y-5">
            @csrf

            <div class="t-card overflow-hidden">
                <div class="px-5 py-4 flex items-center gap-2.5" style="border-bottom:1px solid var(--t-border);">
                    <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                          style="background: rgb(var(--t-accent-rgb) / .12);">
                        <i class="fas fa-location-dot text-sm t-accent"></i>
                    </span>
                    <h2 class="font-extrabold t-heading">Contact &amp; Delivery</h2>
                </div>

                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="tc-label">Full Name <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $authCustomer?->name) }}"
                                   class="t-input" placeholder="Your full name" required
                                   @error('name') style="border-color:#f87171;" @enderror>
                            @error('name') <p class="tc-err">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="tc-label">Phone Number <span style="color:#ef4444;">*</span></label>
                            <input type="tel" name="phone" value="{{ old('phone', $authCustomer?->phone) }}"
                                   class="t-input" placeholder="03XX-XXXXXXX" required
                                   @error('phone') style="border-color:#f87171;" @enderror>
                            @error('phone') <p class="tc-err">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="tc-label">Delivery Address <span style="color:#ef4444;">*</span></label>
                        <textarea name="address" rows="2" class="t-input" placeholder="Street address, area, landmark" required
                                  @error('address') style="border-color:#f87171;" @enderror>{{ old('address', $authCustomer?->address) }}</textarea>
                        @error('address') <p class="tc-err">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="tc-label">City <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="city" value="{{ old('city', $authCustomer?->city) }}"
                               class="t-input" placeholder="e.g. Lahore" required
                               @error('city') style="border-color:#f87171;" @enderror>
                        @error('city') <p class="tc-err">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="tc-label">Order Notes <span class="t-muted font-normal">(optional)</span></label>
                        <textarea name="notes" rows="2" class="t-input" placeholder="Any special instructions…">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="t-card overflow-hidden">
                <div class="px-5 py-4 flex items-center gap-2.5" style="border-bottom:1px solid var(--t-border);">
                    <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                          style="background: rgb(var(--t-accent-rgb) / .12);">
                        <i class="fas fa-wallet text-sm t-accent"></i>
                    </span>
                    <h2 class="font-extrabold t-heading">Payment Method</h2>
                </div>

                <div class="p-5 space-y-3">
                    @if($codAvailable)
                    <label class="tc-pay" :class="paymentMethod === 'cash' ? 'tc-pay-on' : ''">
                        <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="mt-0.5">
                        <span class="min-w-0">
                            <span class="block font-bold text-sm">Cash on Delivery</span>
                            <span class="block text-xs t-muted mt-0.5">Pay when your order arrives at your door</span>
                        </span>
                        <i class="fas fa-money-bill-wave ml-auto mt-0.5" style="color:#22c55e;"></i>
                    </label>
                    @else
                    <div class="flex items-start gap-3 p-4" style="background:#fffbeb; border:1px solid #fcd34d; border-radius: var(--t-radius-sm);">
                        <i class="fas fa-triangle-exclamation mt-0.5 shrink-0" style="color:#f59e0b;"></i>
                        <div>
                            <div class="font-bold text-sm" style="color:#92400e;">Cash on Delivery Unavailable</div>
                            <div class="text-xs mt-1 leading-relaxed" style="color:#b45309;">
                                Cash on Delivery is not available for one or more items in your cart. You will need to pay in
                                advance to place your order. Our agent will call you to confirm your order after payment.
                            </div>
                        </div>
                    </div>
                    @endif

                    <label class="tc-pay" :class="paymentMethod === 'bank_transfer' ? 'tc-pay-on' : ''">
                        <input type="radio" name="payment_method" value="bank_transfer" x-model="paymentMethod" class="mt-0.5">
                        <span class="min-w-0">
                            <span class="block font-bold text-sm">Bank Transfer</span>
                            <span class="block text-xs t-muted mt-0.5">Transfer to our bank account and upload the receipt</span>
                        </span>
                        <i class="fas fa-building-columns ml-auto mt-0.5" style="color:#3b82f6;"></i>
                    </label>

                    <div x-show="paymentMethod === 'bank_transfer'" x-transition x-cloak class="space-y-3 pt-1">
                        @php $bankDetails = \App\Models\Setting::get('bank_details') @endphp
                        @if($bankDetails)
                        <div class="p-4" style="background:#eff6ff; border:1px solid #bfdbfe; border-radius: var(--t-radius-sm);">
                            <p class="text-sm font-bold mb-2" style="color:#1e40af;">Bank Transfer Details</p>
                            <p class="text-sm whitespace-pre-line" style="color:#1d4ed8;">{{ $bankDetails }}</p>
                        </div>
                        @endif
                        <div>
                            <label class="tc-label">Upload Payment Proof <span style="color:#ef4444;">*</span></label>
                            <input type="file" name="payment_proof" accept="image/*,.pdf" class="t-input"
                                   style="padding:.5rem; @error('payment_proof') border-color:#f87171; @enderror">
                            <p class="text-xs t-muted mt-1.5">Screenshot or photo of your bank transfer (JPG, PNG, PDF)</p>
                            @error('payment_proof') <p class="tc-err">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @if($allBankFreeDelivery)
                    <div x-show="paymentMethod !== 'bank_transfer'" x-transition
                         class="flex items-center gap-3 px-4 py-3"
                         style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius: var(--t-radius-sm);">
                        <i class="fas fa-truck shrink-0" style="color:#16a34a;"></i>
                        <p class="text-sm" style="color:#166534;">
                            <strong>Free delivery available!</strong> Switch to <strong>Bank Transfer</strong> to get free delivery on this order.
                        </p>
                    </div>
                    @endif

                    @error('payment_method') <p class="tc-err">{{ $message }}</p> @enderror
                </div>
            </div>
        </form>

        {{-- ── Summary (outside the form so coupon posts don't nest) ───── --}}
        <div>
            <div class="t-card p-5 sticky" style="top: 6rem;">
                <h2 class="font-extrabold text-lg mb-4 t-heading">Order Summary</h2>

                <div class="space-y-3 mb-4" style="max-height: 18rem; overflow-y: auto;">
                    @foreach($items as $item)
                    <div class="flex items-center gap-3">
                        <img src="{{ $item['product']->primary_image_url }}" loading="lazy" alt=""
                             class="w-12 h-12 object-contain shrink-0 p-0.5"
                             style="border-radius: var(--t-radius-sm); background:#fff; border:1px solid var(--t-border);">
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-bold truncate">{{ $item['product']->name }}</div>
                            <div class="text-xs t-muted">Qty: {{ $item['quantity'] }}</div>
                        </div>
                        <div class="text-xs font-bold shrink-0">Rs. {{ number_format($item['line_total']) }}</div>
                    </div>
                    @endforeach

                    @foreach($triggeredDeals as $deal)
                        @foreach($deal->freeProducts as $fp)
                        <div class="flex items-center gap-3 p-1.5" style="background:#f0fdf4; border-radius: var(--t-radius-sm);">
                            <img src="{{ $fp->primary_image_url }}" loading="lazy" alt=""
                                 class="w-12 h-12 object-contain shrink-0" style="border-radius: var(--t-radius-sm); background:#fff;">
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold truncate" style="color:#166534;">{{ $fp->name }}</div>
                                <div class="text-xs" style="color:#16a34a;"><i class="fas fa-gift mr-1"></i>Free — {{ $deal->name }}</div>
                            </div>
                            <div class="text-xs font-black shrink-0" style="color:#15803d;">FREE</div>
                        </div>
                        @endforeach
                    @endforeach
                </div>

                {{-- Coupon --}}
                <div class="mb-4 p-3" style="border:1px solid var(--t-border); border-radius: var(--t-radius-sm);">
                    @if($couponCode)
                    <div class="flex items-center justify-between px-3 py-2"
                         style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius: var(--t-radius-sm);">
                        <div>
                            <i class="fas fa-ticket text-xs mr-1" style="color:#16a34a;"></i>
                            <span class="text-xs font-black uppercase tracking-widest" style="color:#15803d; font-family:ui-monospace,monospace;">{{ $couponCode }}</span>
                            <span class="text-xs ml-1" style="color:#16a34a;">applied!</span>
                        </div>
                        <form method="POST" action="{{ route('cart.coupon.remove') }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs font-semibold" style="color:#ef4444;">
                                <i class="fas fa-times mr-1"></i>Remove
                            </button>
                        </form>
                    </div>
                    @else
                    @error('coupon_code')
                    <p class="text-xs mb-2" style="color:#ef4444;"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                    <form method="POST" action="{{ route('cart.coupon.apply') }}">
                        @csrf
                        <input type="text" name="coupon_code" value="{{ old('coupon_code') }}" placeholder="Coupon code"
                               class="t-input uppercase tracking-widest mb-2"
                               style="font-family:ui-monospace,monospace; @error('coupon_code') border-color:#f87171; @enderror">
                        <button type="submit" class="t-btn w-full text-sm" style="background: var(--t-text); color: var(--t-surface); padding:.55rem 1rem;">
                            Apply Coupon
                        </button>
                    </form>
                    <p class="text-xs t-muted mt-1.5"><i class="fas fa-gift mr-1"></i>Have a coupon code? Enter it here.</p>
                    @endif
                </div>

                <div class="pt-3 space-y-2 text-sm" style="border-top:1px solid var(--t-border);">
                    <div class="flex justify-between">
                        <span class="t-muted">Subtotal</span>
                        <span class="font-semibold">Rs. {{ number_format($subtotal) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="t-muted">Delivery</span>
                        @if($deliveryCharge == 0 && !$allBankFreeDelivery)
                        <span class="font-bold" style="color:#16a34a;">Free</span>
                        @elseif($allBankFreeDelivery)
                        <span>
                            <span x-show="effectiveDelivery === 0" class="font-bold flex items-center gap-1" style="color:#16a34a;">
                                <i class="fas fa-building-columns text-xs"></i> Free
                            </span>
                            <span x-show="effectiveDelivery > 0" class="font-semibold" x-text="'Rs. ' + effectiveDelivery.toLocaleString()"></span>
                        </span>
                        @else
                        <span class="font-semibold">Rs. {{ number_format($deliveryCharge) }}</span>
                        @endif
                    </div>

                    @if($couponDiscount > 0)
                    <div class="flex justify-between font-bold px-2 py-1.5" style="background:#f0fdf4; color:#15803d; border-radius: var(--t-radius-sm);">
                        <span class="flex items-center gap-1"><i class="fas fa-ticket text-xs"></i>{{ $couponCode }}</span>
                        <span>– Rs. {{ number_format($couponDiscount) }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between font-extrabold text-base pt-2" style="border-top:1px solid var(--t-border);">
                        <span>Total</span>
                        @if($allBankFreeDelivery)
                        <span class="t-price text-lg" x-text="'Rs. ' + effectiveTotal.toLocaleString()"></span>
                        @else
                        <span class="t-price text-lg">Rs. {{ number_format($total) }}</span>
                        @endif
                    </div>
                </div>

                <button type="submit" form="checkout-form" class="t-btn t-btn-primary w-full mt-5 text-base py-3.5">
                    <i class="fas fa-circle-check"></i> Place Order
                </button>

                <a href="{{ route('cart.index') }}" class="block text-center text-sm mt-3 t-muted hover:t-accent transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Cart
                </a>

                <div class="flex items-center justify-center gap-2 mt-4 text-xs t-muted">
                    <i class="fas fa-lock"></i> Your details are kept private
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .tc-label { display:block; font-size:.8125rem; font-weight:600; margin-bottom:.375rem; color: var(--t-text); }
    .tc-err   { margin-top:.375rem; font-size:.75rem; color:#ef4444; }
    .tc-pay {
        display:flex; align-items:flex-start; gap:.75rem;
        padding:1rem;
        border:2px solid var(--t-border);
        border-radius: var(--t-radius-sm);
        cursor:pointer;
        transition: border-color .18s ease, background .18s ease;
    }
    .tc-pay:hover { border-color: rgb(var(--t-accent-rgb) / .5); }
    .tc-pay input { accent-color: var(--t-accent); }
    .tc-pay-on {
        border-color: var(--t-accent) !important;
        background: rgb(var(--t-accent-rgb) / .08);
    }
</style>
@endpush

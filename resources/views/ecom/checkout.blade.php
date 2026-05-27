@extends('layouts.ecom')
@section('title', 'Checkout')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">

    <nav class="text-sm text-gray-500 mb-6 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-primary-600">Home</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="{{ route('cart.index') }}" class="hover:text-primary-600">Cart</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-800 font-medium">Checkout</span>
    </nav>

    <h1 class="text-2xl font-bold text-gray-900 mb-8">Checkout</h1>

    @if($errors->has('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first('error') }}
    </div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}" enctype="multipart/form-data"
          x-data="{ paymentMethod: '{{ old('payment_method', 'cash') }}' }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Left: Customer info + Payment --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Customer Information --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-gray-800">Contact & Delivery Information</h2>
                    </div>
                    <div class="card-body space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                       class="form-input @error('name') border-red-500 @enderror"
                                       placeholder="Your full name" required>
                                @error('name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Phone Number <span class="text-red-500">*</span></label>
                                <input type="tel" name="phone" value="{{ old('phone') }}"
                                       class="form-input @error('phone') border-red-500 @enderror"
                                       placeholder="03XX-XXXXXXX" required>
                                @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Delivery Address <span class="text-red-500">*</span></label>
                            <textarea name="address" rows="2"
                                      class="form-textarea @error('address') border-red-500 @enderror"
                                      placeholder="Street address, area, landmark" required>{{ old('address') }}</textarea>
                            @error('address') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">City <span class="text-red-500">*</span></label>
                            <input type="text" name="city" value="{{ old('city') }}"
                                   class="form-input @error('city') border-red-500 @enderror"
                                   placeholder="e.g. Lahore" required>
                            @error('city') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Order Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                            <textarea name="notes" rows="2" class="form-textarea"
                                      placeholder="Any special instructions...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Payment Method --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-gray-800">Payment Method</h2>
                    </div>
                    <div class="card-body space-y-3">

                        {{-- Cash on Delivery --}}
                        <label class="flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all"
                               :class="paymentMethod === 'cash' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" name="payment_method" value="cash" x-model="paymentMethod"
                                   class="mt-0.5 text-primary-600">
                            <div>
                                <div class="font-semibold text-gray-800 text-sm">Cash on Delivery</div>
                                <div class="text-xs text-gray-500 mt-0.5">Pay when your order arrives at your door</div>
                            </div>
                            <i class="fas fa-money-bill-wave text-green-500 ml-auto mt-0.5"></i>
                        </label>

                        {{-- Bank Transfer --}}
                        <label class="flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all"
                               :class="paymentMethod === 'bank_transfer' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" name="payment_method" value="bank_transfer" x-model="paymentMethod"
                                   class="mt-0.5 text-primary-600">
                            <div>
                                <div class="font-semibold text-gray-800 text-sm">Bank Transfer</div>
                                <div class="text-xs text-gray-500 mt-0.5">Transfer to our bank account and upload receipt</div>
                            </div>
                            <i class="fas fa-university text-blue-500 ml-auto mt-0.5"></i>
                        </label>

                        {{-- Bank transfer details --}}
                        <div x-show="paymentMethod === 'bank_transfer'" x-transition class="mt-2 space-y-3">
                            @php $bankDetails = \App\Models\Setting::get('bank_details') @endphp
                            @if($bankDetails)
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                                <p class="text-sm font-semibold text-blue-800 mb-2">Bank Transfer Details:</p>
                                <p class="text-sm text-blue-700 whitespace-pre-line">{{ $bankDetails }}</p>
                            </div>
                            @endif
                            <div>
                                <label class="form-label">Upload Payment Proof <span class="text-red-500">*</span></label>
                                <input type="file" name="payment_proof" accept="image/*,.pdf"
                                       class="form-input @error('payment_proof') border-red-500 @enderror">
                                <p class="form-hint">Upload screenshot or photo of your bank transfer (JPG, PNG, PDF)</p>
                                @error('payment_proof') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        @error('payment_method') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Right: Order Summary --}}
            <div>
                <div class="card p-5 sticky top-24">
                    <h2 class="font-bold text-gray-900 text-lg mb-4">Order Summary</h2>

                    <div class="space-y-3 mb-4">
                        @foreach($items as $item)
                        <div class="flex items-center gap-3">
                            <img src="{{ $item['product']->primary_image_url }}" class="w-12 h-12 object-cover rounded-lg bg-gray-100 shrink-0">
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium text-gray-800 truncate">{{ $item['product']->name }}</div>
                                <div class="text-xs text-gray-500">Qty: {{ $item['quantity'] }}</div>
                            </div>
                            <div class="text-xs font-semibold text-gray-700 shrink-0">
                                Rs. {{ number_format($item['line_total']) }}
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Coupon code --}}
                    <div class="mb-4 border border-gray-200 rounded-xl p-3">
                        @if($couponCode)
                            <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                                <div>
                                    <i class="fas fa-ticket-alt text-green-600 text-xs mr-1"></i>
                                    <span class="text-xs font-bold text-green-700 uppercase tracking-widest font-mono">{{ $couponCode }}</span>
                                    <span class="text-xs text-green-600 ml-1">applied!</span>
                                </div>
                                <form method="POST" action="{{ route('cart.coupon.remove') }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600 text-xs font-medium transition-colors">
                                        <i class="fas fa-times mr-1"></i>Remove
                                    </button>
                                </form>
                            </div>
                        @else
                            @error('coupon_code')
                                <p class="text-red-500 text-xs mb-2"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                            @enderror
                            <form method="POST" action="{{ route('cart.coupon.apply') }}" class="flex gap-2">
                                @csrf
                                <input type="text" name="coupon_code"
                                       value="{{ old('coupon_code') }}"
                                       placeholder="Coupon code"
                                       class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono tracking-widest uppercase @error('coupon_code') border-red-400 @enderror">
                                <button type="submit"
                                        class="shrink-0 bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                                    Apply
                                </button>
                            </form>
                            <p class="text-xs text-gray-400 mt-1.5">
                                <i class="fas fa-gift mr-1"></i>Have a coupon code? Enter it here.
                            </p>
                        @endif
                    </div>

                    <div class="border-t border-gray-200 pt-3 space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>Rs. {{ number_format($subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Delivery</span>
                            @if($deliveryCharge == 0)
                                <span class="text-green-600 font-semibold">Free</span>
                            @else
                                <span>Rs. {{ number_format($deliveryCharge) }}</span>
                            @endif
                        </div>
                        @if($couponDiscount > 0)
                        <div class="flex justify-between text-green-700 font-medium bg-green-50 rounded-lg px-2 py-1.5">
                            <span class="flex items-center gap-1">
                                <i class="fas fa-ticket-alt text-xs"></i> {{ $couponCode }}
                            </span>
                            <span>– Rs. {{ number_format($couponDiscount) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between font-bold text-gray-900 text-base border-t border-gray-200 pt-2">
                            <span>Total</span>
                            <span class="text-primary-700">Rs. {{ number_format($total) }}</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary btn-lg w-full justify-center mt-5">
                        <i class="fas fa-check-circle mr-2"></i> Place Order
                    </button>

                    <a href="{{ route('cart.index') }}" class="block text-center text-sm text-gray-500 hover:text-gray-700 mt-3">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Cart
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

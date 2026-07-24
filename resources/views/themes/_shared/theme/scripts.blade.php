{{--
    Shared storefront behaviour for every themed view: colour picker modal,
    cart toast, WhatsApp widget, announcement popup, live search and page
    transitions. Element ids, event names, routes and payloads are identical to
    the default storefront so cart and search keep working unchanged.
--}}

{{-- ===== COLOR PICKER MODAL ===== --}}
<div x-data="colorPickerModal()"
     @open-color-picker.window="open($event.detail)"
     x-show="isOpen"
     x-cloak
     style="display:none; padding:1rem;"
     class="fixed inset-0 z-[999] flex items-center justify-center">

    <div class="absolute inset-0" style="background:rgba(0,0,0,.6);" @click="close()"></div>

    <div class="relative z-10 w-full t-card"
         style="max-width:400px; margin:auto; box-shadow: var(--t-shadow-lg);"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.stop>

        <div class="flex items-center justify-between px-5 pt-5 pb-3" style="border-bottom:1px solid var(--t-border);">
            <div>
                <h3 class="font-bold text-base t-heading" x-text="productName"></h3>
                <p class="text-xs t-muted mt-0.5">Pick a colour to continue</p>
            </div>
            <button @click="close()"
                    class="w-8 h-8 flex items-center justify-center rounded-full transition-colors ml-3 shrink-0 t-muted hover:t-accent"
                    style="background: var(--t-surface-2);">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <div class="px-5 py-4 space-y-2" style="max-height:300px; overflow-y:auto;">
            <template x-for="color in colors" :key="color.id">
                <button type="button"
                        @click="selectedColorId = color.id"
                        class="w-full flex items-center gap-3 px-4 py-3 text-left transition-all"
                        style="border:2px solid var(--t-border); border-radius: var(--t-radius-sm); background: var(--t-surface);"
                        :style="selectedColorId === color.id
                            ? 'border-color: var(--t-accent); background: rgb(var(--t-accent-rgb) / .10); border-width:2px; border-style:solid; border-radius: var(--t-radius-sm);'
                            : ''">
                    <span class="w-7 h-7 rounded-full shrink-0"
                          style="border:1px solid var(--t-border);"
                          :style="color.hex_code ? `background:${color.hex_code}` : 'background: var(--t-surface-2)'"></span>
                    <span class="font-semibold text-sm" x-text="color.name"></span>
                    <span class="ml-auto text-xs t-muted shrink-0" x-text="`${color.stock_quantity} in stock`"></span>
                    <i class="fas fa-check-circle text-sm shrink-0 t-accent"
                       :style="selectedColorId === color.id ? '' : 'visibility:hidden'"></i>
                </button>
            </template>
        </div>

        <div class="px-5 pt-2" style="padding-bottom:1.75rem;">
            <p x-show="error" class="flex items-center gap-1.5 text-xs mb-3" style="color:#ef4444;">
                <i class="fas fa-exclamation-circle"></i>
                <span x-text="error"></span>
            </p>
            <button @click="addToCart()"
                    :disabled="!selectedColorId || loading"
                    class="t-btn t-btn-primary w-full">
                <i class="fas fa-spinner fa-spin" x-show="loading"></i>
                <i class="fas fa-cart-plus" x-show="!loading"></i>
                <span x-text="loading ? 'Adding...' : 'Add to Cart'"></span>
            </button>
        </div>
    </div>
</div>

{{-- ===== CART TOAST ===== --}}
<div x-data="{ show: false, message: '' }"
     @cart-toast.window="message = $event.detail.message; show = true; setTimeout(() => show = false, 3500)"
     x-show="show"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[1000] text-sm font-semibold px-5 py-3 rounded-full flex items-center gap-2 whitespace-nowrap pointer-events-none no-print"
     style="background:#111827; color:#fff; box-shadow: 0 12px 32px -8px rgba(0,0,0,.5);">
    <i class="fas fa-check-circle" style="color:#4ade80;"></i>
    <span x-text="message"></span>
</div>

{{-- ===== WHATSAPP WIDGET ===== --}}
@php
    $waNumber   = \App\Models\Setting::get('whatsapp_number');
    $waMessage  = \App\Models\Setting::get('whatsapp_message', 'Hi, I need help with an order.');
    $waGreeting = \App\Models\Setting::get('whatsapp_greeting', 'Hello! 👋 How can we help you today? Click below to chat with us on WhatsApp.');
    $waShop     = \App\Models\Setting::get('shop_name', 'MobileHub');
    $waLink     = 'https://wa.me/' . preg_replace('/\D/', '', (string) $waNumber) . '?text=' . urlencode($waMessage);
@endphp
@if($waNumber)
<div x-data="waWidget()" x-init="init()" class="fixed z-[500] no-print" style="bottom:1.5rem; right:1.5rem;">
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-3 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-3 scale-95"
         class="mb-4 w-72 rounded-2xl overflow-hidden"
         style="transform-origin: bottom right; box-shadow: 0 20px 44px -12px rgba(0,0,0,.45);">

        <div class="px-4 py-3 flex items-center gap-3" style="background:#075e54;">
            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" style="background:rgba(255,255,255,.2);">
                <i class="fab fa-whatsapp text-white text-2xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-white font-semibold text-sm truncate">{{ $waShop }}</p>
                <p class="text-xs" style="color:#9de1b5;">Typically replies instantly</p>
            </div>
            <button @click="close()" class="text-white text-xl leading-none ml-1 focus:outline-none" style="opacity:.8;">&times;</button>
        </div>

        <div class="p-4" style="background:#ece5dd;">
            <div class="relative rounded-lg px-3 py-2 text-sm max-w-[90%]"
                 style="background:#fff; color:#374151; border-top-left-radius:0; box-shadow:0 1px 2px rgba(0,0,0,.15);">
                {{ $waGreeting }}
                <span class="text-[10px] ml-2 float-right mt-1" style="color:#9ca3af;">now</span>
            </div>
        </div>

        <div class="px-4 pb-4" style="background:#ece5dd;">
            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer"
               class="flex items-center justify-center gap-2 w-full py-2.5 rounded-full text-white text-sm font-semibold transition-opacity hover:opacity-90"
               style="background:#25D366;">
                <i class="fab fa-whatsapp text-lg"></i> Start Chat
            </a>
        </div>
    </div>

    <button @click="toggle()" :class="{ 'wa-ring': !open }"
            class="w-14 h-14 rounded-full flex items-center justify-center focus:outline-none active:scale-95 transition-transform"
            style="background:#25D366; box-shadow:0 10px 26px -6px rgba(37,211,102,.6);"
            aria-label="Chat on WhatsApp">
        <i x-show="!open" class="fab fa-whatsapp text-white text-3xl"></i>
        <i x-show="open"  class="fas fa-times text-white text-2xl"></i>
    </button>
</div>

<style>
@keyframes wa-shake {
    0%, 50%, 100% { transform: rotate(0deg); }
    10%, 30%      { transform: rotate(-14deg); }
    20%, 40%      { transform: rotate(14deg); }
}
@keyframes wa-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(37,211,102,.55); }
    60%      { box-shadow: 0 0 0 14px rgba(37,211,102,0); }
}
.wa-ring { animation: wa-shake 2.4s ease-in-out infinite, wa-pulse 2.4s ease-in-out infinite; }
</style>

<script>
function waWidget() {
    return {
        open: false,
        init() {
            if (!sessionStorage.getItem('wa_closed')) {
                setTimeout(() => { this.open = true; }, 5000);
            }
        },
        toggle() {
            this.open = !this.open;
            if (!this.open) sessionStorage.setItem('wa_closed', '1');
        },
        close() {
            this.open = false;
            sessionStorage.setItem('wa_closed', '1');
        }
    }
}
</script>
@endif

{{-- ===== ANNOUNCEMENT POPUP ===== --}}
@php
    $announcementEnabled = \App\Models\Setting::get('announcement_enabled', '0') == '1';
    $announcementTitle   = \App\Models\Setting::get('announcement_title', '');
    $announcementMessage = \App\Models\Setting::get('announcement_message', '');
    $annLogo             = \App\Models\Setting::get('logo');
    $annShop             = \App\Models\Setting::get('shop_name', 'MobileHub');
@endphp
@if($announcementEnabled && $announcementMessage)
<div x-data="announcementPopup()" x-init="init()" x-show="visible" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[200] flex items-center justify-center p-4 no-print"
     style="background:rgba(0,0,0,.55); backdrop-filter:blur(2px);"
     @keydown.escape.window="close()">

    <div class="t-card w-full max-w-sm mx-auto relative"
         style="box-shadow: var(--t-shadow-lg);"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         @click.outside="close()">

        <button @click="close()"
                class="absolute top-3 right-3 w-8 h-8 rounded-full flex items-center justify-center transition-colors z-10 t-muted"
                style="background: var(--t-surface-2);">
            <i class="fas fa-times text-base"></i>
        </button>

        <div class="px-7 py-8 text-center">
            <div class="mb-4">
                @if($annLogo)
                    <img src="{{ asset('storage/'.$annLogo) }}" alt="{{ $annShop }}" class="h-12 mx-auto object-contain">
                @else
                    <div class="text-lg font-extrabold t-accent t-heading">{{ $annShop }}</div>
                @endif
            </div>

            @if($announcementTitle)
            <h2 class="text-xl font-extrabold mb-3 t-heading">{{ $announcementTitle }}</h2>
            @endif

            <p class="text-sm leading-relaxed t-muted">{{ $announcementMessage }}</p>

            <button @click="close()" class="t-btn t-btn-primary mt-6 px-8 mx-auto">Got it!</button>
        </div>
    </div>
</div>

<script>
function announcementPopup() {
    return {
        visible: false,
        key: 'announcement_seen_{{ md5($announcementTitle . $announcementMessage) }}',
        init() {
            if (!sessionStorage.getItem(this.key)) {
                setTimeout(() => { this.visible = true; }, 600);
            }
        },
        close() {
            this.visible = false;
            sessionStorage.setItem(this.key, '1');
        },
    };
}
</script>
@endif

{{-- ===== SHARED BEHAVIOUR ===== --}}
<script>
// Live search — same endpoint and payload as the default storefront.
function liveSearch() {
    return {
        query: '',
        results: [],
        open: false,
        async search() {
            if (this.query.length < 2) { this.results = []; return; }
            const res = await fetch(`/api/products/search?q=${encodeURIComponent(this.query)}`);
            this.results = await res.json();
            this.open = true;
        }
    }
}

function colorPickerModal() {
    return {
        isOpen:          false,
        productId:       null,
        productName:     '',
        colors:          [],
        selectedColorId: null,
        loading:         false,
        error:           '',

        open(detail) {
            this.productId       = detail.productId;
            this.productName     = detail.productName;
            this.colors          = detail.colors;
            this.selectedColorId = detail.colors.length === 1 ? detail.colors[0].id : null;
            this.loading         = false;
            this.error           = '';
            this.isOpen          = true;
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
        },

        async addToCart() {
            if (!this.selectedColorId || this.loading) return;
            this.loading = true;
            this.error   = '';

            const form = new FormData();
            form.append('product_id', this.productId);
            form.append('color_id',   this.selectedColorId);
            form.append('quantity',   1);
            form.append('_token',     document.querySelector('meta[name="csrf-token"]').content);

            try {
                const res  = await fetch('{{ route("cart.add") }}', {
                    method:  'POST',
                    headers: { 'Accept': 'application/json' },
                    body:    form,
                });
                const data = await res.json();

                if (res.ok) {
                    const badge = document.getElementById('cart-count-badge');
                    if (badge) {
                        badge.textContent   = data.count > 9 ? '9+' : data.count;
                        badge.style.display = data.count > 0 ? '' : 'none';
                    }
                    window.dispatchEvent(new CustomEvent('cart-toast', { detail: { message: this.productName + ' added to cart!' } }));
                    this.close();
                } else {
                    this.error = (data.errors && data.errors.color && data.errors.color[0])
                        || data.message
                        || 'Could not add to cart.';
                }
            } catch (e) {
                this.error = 'Something went wrong. Please try again.';
            } finally {
                this.loading = false;
            }
        },
    };
}

// Scroll reveal
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    document.querySelectorAll('[data-reveal-grid]').forEach(function (grid) {
        Array.from(grid.children).forEach(function (child, i) {
            child.classList.add('reveal');
            child.style.transitionDelay = Math.min(i * 0.06, 0.42) + 's';
        });
    });

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -48px 0px' });

    document.querySelectorAll('.reveal').forEach(function (el) { observer.observe(el); });
}());

// Page transition — skips modifier clicks, external links and new tabs.
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var leaving = false;
    document.addEventListener('click', function (e) {
        if (leaving) return;
        var a = e.target.closest('a[href]');
        if (!a) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript') === 0) return;
        if (a.target === '_blank' || a.target === '_parent') return;
        try {
            var url = new URL(href, location.href);
            if (url.hostname !== location.hostname) return;
        } catch (ex) { return; }
        e.preventDefault();
        leaving = true;
        var main = document.getElementById('page-main');
        if (main) {
            main.style.transition = 'opacity .2s ease, transform .2s ease';
            main.style.opacity    = '0';
            main.style.transform  = 'translateY(-10px)';
        }
        setTimeout(function () { location.href = href; }, 200);
    });
}());
</script>

@stack('scripts')

{{-- Themed flash messages — same session keys as the default storefront. --}}
@if(session('success'))
<div class="fixed top-20 right-4 z-[800] max-w-sm no-print" x-data x-init="setTimeout(() => $el.remove(), 4000)">
    <div class="flex items-start gap-3 p-4 t-card" style="border-left: 4px solid #16a34a;">
        <i class="fas fa-check-circle shrink-0 mt-0.5" style="color:#16a34a;"></i>
        <span class="text-sm">{{ session('success') }}</span>
    </div>
</div>
@endif

@if(session('error') || $errors->any())
<div class="fixed top-20 right-4 z-[800] max-w-sm no-print" x-data x-init="setTimeout(() => $el.remove(), 5000)">
    <div class="flex items-start gap-3 p-4 t-card" style="border-left: 4px solid #dc2626;">
        <i class="fas fa-exclamation-circle shrink-0 mt-0.5" style="color:#dc2626;"></i>
        <span class="text-sm">{{ session('error') ?: $errors->first() }}</span>
    </div>
</div>
@endif

@php
    use App\Support\EcomTheme;

    $pvSlug = $ecomThemePreview ?? null;
@endphp
@if($pvSlug)
@php
    $pvMeta    = EcomTheme::meta($pvSlug);
    $pvName    = $pvMeta['name'] ?? 'Current storefront';
    $pvApplied = EcomTheme::applied();
    $pvIsLive  = $pvApplied === $pvSlug;
@endphp
<div x-data="{ open: true }" x-show="open" x-cloak
     class="fixed inset-x-0 bottom-0 z-[900] no-print"
     style="font-family: ui-sans-serif, system-ui, sans-serif;">
    <div class="mx-auto max-w-4xl m-3 rounded-2xl shadow-2xl overflow-hidden"
         style="background:#0f172a; color:#e2e8f0; border:1px solid #1e293b;">

        <div class="flex flex-wrap items-center gap-3 px-4 py-3">
            <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full shrink-0"
                  style="background:#f59e0b; color:#1f2937;">
                <i class="fas fa-eye"></i> Preview
            </span>

            <div class="min-w-0 flex-1">
                <div class="text-sm font-semibold truncate">{{ $pvName }}</div>
                <div class="text-[11px]" style="color:#94a3b8;">
                    Only you can see this. Customers still see
                    {{ EcomTheme::meta($pvApplied)['name'] ?? 'the current storefront' }}.
                </div>
            </div>

            {{-- Jump between views without leaving the page --}}
            <div class="flex items-center gap-1.5 flex-wrap">
                @foreach(EcomTheme::all() as $slug => $meta)
                <a href="{{ request()->fullUrlWithQuery([EcomTheme::PREVIEW_PARAM => $slug]) }}"
                   class="w-7 h-7 rounded-full border-2 transition-transform hover:scale-110"
                   title="Preview {{ $meta['name'] }}"
                   style="background: {{ $meta['primary'] }}; border-color: {{ $slug === $pvSlug ? '#f59e0b' : 'rgba(255,255,255,.25)' }};"></a>
                @endforeach
                <a href="{{ request()->fullUrlWithQuery([EcomTheme::PREVIEW_PARAM => EcomTheme::CLASSIC]) }}"
                   class="w-7 h-7 rounded-full border-2 flex items-center justify-center text-[10px] font-bold transition-transform hover:scale-110"
                   title="Preview the current storefront"
                   style="background:#475569; color:#e2e8f0; border-color: {{ $pvSlug === EcomTheme::CLASSIC ? '#f59e0b' : 'rgba(255,255,255,.25)' }};">
                    <i class="fas fa-rotate-left"></i>
                </a>
            </div>

            <div class="flex items-center gap-2 ml-auto">
                @if(EcomTheme::exists($pvSlug) && !$pvIsLive)
                <form method="POST" action="{{ route('admin.settings.theme.apply') }}">
                    @csrf
                    <input type="hidden" name="theme" value="{{ $pvSlug }}">
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-sm font-bold transition-opacity hover:opacity-90"
                            style="background:#22c55e; color:#04240f;">
                        <i class="fas fa-check mr-1.5"></i>Apply to store
                    </button>
                </form>
                @elseif($pvIsLive)
                <span class="px-3 py-2 rounded-xl text-xs font-bold" style="background:#166534; color:#bbf7d0;">
                    <i class="fas fa-circle-check mr-1"></i>Live
                </span>
                @endif

                <a href="{{ request()->fullUrlWithQuery([EcomTheme::PREVIEW_PARAM => 'off']) }}"
                   class="px-3 py-2 rounded-xl text-sm font-medium transition-colors"
                   style="background:#1e293b; color:#cbd5e1;">
                    Exit
                </a>

                <a href="{{ route('admin.settings.index') }}#storefront-view"
                   class="px-3 py-2 rounded-xl text-sm font-medium transition-colors hidden sm:inline-block"
                   style="background:#1e293b; color:#cbd5e1;" title="Back to settings">
                    <i class="fas fa-sliders"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endif

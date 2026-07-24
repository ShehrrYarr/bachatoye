{{-- $crumbs: array of [label, url|null] — the last entry renders as current page. --}}
<nav class="text-xs md:text-sm mb-5 flex items-center gap-2 flex-wrap t-muted" aria-label="Breadcrumb">
    <a href="{{ route('home') }}" class="hover:t-accent transition-colors">Home</a>
    @foreach($crumbs as [$label, $url])
    <i class="fas fa-chevron-right text-[9px]" style="opacity:.6;"></i>
    @if($url && !$loop->last)
    <a href="{{ $url }}" class="hover:t-accent transition-colors">{{ $label }}</a>
    @else
    <span class="font-semibold" style="color: var(--t-text);">{{ $label }}</span>
    @endif
    @endforeach
</nav>

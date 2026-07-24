{{-- Shared empty state. $icon, $title, $text, optional $ctaUrl + $ctaText. --}}
<div class="text-center py-16 md:py-20 t-card">
    <span class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center"
          style="background: var(--t-surface-2);">
        <i class="fas fa-{{ $icon ?? 'box-open' }} text-2xl t-muted"></i>
    </span>
    <p class="font-bold text-lg t-heading">{{ $title }}</p>
    @if(!empty($text))
    <p class="text-sm t-muted mt-1.5 max-w-sm mx-auto px-4">{{ $text }}</p>
    @endif
    @if(!empty($ctaUrl))
    <a href="{{ $ctaUrl }}" class="t-btn t-btn-primary mt-6">{{ $ctaText ?? 'Continue' }}</a>
    @endif
</div>

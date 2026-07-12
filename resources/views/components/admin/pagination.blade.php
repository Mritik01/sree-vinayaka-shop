@props(['paginator'])
@if ($paginator->hasPages())
    <div class="flex items-center justify-between px-5 py-4 border-t border-gold-100 flex-wrap gap-3">
        <p class="text-xs text-maroon-400">
            {{ __('Showing') }} {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} {{ __('of') }} {{ $paginator->total() }}
        </p>
        <div class="flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1.5 rounded-lg text-sm text-maroon-300 border border-gold-100">‹ {{ __('Prev') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-sm text-maroon-600 border border-gold-200/60 hover:border-gold-400 transition">‹ {{ __('Prev') }}</a>
            @endif

            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                <a href="{{ $url }}"
                   class="px-3 py-1.5 rounded-lg text-sm border transition {{ $page === $paginator->currentPage() ? 'bg-maroon-700 text-cream border-maroon-700' : 'text-maroon-600 border-gold-200/60 hover:border-gold-400' }}">
                    {{ $page }}
                </a>
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-sm text-maroon-600 border border-gold-200/60 hover:border-gold-400 transition">{{ __('Next') }} ›</a>
            @else
                <span class="px-3 py-1.5 rounded-lg text-sm text-maroon-300 border border-gold-100">{{ __('Next') }} ›</span>
            @endif
        </div>
    </div>
@endif

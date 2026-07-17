{{-- Mobile-only persistent bottom tab bar — gives mobile users a always-visible way to reach
     Home/Shop/Categories/Account without digging through the hamburger menu. Cart intentionally
     isn't a tab here — it's already the floating "View Cart" pill above this bar. --}}
@php
    $isProductsIndex = request()->routeIs('products.index');
    $tabs = [
        [
            'href' => url('/'),
            'label' => __('Home'),
            'active' => request()->is('/'),
            'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        ],
        [
            'href' => route('products.index'),
            'label' => __('Shop All'),
            'active' => $isProductsIndex,
            'icon' => 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.06.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
        ],
    ];
@endphp
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-50 h-16 bg-cream/95 backdrop-blur border-t border-gold-300/40 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] flex items-stretch"
     aria-label="{{ __('Primary') }}">
    @foreach ($tabs as $tab)
        <a href="{{ $tab['href'] }}"
           class="flex-1 flex flex-col items-center justify-center gap-0.5 transition {{ $tab['active'] ? 'text-maroon-700' : 'text-maroon-400' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $tab['active'] ? 2.1 : 1.7 }}">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}" />
            </svg>
            <span class="text-[11px] font-medium leading-none">{{ $tab['label'] }}</span>
        </a>
    @endforeach

    {{-- opens the mobile category panel as an overlay (partials/category-panel.blade.php)
         instead of navigating — same instant-feel as the auth modal/cart drawer, and avoids a
         page reload just to browse categories --}}
    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-category-panel'))"
            class="flex-1 flex flex-col items-center justify-center gap-0.5 transition text-maroon-400">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
        </svg>
        <span class="text-[11px] font-medium leading-none">{{ __('Categories') }}</span>
    </button>

    <a href="{{ route('account') }}"
       onclick="if (!window.__mbIsLoggedIn) { event.preventDefault(); window.dispatchEvent(new CustomEvent('open-auth-modal')); }"
       class="flex-1 flex flex-col items-center justify-center gap-0.5 transition {{ request()->routeIs('account') ? 'text-maroon-700' : 'text-maroon-400' }}">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('account') ? 2.1 : 1.7 }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        <span class="text-[11px] font-medium leading-none">{{ __('Account') }}</span>
    </a>
</nav>

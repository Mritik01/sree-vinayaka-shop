@extends('admin.layout')

@section('title', 'Support Chat')
@section('page-title', __('Support Chat'))

@section('content')
    <div x-data='adminSupportInbox(@json($conversations))'>

        <div x-show="conversations.length === 0" x-cloak class="bg-white rounded-2xl border border-gold-200/60 p-10 text-center">
            <p class="text-4xl mb-3">💬</p>
            <p class="font-display text-maroon-800">{{ __('No support conversations yet') }}</p>
            <p class="text-sm text-maroon-400 mt-1">{{ __('When a customer taps "Help" on their order page, the chat will show up here.') }}</p>
        </div>

        <div x-show="conversations.length > 0" x-cloak>
            <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
                <p class="text-sm text-maroon-500">
                    <span x-text="filteredConversations().length"></span> {{ __('of') }} <span x-text="conversations.length"></span> {{ __('conversation(s)') }}
                </p>
                <div class="relative">
                    <input type="search" x-model="search" @input="page = 1"
                           placeholder="{{ __('Search by name or order #…') }}"
                           class="w-56 sm:w-72 rounded-lg border border-gold-300/70 bg-white pl-9 pr-3 py-2 text-sm text-maroon-800 placeholder-maroon-400/60 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-maroon-300 text-sm pointer-events-none">🔎</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden divide-y divide-gold-100">
                <p x-show="filteredConversations().length === 0" x-cloak class="text-maroon-400 text-sm px-5 py-8 text-center">
                    {{ __('No conversations match') }} "<span x-text="search"></span>".
                </p>

                <template x-for="c in pagedConversations()" :key="c.order_id">
                    {{-- opens the floating widget's thread view instead of navigating to a full
                         page — replying no longer means leaving this list behind. This row is a
                         <div> (not a <button>) specifically so the delete button below can be a
                         real sibling <button> rather than invalid button-inside-button markup. --}}
                    <div role="button" tabindex="0"
                         @click="window.dispatchEvent(new CustomEvent('open-support-widget', { detail: { orderId: c.order_id, summary: c } }))"
                         @keydown.enter="window.dispatchEvent(new CustomEvent('open-support-widget', { detail: { orderId: c.order_id, summary: c } }))"
                         class="w-full flex items-center gap-4 px-5 py-4 hover:bg-cream/50 transition text-left cursor-pointer"
                         :class="c.unread > 0 && 'bg-gold-50/60'">
                        {{-- avatar: customer's initial --}}
                        <span class="w-11 h-11 shrink-0 rounded-full bg-gradient-to-br from-maroon-600 to-maroon-800 text-cream font-display font-bold grid place-items-center text-lg"
                              x-text="(c.customer_name || '?').trim().charAt(0).toUpperCase()"></span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-maroon-800 truncate" x-text="c.customer_name"></p>
                                <span class="text-xs text-maroon-400 font-mono shrink-0" x-text="c.order_number"></span>
                            </div>
                            <p class="text-sm mt-0.5 truncate"
                               :class="c.unread > 0 ? 'text-maroon-800 font-semibold' : 'text-maroon-500'">
                                <span x-show="c.last_sender === 'admin'" class="text-maroon-400 font-normal">{{ __('You:') }} </span><span x-text="c.snippet"></span>
                            </p>
                        </div>

                        <div class="shrink-0 text-right space-y-1.5">
                            <p class="text-[11px] text-maroon-400" x-text="c.last_at"></p>
                            <span x-show="c.unread > 0" x-cloak
                                  class="inline-flex min-w-[20px] h-5 px-1.5 rounded-full bg-red-600 text-white text-[11px] font-bold items-center justify-center"
                                  x-text="c.unread > 9 ? '9+' : c.unread"></span>
                        </div>

                        <button type="button" @click.stop="deleteConversation(c.order_id, c.customer_name)"
                                aria-label="{{ __('Delete conversation') }}"
                                class="shrink-0 w-8 h-8 rounded-lg grid place-items-center text-maroon-300 hover:text-red-600 hover:bg-red-50 transition">
                            🗑️
                        </button>
                    </div>
                </template>

                {{-- client-side pagination — the whole (already-searched) list lives in memory
                     already, so this is just a slice + page controls, no extra round-trip.
                     Mirrors the look of <x-admin.pagination> used on the server-paginated
                     tables elsewhere in the admin, and hides itself the same way when there's
                     only one page. --}}
                <div x-show="totalPages() > 1" x-cloak class="flex items-center justify-between px-5 py-4 border-t border-gold-100 flex-wrap gap-3">
                    <p class="text-xs text-maroon-400">
                        {{ __('Showing') }} <span x-text="pageFirstItem()"></span>–<span x-text="pageLastItem()"></span> {{ __('of') }} <span x-text="filteredConversations().length"></span>
                    </p>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="goToPage(page - 1)" :disabled="page === 1"
                                class="px-3 py-1.5 rounded-lg text-sm border transition"
                                :class="page === 1 ? 'text-maroon-300 border-gold-100 cursor-default' : 'text-maroon-600 border-gold-200/60 hover:border-gold-400'">
                            ‹ {{ __('Prev') }}
                        </button>

                        <template x-for="p in pageRange()" :key="p">
                            <button type="button" @click="goToPage(p)"
                                    class="px-3 py-1.5 rounded-lg text-sm border transition"
                                    :class="p === page ? 'bg-maroon-700 text-cream border-maroon-700' : 'text-maroon-600 border-gold-200/60 hover:border-gold-400'"
                                    x-text="p"></button>
                        </template>

                        <button type="button" @click="goToPage(page + 1)" :disabled="page === totalPages()"
                                class="px-3 py-1.5 rounded-lg text-sm border transition"
                                :class="page === totalPages() ? 'text-maroon-300 border-gold-100 cursor-default' : 'text-maroon-600 border-gold-200/60 hover:border-gold-400'">
                            {{ __('Next') }} ›
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

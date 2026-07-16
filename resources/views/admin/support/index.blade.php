@extends('admin.layout')

@section('title', 'Support Chat')
@section('page-title', __('Support Chat'))

@section('content')
    <div class="max-w-4xl mx-auto" x-data='adminSupportInbox(@json($conversations))'>

        <div x-show="conversations.length === 0" x-cloak class="bg-white rounded-2xl border border-gold-200/60 p-10 text-center">
            <p class="text-4xl mb-3">💬</p>
            <p class="font-display text-maroon-800">{{ __('No support conversations yet') }}</p>
            <p class="text-sm text-maroon-400 mt-1">{{ __('When a customer taps "Help" on their order page, the chat will show up here.') }}</p>
        </div>

        <div x-show="conversations.length > 0" x-cloak class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden divide-y divide-gold-100">
            <template x-for="c in conversations" :key="c.order_id">
                <a :href="`/admin/support/${c.order_id}`" class="flex items-center gap-4 px-5 py-4 hover:bg-cream/50 transition"
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
                </a>
            </template>
        </div>
    </div>
@endsection

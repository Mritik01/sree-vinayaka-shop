@extends('admin.layout')

@section('title', 'Chat — ' . $order->orderNumber())
@section('page-title', __('Support Chat'))

@section('content')
    {{-- -m-8 cancels the admin layout's <main class="p-8"> so this pane runs flush against the
         header and both edges — a chat thread reads as cramped inside the usual padded card --}}
    <div class="-m-8 h-[calc(100vh-4.375rem)] flex flex-col" x-data='adminSupportThread({{ $order->id }}, @json($messagesForJs))'>

        {{-- thread header: back + who + which order, with quick jumps --}}
        <div class="shrink-0 px-5 py-3 border-b border-gold-100 bg-cream/60 flex items-center gap-3 flex-wrap">
            <a href="{{ route('admin.support.index') }}" aria-label="{{ __('All conversations') }}"
               class="shrink-0 w-9 h-9 grid place-items-center rounded-full text-maroon-600 hover:bg-white hover:text-maroon-800 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            </a>
            <span class="w-10 h-10 shrink-0 rounded-full bg-gradient-to-br from-maroon-600 to-maroon-800 text-cream font-display font-bold grid place-items-center">
                {{ strtoupper(mb_substr(trim($order->customer_name), 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-maroon-800 leading-tight">{{ $order->customer_name }}</p>
                <p class="text-xs text-maroon-400 mt-0.5">
                    {{ $order->orderNumber() }} · ₹{{ number_format($order->total) }} · <x-admin.status-badge :status="$order->status" />
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="tel:{{ $order->customer_phone }}"
                   class="text-xs font-semibold text-maroon-700 border border-gold-300/70 hover:border-gold-400 hover:bg-white rounded-lg px-3 py-2 transition">📞 {{ __('Call') }}</a>
                <a href="{{ route('admin.orders.show', $order) }}"
                   class="text-xs font-semibold text-maroon-700 border border-gold-300/70 hover:border-gold-400 hover:bg-white rounded-lg px-3 py-2 transition">🧾 {{ __('View Order') }}</a>
            </div>
        </div>

            {{-- messages --}}
            <div x-ref="thread" class="flex-1 overflow-y-auto overscroll-contain px-5 py-4 bg-ivory">
                <div x-show="messages.length === 0" x-cloak class="h-full flex flex-col items-center justify-center text-center">
                    <span class="text-4xl">💬</span>
                    <p class="text-sm text-maroon-400 mt-2">{{ __('No messages yet — say hello!') }}</p>
                </div>

                <template x-for="m in messages" :key="m.id">
                    <div>
                        <div x-show="m.showDay" class="text-center my-3">
                            <span class="text-[10px] font-semibold text-maroon-400 bg-cream border border-gold-200/60 rounded-full px-3 py-1" x-text="m.day"></span>
                        </div>
                        {{-- mirror of the customer view: admin's own messages on the right --}}
                        <div class="flex items-end gap-2 mt-2" :class="m.sender === 'admin' ? 'justify-end' : 'justify-start'">
                            <span x-show="m.sender === 'customer'"
                                  class="w-7 h-7 shrink-0 rounded-full bg-maroon-100 border border-maroon-400/30 grid place-items-center text-[11px] font-bold text-maroon-700 mb-4"
                                  x-text="{{ json_encode(strtoupper(mb_substr(trim($order->customer_name), 0, 1))) }}"></span>
                            <div class="max-w-[75%]">
                                <a x-show="m.image_url" x-cloak :href="m.image_url" target="_blank" rel="noopener"
                                   class="block max-w-[220px] rounded-2xl overflow-hidden border border-gold-200/60 shadow-sm mb-1">
                                    <img :src="m.image_url" class="w-full h-auto object-cover" loading="lazy" alt="">
                                </a>
                                <div x-show="m.message" x-cloak
                                     class="px-3.5 py-2.5 text-sm leading-relaxed whitespace-pre-line break-words shadow-sm"
                                     :class="m.sender === 'admin'
                                         ? 'bg-gradient-to-br from-maroon-700 to-maroon-800 text-cream rounded-2xl rounded-br-md'
                                         : 'bg-white text-maroon-800 border border-gold-200/70 rounded-2xl rounded-bl-md'"
                                     x-text="m.message"></div>
                                <p class="text-[10px] text-maroon-400 mt-1 px-1" :class="m.sender === 'admin' && 'text-right'" x-text="m.time"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- composer --}}
            <div class="shrink-0 border-t border-gold-100 bg-white px-4 py-3">
                <p x-show="sendError" x-cloak class="text-red-600 text-xs mb-2 px-1" x-text="sendError"></p>

                {{-- pending photo preview — shown above the input once one's picked, before send --}}
                <div x-show="pendingImagePreview" x-cloak class="relative inline-block mb-2 ml-1">
                    <img :src="pendingImagePreview" class="h-16 w-16 object-cover rounded-xl border border-gold-300/60 shadow-sm">
                    <button @click="clearPendingImage()" type="button" aria-label="{{ __('Remove photo') }}"
                            class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-maroon-800 text-cream text-xs grid place-items-center shadow">✕</button>
                </div>

                <div class="flex items-center gap-2">
                    <input x-ref="imageInput" type="file" accept="image/*" class="hidden" @change="onImageSelected($event)">
                    <button @click="$refs.imageInput.click()" type="button" aria-label="{{ __('Attach a photo') }}"
                            class="shrink-0 w-10 h-10 rounded-full grid place-items-center text-maroon-500 hover:text-maroon-700 hover:bg-cream transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 4.5h16.5a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6a1.5 1.5 0 0 1 1.5-1.5Zm10.125 3.375a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </button>
                    <input x-ref="threadInput" x-model="draft" @keydown.enter.prevent="send()"
                           type="text" maxlength="1000" placeholder="{{ __('Reply to') }} {{ Str::of($order->customer_name)->before(' ') }}…"
                           class="flex-1 min-w-0 rounded-full border border-gold-300/70 bg-cream/50 px-4 py-2.5 text-sm text-maroon-800 placeholder-maroon-400/60 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                    <button @click="send()" :disabled="sending || (!draft.trim() && !pendingImage)" type="button" aria-label="{{ __('Send message') }}"
                            class="shrink-0 w-11 h-11 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 text-maroon-900 grid place-items-center shadow-md hover:shadow-lg active:scale-90 transition disabled:opacity-40 disabled:shadow-none">
                        <svg class="w-5 h-5 translate-x-[1px]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
                        </svg>
                    </button>
                </div>
            </div>
    </div>
@endsection

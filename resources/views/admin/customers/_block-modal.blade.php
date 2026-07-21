{{-- Block Customer modal — opened from the customer list or the customer detail page via the
     global `open-block-modal` event, same idiom as _notify-modal.blade.php --}}
<div x-data='blockCustomerModal(@json(\App\Models\User::BLOCK_REASONS), @json(\App\Models\User::DEFAULT_BLOCK_MESSAGES))' x-show="open" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4">
    <div x-show="open" class="absolute inset-0 bg-maroon-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="!sending && (open = false)"></div>

    <div x-show="open" class="relative bg-cream rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden max-h-[90vh] flex flex-col"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
        <div class="relative px-6 py-5 bg-gradient-to-r from-red-500 to-maroon-700 overflow-hidden shrink-0">
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 16px 16px;"></div>
            <p class="relative font-display font-bold text-lg text-white">🚫 <span x-text="`Block ${targetName}`"></span></p>
            <p class="relative text-white/80 text-xs mt-0.5">They'll immediately lose access to ordering, cart, checkout &amp; coupons.</p>
        </div>

        <div class="p-6 overflow-y-auto">
            <template x-if="!blocked">
                <form @submit.prevent="submit()" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-2">Reason</label>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="[key, label] in Object.entries(reasons)" :key="key">
                                <button type="button" @click="selectReason(key)"
                                        class="text-left text-sm px-3 py-2.5 rounded-xl border-2 transition"
                                        :class="reason === key ? 'bg-red-50 border-red-400 text-red-700 font-semibold' : 'bg-white border-gold-200/70 text-maroon-600 hover:border-gold-400'">
                                    <span x-text="key === 'other' ? '✍️' : '🚫'"></span> <span x-text="label"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide">Message to Customer</label>
                            <span class="text-[11px] text-maroon-300" x-text="`${message.length}/1000`"></span>
                        </div>
                        <textarea x-model="message" maxlength="1000" rows="3" placeholder="This is shown to the customer on their restricted-account screen…"
                                  class="w-full rounded-lg border border-gold-300/70 bg-white px-3 py-2.5 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition resize-none"></textarea>
                        <p class="text-[11px] text-maroon-400 mt-1">A clear default is filled in per reason — feel free to edit it, just keep it easy for the customer to understand.</p>
                    </div>

                    {{-- live preview of what the customer will actually see --}}
                    <div x-show="message.trim()" x-cloak class="rounded-xl bg-gold-50 border border-gold-200 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gold-600 mb-1">👁️ Preview — what the customer sees</p>
                        <p class="text-sm text-maroon-700 leading-relaxed">&ldquo;<span x-text="message"></span>&rdquo;</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Internal Notes <span class="font-normal normal-case text-maroon-400">(optional, never shown to the customer)</span></label>
                        <textarea x-model="notes" maxlength="1000" rows="2" placeholder="Any extra context for your team…"
                                  class="w-full rounded-lg border border-gold-300/70 bg-white px-3 py-2.5 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition resize-none"></textarea>
                    </div>

                    <p x-show="error" x-cloak x-text="error" class="text-xs text-red-600 font-medium"></p>

                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" :disabled="sending || !reason"
                                class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl py-2.5 transition text-sm disabled:opacity-50">
                            <span x-show="!sending">🚫 Block Customer</span>
                            <span x-show="sending" x-cloak>Blocking…</span>
                        </button>
                        <button type="button" @click="open = false" :disabled="sending"
                                class="px-5 bg-white border border-gold-300/70 hover:bg-gold-50 text-maroon-600 font-semibold rounded-xl py-2.5 transition text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </template>

            <template x-if="blocked">
                <div class="text-center py-4">
                    <p class="text-4xl">🚫</p>
                    <p class="font-display text-maroon-800 mt-2" x-text="`${targetName} has been blocked`"></p>
                    <p class="text-xs text-maroon-400 mt-1">They'll see the restricted-account screen the moment they next try to log in or take any restricted action.</p>
                </div>
            </template>
        </div>
    </div>
</div>

{{-- shared send-notification modal — opened from grid rows, the "Notify All" toolbar
     button, or the customer detail page via the global `open-notify-modal` event --}}
<div x-data="notifyCustomerModal()" x-show="open" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4">
    <div x-show="open" class="absolute inset-0 bg-maroon-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="open = false"></div>

    <div x-show="open" class="relative bg-cream rounded-2xl shadow-2xl w-full max-w-md overflow-hidden"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
        <div class="relative px-6 py-5 bg-gradient-to-r from-gold-400 to-gold-600 overflow-hidden">
            <div class="absolute inset-0 opacity-25" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 16px 16px;"></div>
            <p class="relative font-display font-bold text-lg text-maroon-900">
                🔔 <span x-text="toAll ? 'Notify All Customers' : `Notify ${targetName}`"></span>
            </p>
            <p x-show="toAll" x-cloak class="relative text-maroon-800/70 text-xs mt-0.5">This message will be sent to every registered customer.</p>
        </div>

        <div class="p-6">
            <template x-if="!sent">
                <form @submit.prevent="send()" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Title</label>
                        <input type="text" x-model="title" maxlength="100" placeholder="e.g. Diwali special offer 🪔"
                               class="w-full rounded-lg border border-gold-300/70 bg-white px-3 py-2.5 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Message</label>
                        <textarea x-model="message" maxlength="500" rows="4" placeholder="Write your message to the customer…"
                                  class="w-full rounded-lg border border-gold-300/70 bg-white px-3 py-2.5 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition resize-none"></textarea>
                        <p class="text-[11px] text-maroon-300 mt-1 text-right" x-text="`${message.length}/500`"></p>
                    </div>

                    <p x-show="error" x-cloak x-text="error" class="text-xs text-red-600 font-medium"></p>

                    <div class="flex items-center gap-3">
                        <button type="submit" :disabled="sending"
                                class="flex-1 bg-maroon-700 hover:bg-maroon-800 text-cream font-semibold rounded-xl py-2.5 transition text-sm disabled:opacity-60">
                            <span x-show="!sending">📨 Send Notification</span>
                            <span x-show="sending" x-cloak>Sending…</span>
                        </button>
                        <button type="button" @click="open = false"
                                class="px-5 bg-white border border-gold-300/70 hover:bg-gold-50 text-maroon-600 font-semibold rounded-xl py-2.5 transition text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </template>

            <template x-if="sent">
                <div class="text-center py-4">
                    <p class="text-4xl animate-bounce">✅</p>
                    <p class="font-display text-maroon-800 mt-2">Notification sent!</p>
                    <p class="text-xs text-maroon-400 mt-1" x-text="toAll ? `Delivered to ${sentCount} customer${sentCount === 1 ? '' : 's'}.` : `${targetName} will see it in their notification bell.`"></p>
                </div>
            </template>
        </div>
    </div>
</div>

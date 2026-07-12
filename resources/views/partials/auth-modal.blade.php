{{-- Login / Sign Up modal — visibility controlled by `authOpen` in the parent x-data; unified phone+OTP flow (no tabs) owned by authModal() below --}}
<div x-data="authModal()"
     x-show="authOpen"
     x-cloak
     @keydown.escape.window="authOpen = false"
     class="fixed inset-0 z-[120] flex items-center justify-center p-4 sm:p-6">

    {{-- backdrop --}}
    <div x-show="authOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="authOpen = false"
         class="absolute inset-0 bg-maroon-900/70 backdrop-blur-sm"></div>

    {{-- modal panel --}}
    <div x-show="authOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative w-full max-w-4xl max-h-[90vh] overflow-y-auto sm:overflow-hidden bg-ivory rounded-2xl shadow-2xl grid grid-cols-1 md:grid-cols-2 border border-gold-300/50">

        {{-- close button --}}
        <button @click="authOpen = false" aria-label="Close"
            class="absolute top-4 right-4 z-10 w-9 h-9 rounded-full bg-white/70 hover:bg-white text-maroon-800 flex items-center justify-center shadow-sm transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- left: image panel (hidden on mobile to keep the modal compact) — crossfades between form/otp photos --}}
        <div class="hidden md:block relative overflow-hidden">
            <img src="{{ asset('images/hero/hero-4.jpg') }}" alt="Makhanbhog Sweets — freshly made every morning"
                 x-show="step === 'form'"
                 x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute inset-0 w-full h-full object-cover [object-position:62%_12%]">
            <img src="{{ asset('images/hero/hero-5.jpg') }}" alt="Customer at Makhanbhog Sweets"
                 x-show="step === 'otp'" x-cloak
                 x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute inset-0 w-full h-full object-cover [object-position:78%_18%]">

            <div class="absolute inset-0 bg-gradient-to-t from-maroon-900/85 via-maroon-900/20 to-maroon-900/10"></div>

            <div class="relative z-10 h-full flex flex-col justify-end p-8">
                <template x-if="step === 'form'">
                    <div>
                        <p class="text-gold-300 font-semibold tracking-widest uppercase text-xs mb-2">Makhanbhog Sweets</p>
                        <h3 class="font-display text-2xl font-bold text-cream leading-snug">Welcome</h3>
                        <p class="text-gold-100/80 text-sm mt-2">Sign in or create your account in seconds — no password needed.</p>
                    </div>
                </template>
                <template x-if="step === 'otp'">
                    <div>
                        <p class="text-gold-300 font-semibold tracking-widest uppercase text-xs mb-2">Almost There</p>
                        <h3 class="font-display text-2xl font-bold text-cream leading-snug">Verify Your Number</h3>
                        <p class="text-gold-100/80 text-sm mt-2">We've sent a 6-digit code to your phone.</p>
                    </div>
                </template>
            </div>
        </div>

        {{-- right: form --}}
        <div class="p-6 sm:p-10 flex flex-col justify-center">
            {{-- STEP 1: name + phone --}}
            <template x-if="step === 'form'">
                <div>
                    <h2 class="font-display font-bold text-2xl text-maroon-800 mb-1">Log In / Sign Up</h2>
                    <p class="text-maroon-500 text-sm mb-6">One step — we'll text you a code, no password needed.</p>

                    <form @submit.prevent="sendOtp()" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Full Name</label>
                            <input type="text" x-model="name" required placeholder="Your name"
                                class="w-full rounded-xl bg-white border border-gold-300/60 text-maroon-900 placeholder-maroon-400/50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Phone Number</label>
                            <input type="tel" x-model="phone" required maxlength="10" inputmode="numeric" pattern="[0-9]*" placeholder="10-digit mobile number"
                                class="w-full rounded-xl bg-white border border-gold-300/60 text-maroon-900 placeholder-maroon-400/50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                        </div>

                        <p x-show="error" x-cloak x-text="error" class="text-xs text-maroon-600 font-medium"></p>

                        <button type="submit" :disabled="loading" class="btn-maroon w-full text-center disabled:opacity-60">
                            <span x-show="!loading">Send OTP</span>
                            <span x-show="loading" x-cloak>Sending...</span>
                        </button>
                    </form>
                </div>
            </template>

            {{-- STEP 2: OTP --}}
            <template x-if="step === 'otp'">
                <div>
                    <h2 class="font-display font-bold text-2xl text-maroon-800 mb-1">Enter OTP</h2>
                    <p class="text-maroon-500 text-sm mb-6">Code sent to <span x-text="phone" class="font-semibold"></span></p>

                    <p x-show="devOtp" x-cloak class="text-xs text-blue-700 bg-blue-50 border border-dashed border-blue-300 rounded-lg px-3 py-2 mb-4">
                        🛠️ Dev mode — OTP: <strong x-text="devOtp"></strong>
                    </p>

                    <form @submit.prevent="verifyOtp()" class="space-y-4">
                        <input type="text" x-model="otp" required maxlength="6" inputmode="numeric" pattern="[0-9]*" placeholder="000000"
                            class="w-full rounded-xl bg-white border border-gold-300/60 text-maroon-900 placeholder-maroon-400/50 px-4 py-3 text-center text-lg tracking-[0.5em] focus:outline-none focus:ring-2 focus:ring-gold-400">

                        <p x-show="error" x-cloak x-text="error" class="text-xs text-maroon-600 font-medium"></p>

                        <button type="submit" :disabled="loading" class="btn-maroon w-full text-center disabled:opacity-60">
                            <span x-show="!loading">Verify &amp; Continue</span>
                            <span x-show="loading" x-cloak>Verifying...</span>
                        </button>

                        <button type="button" @click="resendOtp()" :disabled="resendCooldown > 0 || loading"
                            class="text-xs text-gold-600 font-semibold hover:text-gold-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="resendCooldown === 0">Resend OTP</span>
                            <span x-show="resendCooldown > 0" x-cloak>Resend OTP in <span x-text="resendCooldown"></span>s</span>
                        </button>

                        <button type="button" @click="step = 'form'; error = ''" class="block text-xs text-maroon-400 hover:text-maroon-600 transition">
                            Change phone number
                        </button>
                    </form>
                </div>
            </template>
        </div>
    </div>
</div>

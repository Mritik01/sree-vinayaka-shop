{{-- First-visit "Shaadi/Function" lead-capture popup with OTP-verified phone number. Shows once per browser (localStorage flag), then never again unless storage is cleared. --}}
<div x-data="promoPopup()"
     @keydown.escape.window="if (promoOpen) dismiss()">

    <div x-show="promoOpen" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6">

        {{-- backdrop --}}
        <div x-show="promoOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="dismiss()"
             class="absolute inset-0 bg-maroon-900/75 backdrop-blur-sm"></div>

        {{-- card --}}
        <div x-show="promoOpen"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 scale-90 translate-y-6"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-md sm:max-w-lg rounded-2xl overflow-hidden shadow-2xl border border-gold-300/50 bg-ivory">

            {{-- photo --}}
            <div class="relative">
                <img :src="imageRevealed ? {{ Illuminate\Support\Js::from(asset('images/promo/wedding-promo.jpg')) }} : false" alt="Newlywed couple presenting a Shri Vinayak Family Shop gift box" loading="lazy" class="w-full h-40 sm:h-48 object-cover object-[center_30%]">
                <div class="absolute inset-0 bg-gradient-to-t from-maroon-900/30 to-transparent"></div>

                <button @click="dismiss()" aria-label="Close"
                    class="absolute top-3 right-3 w-9 h-9 rounded-full bg-white/85 hover:bg-white text-maroon-800 flex items-center justify-center shadow-sm transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @include('partials.scallop-cloud', ['fill' => '#fffbf2'])

            {{-- content --}}
            <div class="bg-ivory px-6 sm:px-8 pb-7 pt-1 text-center">
                <h3 class="font-display font-bold text-xl sm:text-2xl text-maroon-800 leading-snug">
                    <span class="font-hindi">शादी हो या फंक्शन?</span> <span class="text-gold-600">Worry Not, <span class="font-hindi">हम हैं ना!</span></span> 🎉
                </h3>

                {{-- STEP 1: name + phone --}}
                <template x-if="step === 'form'">
                    <div>
                        <p class="text-maroon-500 text-sm mt-2">
                            Apna naam aur mobile number daalein — hum OTP se verify karke best package suggest karenge.
                        </p>

                        <form @submit.prevent="sendOtp()" class="mt-6 space-y-3">
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-maroon-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.964 0a9 9 0 1 0-11.964 0m11.964 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </span>
                                <input type="text" x-model="name" required placeholder="Aapka Naam"
                                    class="w-full rounded-full bg-white border border-gold-300/60 text-maroon-900 placeholder-maroon-400/50 pl-12 pr-4 py-3.5 text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                            </div>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-maroon-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                    </svg>
                                </span>
                                <input type="tel" x-model="phone" required placeholder="10-digit mobile number" maxlength="10" inputmode="numeric" pattern="[0-9]*"
                                    class="w-full rounded-full bg-white border border-gold-300/60 text-maroon-900 placeholder-maroon-400/50 pl-12 pr-4 py-3.5 text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                            </div>

                            <p x-show="error" x-cloak x-text="error" class="text-xs text-maroon-600 font-medium"></p>

                            <button type="submit" :disabled="loading" class="btn-gold w-full mt-1 disabled:opacity-60">
                                <span x-show="!loading">Send OTP</span>
                                <span x-show="loading" x-cloak>Sending...</span>
                            </button>
                            <p class="text-xs text-maroon-400 mt-3">Bas ek OTP verify karo — no spam, promise! 🍬</p>
                        </form>
                    </div>
                </template>

                {{-- STEP 2: OTP entry --}}
                <template x-if="step === 'otp'">
                    <div>
                        <p class="text-maroon-500 text-sm mt-2">
                            <span x-text="phone"></span> par bheja gaya 6-digit OTP daalein.
                        </p>

                        <p x-show="devOtp" x-cloak class="text-xs text-blue-700 bg-blue-50 border border-dashed border-blue-300 rounded-lg px-3 py-2 mt-3">
                            🛠️ Dev mode — OTP: <strong x-text="devOtp"></strong>
                        </p>

                        <form @submit.prevent="verifyOtp()" class="mt-6 space-y-3">
                            <input type="text" x-model="otp" required maxlength="6" inputmode="numeric" pattern="[0-9]*" placeholder="000000"
                                class="w-full rounded-full bg-white border border-gold-300/60 text-maroon-900 placeholder-maroon-400/50 px-4 py-3.5 text-center text-lg tracking-[0.5em] focus:outline-none focus:ring-2 focus:ring-gold-400">

                            <p x-show="error" x-cloak x-text="error" class="text-xs text-maroon-600 font-medium"></p>

                            <button type="submit" :disabled="loading" class="btn-gold w-full mt-1 disabled:opacity-60">
                                <span x-show="!loading">Verify &amp; Submit</span>
                                <span x-show="loading" x-cloak>Verifying...</span>
                            </button>

                            <button type="button" @click="resendOtp()" :disabled="resendCooldown > 0 || loading"
                                class="text-xs text-gold-600 font-semibold hover:text-gold-700 transition disabled:opacity-50 disabled:cursor-not-allowed mt-1">
                                <span x-show="resendCooldown === 0">Resend OTP</span>
                                <span x-show="resendCooldown > 0" x-cloak>Resend OTP in <span x-text="resendCooldown"></span>s</span>
                            </button>
                        </form>
                    </div>
                </template>

                {{-- STEP 3: success --}}
                <div x-show="step === 'sent'" x-cloak class="mt-6 py-4">
                    <p class="text-2xl">🙏</p>
                    <p class="text-pista-500 font-semibold mt-2">Shukriya! Aapka number verify ho gaya, hum jald hi contact karenge.</p>
                </div>
            </div>
        </div>
    </div>
</div>

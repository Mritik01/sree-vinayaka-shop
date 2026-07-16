{{-- Login / Sign Up modal — visibility controlled by `authOpen` in the parent x-data.
     Phone-first, frictionless flow owned by authModal() in app.js:
       phone → otp → name (new accounts only, existing ones skip straight to success) → success --}}
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

        {{-- left: image panel (hidden on mobile to keep the modal compact) — crossfades per step --}}
        <div class="hidden md:block relative overflow-hidden">
            <img src="{{ asset('images/hero/hero-4.jpg') }}" alt="Makhanbhog Sweets — freshly made every morning"
                 x-show="step === 'phone'"
                 x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute inset-0 w-full h-full object-cover [object-position:62%_12%]">
            <img src="{{ asset('images/hero/hero-5.jpg') }}" alt="Customer at Makhanbhog Sweets"
                 x-show="step === 'otp'" x-cloak
                 x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute inset-0 w-full h-full object-cover [object-position:78%_18%]">
            <img src="{{ asset('images/hero/hero-3.jpg') }}" alt="Makhanbhog Sweets gift box"
                 x-show="step === 'name'" x-cloak
                 x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute inset-0 w-full h-full object-cover">
            <img src="{{ asset('images/hero/hero-2.jpg') }}" alt="Makhanbhog Sweets, ready to celebrate"
                 x-show="step === 'success'" x-cloak
                 x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute inset-0 w-full h-full object-cover">

            <div class="absolute inset-0 bg-gradient-to-t from-maroon-900/85 via-maroon-900/20 to-maroon-900/10"></div>

            <div class="relative z-10 h-full flex flex-col justify-end p-8">
                <template x-if="step === 'phone'">
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
                <template x-if="step === 'name'">
                    <div>
                        <p class="text-gold-300 font-semibold tracking-widest uppercase text-xs mb-2">One Last Step</p>
                        <h3 class="font-display text-2xl font-bold text-cream leading-snug">Nice to Meet You!</h3>
                        <p class="text-gold-100/80 text-sm mt-2">Tell us your name and you're in.</p>
                    </div>
                </template>
                <template x-if="step === 'success'">
                    <div>
                        <p class="text-gold-300 font-semibold tracking-widest uppercase text-xs mb-2" x-text="isNewUser ? 'You\'re In!' : 'Welcome Back'"></p>
                        <h3 class="font-display text-2xl font-bold text-cream leading-snug" x-text="isNewUser ? 'Welcome to the Family' : 'Great to See You Again'"></h3>
                        <p class="text-gold-100/80 text-sm mt-2" x-text="isNewUser ? 'Your account is ready to go.' : 'Your cart and favourites are right where you left them.'"></p>
                    </div>
                </template>
            </div>
        </div>

        {{-- right: form --}}
        <div class="p-6 sm:p-10 flex flex-col justify-center">
            {{-- STEP 1: phone number only --}}
            <template x-if="step === 'phone'">
                <div>
                    <div class="mx-auto w-16 h-16 rounded-full bg-gradient-to-br from-maroon-600 to-maroon-800 flex items-center justify-center text-3xl shadow-md animate-auth-icon-pop">👋</div>
                    <h2 class="font-display font-bold text-2xl text-maroon-800 mt-4 text-center">{{ __('Welcome!') }}</h2>
                    <p class="text-maroon-500 text-sm mt-1.5 mb-6 text-center">{{ __('Enter your mobile number to get started — no password needed.') }}</p>

                    <form @submit.prevent="sendOtp()" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-maroon-700 mb-1.5">{{ __('Phone Number') }}</label>
                            <input type="tel" x-model="phone" required maxlength="10" inputmode="numeric" pattern="[0-9]*" placeholder="{{ __('10-digit mobile number') }}" autofocus
                                class="w-full rounded-xl bg-white border border-gold-300/60 text-maroon-900 placeholder-maroon-400/50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                        </div>

                        <p x-show="error" x-cloak x-text="error" class="text-xs text-maroon-600 font-medium"></p>

                        <button type="submit" :disabled="loading || phone.length !== 10" class="btn-maroon w-full text-center disabled:opacity-60">
                            <span x-show="!loading">{{ __('Send OTP') }}</span>
                            <span x-show="loading" x-cloak>{{ __('Sending...') }}</span>
                        </button>
                        <p class="text-xs text-maroon-400 text-center">{{ __("We'll text you a quick verification code.") }}</p>
                    </form>
                </div>
            </template>

            {{-- STEP 2: OTP --}}
            <template x-if="step === 'otp'">
                <div>
                    <div class="mx-auto w-16 h-16 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center text-3xl shadow-md animate-auth-icon-pop">🔐</div>
                    <h2 class="font-display font-bold text-2xl text-maroon-800 mt-4 text-center">{{ __('Verify Your Number') }}</h2>
                    <p class="text-maroon-500 text-sm mt-1.5 mb-6 text-center">
                        {{ __('Enter the 6-digit code sent to') }} <span x-text="phone" class="font-semibold text-maroon-700"></span>
                    </p>

                    <p x-show="devOtp" x-cloak class="text-xs text-blue-700 bg-blue-50 border border-dashed border-blue-300 rounded-lg px-3 py-2 mb-4 text-center">
                        🛠️ Dev mode — OTP: <strong x-text="devOtp"></strong>
                    </p>

                    <form @submit.prevent="verifyOtp()" class="space-y-4">
                        <input type="text" x-model="otp" required maxlength="6" inputmode="numeric" pattern="[0-9]*" placeholder="000000" autofocus
                            class="w-full rounded-xl bg-white border border-gold-300/60 text-maroon-900 placeholder-maroon-400/50 px-4 py-3 text-center text-lg tracking-[0.5em] focus:outline-none focus:ring-2 focus:ring-gold-400">

                        <p x-show="error" x-cloak x-text="error" class="text-xs text-maroon-600 font-medium"></p>

                        <button type="submit" :disabled="loading || otp.length !== 6" class="btn-maroon w-full text-center disabled:opacity-60">
                            <span x-show="!loading">{{ __('Verify & Continue') }}</span>
                            <span x-show="loading" x-cloak>{{ __('Verifying...') }}</span>
                        </button>

                        <div class="flex items-center justify-between pt-1">
                            <button type="button" @click="resendOtp()" :disabled="resendCooldown > 0 || loading"
                                class="text-xs text-gold-600 font-semibold hover:text-gold-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="resendCooldown === 0">{{ __('Resend OTP') }}</span>
                                <span x-show="resendCooldown > 0" x-cloak>{{ __('Resend in') }} <span x-text="resendCooldown"></span>s</span>
                            </button>
                            <button type="button" @click="step = 'phone'; error = ''" class="text-xs text-maroon-400 hover:text-maroon-600 transition">
                                {{ __('Change number') }}
                            </button>
                        </div>
                    </form>
                </div>
            </template>

            {{-- STEP 3: name (new accounts only) — warm, personal, one field --}}
            <template x-if="step === 'name'">
                <div>
                    <div class="mx-auto w-16 h-16 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center text-3xl shadow-md animate-auth-icon-pop">🎉</div>
                    <h2 class="font-display font-bold text-2xl text-maroon-800 mt-4 text-center">{{ __('Welcome!') }} 🎉</h2>
                    <p class="text-maroon-500 text-sm mt-1.5 text-center">{{ __("We're so happy to have you here.") }}</p>
                    <p class="text-maroon-700 text-sm font-semibold mt-3 text-center">{{ __("Let's get to know you — just one last step.") }}</p>

                    <form @submit.prevent="completeSignup()" class="mt-5 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-maroon-700 mb-1.5 text-center">{{ __('What should we call you?') }}</label>
                            <input type="text" x-model="name" required maxlength="100" placeholder="{{ __('Your name') }}" autofocus
                                class="w-full rounded-xl bg-white border border-gold-300/60 text-maroon-900 placeholder-maroon-400/50 px-4 py-3 text-sm text-center focus:outline-none focus:ring-2 focus:ring-gold-400">
                        </div>

                        <p x-show="error" x-cloak x-text="error" class="text-xs text-maroon-600 font-medium text-center"></p>

                        <button type="submit" :disabled="loading || !name.trim()" class="btn-maroon w-full text-center disabled:opacity-60">
                            <span x-show="!loading">{{ __("Let's Go!") }}</span>
                            <span x-show="loading" x-cloak>{{ __('Just a moment...') }}</span>
                        </button>
                    </form>
                </div>
            </template>

            {{-- STEP 4: success — checkmark pop + celebratory message, then auto-redirect --}}
            <template x-if="step === 'success'">
                <div class="text-center py-4">
                    <div class="mx-auto w-20 h-20 rounded-full bg-gradient-to-br from-pista-400 to-pista-600 flex items-center justify-center shadow-lg animate-auth-success-ring">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                            <path class="animate-auth-success-check" stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>
                    <h2 class="font-display font-bold text-2xl text-maroon-800 mt-5"
                        x-text="isNewUser
                            ? (@json(__('You\'re all set! Welcome to the family.')) + ' ❤️')
                            : (welcomeName ? (@json(__('Welcome back')) + ', ' + welcomeName + '! 👋') : (@json(__('Welcome back!')) + ' 👋'))"></h2>
                    <p class="text-maroon-400 text-sm mt-2">{{ __('Redirecting you now...') }}</p>
                </div>
            </template>
        </div>
    </div>
</div>

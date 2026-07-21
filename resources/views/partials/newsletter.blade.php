{{-- was a flat static card with a fake @submit.prevent that never saved anywhere and a
     placeholder honey emoji in a box (see the old TODO comment, now resolved). Now: a real photo,
     the same textured/glow-blob treatment used on festival-special/promo-banner, a fade-up reveal,
     and a form that actually posts to NewsletterController::subscribe() — admin sees every signup
     at Admin → Newsletter (mirrors the existing Leads list exactly). --}}
<section class="relative bg-white py-20 overflow-hidden">
    <div class="max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12">
        <div class="relative rounded-2xl sm:rounded-3xl bg-gradient-to-br from-cream via-gold-50 to-cream border border-gold-300/60 shadow-lg px-6 sm:px-16 py-12 sm:py-16 grid grid-cols-1 md:grid-cols-2 gap-10 items-center overflow-hidden"
             x-data="{ shown: false }"
             x-init="const io = new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { shown = true; io.disconnect(); } }, { threshold: 0.2 }); io.observe($el)">

            {{-- ambient texture + glow, matching festival-special/promo-banner --}}
            <div class="pointer-events-none absolute inset-0 opacity-10 bg-dot-grid text-gold-500"></div>
            <div class="pointer-events-none absolute -top-16 -left-10 w-64 h-64 rounded-full bg-gold-400/25 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-16 -right-10 w-72 h-72 rounded-full bg-maroon-400/15 blur-3xl"></div>

            <div class="relative z-10 text-center md:text-left transition-all duration-700 ease-out"
                 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
                <p class="text-gold-600 font-semibold tracking-widest uppercase text-sm mb-3">{{ __('Stay in Touch') }}</p>
                <h2 class="font-display text-3xl sm:text-5xl font-bold text-maroon-900 leading-tight">{{ __('Join the Shree Vinayak Parivaar') }}</h2>
                <p class="text-maroon-500 mt-4">{{ __('Be the first to hear about new arrivals, special offers, and seasonal treats.') }}</p>

                <form x-data="{
                          email: '', loading: false, sent: false, already: false, error: '',
                          async submit() {
                              if (this.loading) return;
                              this.loading = true; this.error = '';
                              try {
                                  const csrf = document.querySelector('meta[name=csrf-token]').content;
                                  const res = await fetch('{{ route('newsletter.subscribe') }}', {
                                      method: 'POST',
                                      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                                      body: JSON.stringify({ email: this.email }),
                                  });
                                  const data = await res.json().catch(() => ({}));
                                  if (data.ok) {
                                      this.sent = true;
                                      this.already = !!data.already;
                                  } else {
                                      this.error = data.errors?.email?.[0] || data.message || {{ Illuminate\Support\Js::from(__('Something went wrong, please try again.')) }};
                                  }
                              } catch (e) {
                                  this.error = {{ Illuminate\Support\Js::from(__('Network error, please try again.')) }};
                              } finally {
                                  this.loading = false;
                              }
                          },
                      }"
                      @submit.prevent="submit()" class="mt-8 max-w-md mx-auto md:mx-0">
                    <div class="flex flex-col sm:flex-row gap-3" x-show="!sent">
                        <div class="relative flex-1">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gold-500/70 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            <input type="email" x-model="email" required placeholder="{{ __('Enter your email') }}"
                                class="w-full rounded-xl bg-white border border-gold-300/60 text-maroon-900 placeholder-maroon-400/50 pl-11 pr-5 py-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400 transition">
                        </div>
                        <button type="submit" :disabled="loading" class="btn-maroon shrink-0 disabled:opacity-60">
                            <span x-show="!loading">{{ __('Sign Up') }}</span>
                            <span x-show="loading" x-cloak>{{ __('Sending…') }}</span>
                        </button>
                    </div>
                    <p x-show="error" x-cloak x-text="error" class="text-xs text-red-600 font-medium mt-2"></p>

                    <div x-show="sent" x-cloak
                         x-transition:enter="transition ease-out duration-400" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                         class="flex items-center justify-center md:justify-start gap-2.5 bg-white/70 border border-gold-300/50 rounded-xl px-5 py-3.5">
                        <span class="text-xl">🙏</span>
                        <span class="font-semibold text-maroon-800">
                            <span x-show="!already">{{ __('Welcome to the family!') }}</span>
                            <span x-show="already" x-cloak>{{ __("You're already with us — welcome back!") }}</span>
                        </span>
                    </div>
                </form>
            </div>

            <div class="relative z-10 flex justify-center md:justify-end transition-all duration-700 ease-out delay-150"
                 :class="shown ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 translate-y-6 scale-95'">
                <div class="relative w-56 h-56 sm:w-72 sm:h-72 rotate-2">
                    <div class="absolute inset-0 rounded-[1.75rem] bg-gradient-to-br from-gold-400 to-maroon-600 rotate-3"></div>
                    <div class="absolute inset-0 rounded-[1.75rem] overflow-hidden ring-4 ring-white shadow-xl -rotate-1">
                        <img src="{{ asset('images/promo/gift-box-showcase.jpg') }}" alt="{{ __('Shree Vinayak gift box') }}" loading="lazy"
                             class="w-full h-full object-cover">
                    </div>
                    <span class="absolute -bottom-3 -left-3 w-14 h-14 rounded-full bg-white shadow-lg flex items-center justify-center text-2xl -rotate-6">🎁</span>
                </div>
            </div>
        </div>
    </div>
</section>

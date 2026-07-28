@extends('layouts.app')

@section('title', __('Account Restricted') . ' — Shri Vinayak Family Shop')

@section('content')
    <div class="relative min-h-[75vh] flex items-center justify-center px-5 py-14 overflow-hidden">
        <div class="absolute inset-0 bg-dot-grid text-gold-400/25 pointer-events-none"></div>
        <div class="absolute top-1/4 -left-16 w-72 h-72 rounded-full bg-red-400/10 blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-1/4 -right-16 w-80 h-80 rounded-full bg-maroon-400/10 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-lg w-full">
            <div class="bg-white rounded-3xl shadow-xl border border-red-100 overflow-hidden animate-fade-up">
                <div class="relative bg-gradient-to-br from-maroon-800 via-maroon-700 to-maroon-800 px-6 py-8 text-center overflow-hidden">
                    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, #fcd34d 1.5px, transparent 1.5px); background-size: 18px 18px;"></div>
                    <p class="relative text-5xl animate-wobble-404">🚫</p>
                    <h1 class="relative font-display font-bold text-xl sm:text-2xl text-cream mt-3">{{ __('Account Temporarily Restricted') }}</h1>
                    <p class="relative font-hindi text-gold-300 text-sm mt-1.5">खाता अस्थायी रूप से प्रतिबंधित</p>
                </div>

                <div class="p-6 sm:p-7 space-y-5">
                    <p class="text-maroon-600 text-sm leading-relaxed text-center animate-fade-up" style="animation-delay: 60ms">
                        {{ __("We're sorry, your account has been temporarily restricted from placing new orders.") }}
                    </p>

                    @if ($user->blockReasonLabel())
                        <div class="rounded-2xl bg-red-50 border border-red-200 px-4 py-3 animate-fade-up" style="animation-delay: 110ms">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-red-500">{{ __('Reason') }}</p>
                            <p class="text-sm text-red-700 font-medium mt-0.5">🚫 {{ $user->blockReasonLabel() }}</p>
                        </div>
                    @endif

                    @if ($user->block_message)
                        <div class="rounded-2xl bg-gold-50 border border-gold-200 px-4 py-3.5 animate-fade-up" style="animation-delay: 160ms">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gold-600">{{ __('Message from Store') }}</p>
                            <p class="text-sm text-maroon-700 mt-1 leading-relaxed">&ldquo;{{ $user->block_message }}&rdquo;</p>
                        </div>
                    @endif

                    <p class="text-maroon-400 text-xs text-center animate-fade-up" style="animation-delay: 200ms">
                        {{ __('If you believe this is a mistake, please contact our support team.') }}
                    </p>

                    <div class="flex flex-col gap-2.5 pt-1 animate-fade-up" style="animation-delay: 250ms">
                        <a href="{{ route('contact') }}" class="btn-gold w-full text-center">
                            💬 {{ __('Contact Store') }}
                        </a>
                        @if ($businessPhone)
                            <a href="{{ $businessPhone['tel'] }}" class="btn-maroon w-full text-center">
                                📞 {{ __('Call Business') }}
                            </a>
                            @if ($businessPhone['whatsapp'])
                                <a href="{{ $businessPhone['whatsapp'] }}" target="_blank" rel="noopener"
                                   class="w-full text-center bg-pista-500 hover:bg-pista-600 text-white font-semibold rounded-xl py-3 transition">
                                    💚 {{ __('WhatsApp Us') }}
                                </a>
                            @endif
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="pt-1">
                            @csrf
                            <button type="submit" class="w-full text-center text-sm text-maroon-400 hover:text-maroon-600 transition py-2">
                                🚪 {{ __('Log Out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

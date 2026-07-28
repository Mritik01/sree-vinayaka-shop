<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Page Not Found') }} — Shri Vinayak Family Shop</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Baloo+2:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    {{-- this page doesn't extend layouts.app, so it needs its own copy of the per-request theme
         override — $customerTheme is shared globally via View::share, so it's available here too --}}
    <style>
        :root {
            @foreach ($customerTheme['vars'] as $name => $value)
                --color-{{ $name }}: {{ $value }};
            @endforeach
        }
    </style>
</head>
<body class="antialiased font-body bg-ivory text-maroon-900 min-h-screen flex flex-col overflow-x-hidden">

    {{-- minimal header — just enough to reorient, none of the cart/auth/search machinery
         a genuine error page has no business depending on --}}
    <header class="relative z-10 px-5 sm:px-8 py-5">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5">
            <img src="{{ $businessLogo ?? asset('images/logo-circle.png') }}" alt="Shri Vinayak Family Shop" class="h-9 w-9 sm:h-10 sm:w-10 rounded-full object-cover bg-white">
            <span class="font-display font-bold text-base sm:text-lg">
                <span class="text-gold-600">Shri Vinayak</span> <span class="text-maroon-800">Family Shop</span>
            </span>
        </a>
    </header>

    <main class="relative flex-1 flex items-center justify-center px-5 py-8 overflow-hidden">
        {{-- warm dot-grid texture + soft ambient glow, matching the mobile Categories sheet --}}
        <div class="absolute inset-0 bg-dot-grid text-gold-400/25 pointer-events-none"></div>
        <div class="absolute top-1/4 -left-16 w-72 h-72 rounded-full bg-gold-400/20 blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-1/4 -right-16 w-80 h-80 rounded-full bg-maroon-400/10 blur-3xl pointer-events-none"></div>

        {{-- scattered background sweets, each with its own drift timing so the field feels
             organic rather than mechanically synchronized --}}
        <span class="hidden sm:block absolute text-5xl opacity-70 animate-bob-float" style="top:14%; left:10%; --dur:4.2s; --delay:0s; --rot:-8deg;" aria-hidden="true">🍬</span>
        <span class="hidden sm:block absolute text-4xl opacity-60 animate-bob-float" style="top:22%; right:14%; --dur:5s; --delay:0.6s; --rot:10deg;" aria-hidden="true">🧁</span>
        <span class="hidden sm:block absolute text-5xl opacity-60 animate-bob-float" style="bottom:20%; left:16%; --dur:4.6s; --delay:0.3s; --rot:6deg;" aria-hidden="true">🍩</span>
        <span class="hidden sm:block absolute text-4xl opacity-70 animate-bob-float" style="bottom:16%; right:12%; --dur:4s; --delay:0.9s; --rot:-10deg;" aria-hidden="true">🍡</span>
        <span class="absolute text-3xl opacity-50 animate-bob-float" style="top:10%; right:28%; --dur:3.6s; --delay:0.4s; --rot:12deg;" aria-hidden="true">✨</span>
        <span class="absolute text-3xl opacity-40 animate-bob-float" style="bottom:12%; left:32%; --dur:4.4s; --delay:0.7s; --rot:-6deg;" aria-hidden="true">✨</span>

        <div class="relative z-10 max-w-lg w-full text-center">
            {{-- the numeral itself — a wobbling laddoo standing in for the middle "0", with a
                 few crumbs drifting off it, like it's just been nibbled from the shelf --}}
            <div class="relative inline-flex items-center justify-center gap-1 sm:gap-3 select-none">
                <span class="font-display font-extrabold text-[5.5rem] sm:text-[8rem] leading-none text-maroon-800 drop-shadow-sm animate-fade-up">4</span>

                <span class="relative inline-block animate-fade-up [animation-delay:120ms]">
                    <span class="block text-[4.5rem] sm:text-[6.5rem] leading-none animate-wobble-404">🍡</span>
                    <span class="absolute top-1 left-1 text-base opacity-70 animate-crumb-drift" style="--tx:-22px; --ty:14px; --delay:0s;" aria-hidden="true">•</span>
                    <span class="absolute top-3 right-2 text-sm opacity-60 animate-crumb-drift" style="--tx:20px; --ty:18px; --delay:0.7s;" aria-hidden="true">•</span>
                    <span class="absolute bottom-2 left-3 text-xs opacity-50 animate-crumb-drift" style="--tx:-14px; --ty:-16px; --delay:1.3s;" aria-hidden="true">•</span>
                </span>

                <span class="font-display font-extrabold text-[5.5rem] sm:text-[8rem] leading-none text-maroon-800 drop-shadow-sm animate-fade-up [animation-delay:60ms]">4</span>
            </div>

            <p class="font-hindi text-gold-600 text-sm tracking-wide mt-4 animate-fade-up [animation-delay:250ms]">यह मिठाई यहाँ नहीं है 🍥</p>

            <h1 class="font-display font-bold text-2xl sm:text-3xl text-maroon-900 mt-2 animate-fade-up [animation-delay:300ms]">
                {{ __('Oops — This Page Got Gobbled Up') }}
            </h1>
            <p class="text-maroon-500 text-sm sm:text-base mt-3 max-w-sm mx-auto leading-relaxed animate-fade-up [animation-delay:400ms]">
                {{ __("The page you're looking for isn't on our shelves anymore — it may have sold out, moved, or never existed at all.") }}
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-8 animate-fade-up [animation-delay:500ms]">
                <a href="{{ url('/') }}" class="btn-gold w-full sm:w-auto text-center">
                    🏠 {{ __('Back to Home') }}
                </a>
                <a href="{{ url('/products') }}" class="btn-maroon w-full sm:w-auto text-center">
                    🍬 {{ __('Browse Our Sweets') }}
                </a>
            </div>
        </div>
    </main>

    <div class="relative z-10">
        @include('partials.trim', ['fill' => '#3a0b12'])
    </div>
    <footer class="relative bg-maroon-800 text-cream/70 text-center text-xs sm:text-sm py-5 px-5">
        {{ __('Shri Vinayak Family Shop') }} · {{ __("Siswa Bazar's favourite grocery store since generations") }}
    </footer>
</body>
</html>

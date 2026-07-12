<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Deliveries') — Makhanbhog Sweets</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('partials.order-status-i18n')
    @vite(['resources/css/app.css', 'resources/js/rider.js'])
</head>
<body class="antialiased font-body bg-cream min-h-screen">
    <header class="sticky top-0 z-30 bg-maroon-800 text-cream px-4 py-3.5 flex items-center justify-between shadow-md">
        <a href="{{ route('rider.orders.index') }}" class="flex items-center gap-2">
            <span class="text-lg">🛵</span>
            <span class="font-display font-bold text-base">{{ __('Deliveries') }}</span>
        </a>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1 text-xs font-bold">
                <form method="POST" action="{{ route('rider.locale.switch', 'en') }}">
                    @csrf
                    <button type="submit" class="px-2 py-1 rounded transition {{ app()->getLocale() === 'en' ? 'bg-cream/15 text-cream' : 'text-cream/50 hover:text-cream' }}">EN</button>
                </form>
                <form method="POST" action="{{ route('rider.locale.switch', 'hi') }}">
                    @csrf
                    <button type="submit" class="font-hindi px-2 py-1 rounded transition {{ app()->getLocale() === 'hi' ? 'bg-cream/15 text-cream' : 'text-cream/50 hover:text-cream' }}">हिं</button>
                </form>
            </div>
            <form method="POST" action="{{ route('rider.logout') }}">
                @csrf
                <button type="submit" class="text-cream/70 hover:text-cream text-sm transition">{{ __('Log Out') }}</button>
            </form>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-5 pb-10">
        @if (session('status'))
            <div class="mb-5 rounded-lg bg-pista-100 border border-pista-400/40 text-pista-600 text-sm px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>

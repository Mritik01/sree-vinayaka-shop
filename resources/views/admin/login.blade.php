<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login — Shree Vinayak Family Shop</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased font-body bg-maroon-800 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <p class="font-display font-bold text-2xl text-cream">Shree Vinayak <span class="text-gold-400">Family Shop</span></p>
            <p class="text-gold-200/70 text-sm mt-1">Admin Panel</p>
        </div>

        <div class="bg-cream rounded-2xl shadow-xl p-8">
            @if ($errors->any())
                <div class="mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-maroon-700 mb-1.5">Username</label>
                    <input type="text" name="username" value="{{ old('username') }}" required autofocus
                           class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-maroon-700 mb-1.5">Password</label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                </div>
                <button type="submit" class="w-full btn-gold text-center mt-2">Log In</button>
            </form>
        </div>
    </div>
</body>
</html>

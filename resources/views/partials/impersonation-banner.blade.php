@if ($imp = \App\Services\ImpersonationService::active())
    <div class="w-full bg-maroon-800 text-cream px-4 py-2.5 flex flex-wrap items-center justify-center gap-x-3 gap-y-1.5 text-sm font-medium relative z-30">
        <span>🕵️ You are viewing this account as an Administrator — <strong>{{ $imp['user_name'] }}</strong></span>
        <form method="POST" action="{{ route('admin.impersonate.stop') }}">
            @csrf
            <button type="submit" class="px-3 py-1 rounded-full bg-gold-500 text-maroon-900 font-semibold hover:bg-gold-600 transition text-xs">
                Return to Admin
            </button>
        </form>
    </div>
@endif

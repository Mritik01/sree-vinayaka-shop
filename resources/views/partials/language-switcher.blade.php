<div class="flex items-center gap-1 text-xs font-bold shrink-0">
    <form method="POST" action="{{ route('locale.switch', 'en') }}">
        @csrf
        <button type="submit" class="px-1.5 py-1 rounded transition {{ app()->getLocale() === 'en' ? 'text-maroon-900' : 'text-maroon-300 hover:text-maroon-600' }}">EN</button>
    </form>
    <span class="text-gold-400">/</span>
    <form method="POST" action="{{ route('locale.switch', 'hi') }}">
        @csrf
        <button type="submit" class="font-hindi px-1.5 py-1 rounded transition {{ app()->getLocale() === 'hi' ? 'text-maroon-900' : 'text-maroon-300 hover:text-maroon-600' }}">हिं</button>
    </form>
</div>

@if ($errors->any())
    <div class="mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid md:grid-cols-2 gap-5">
    <div>
        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Name</label>
        <input type="text" name="name" value="{{ old('name', $admin->name ?? '') }}" required
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
    </div>
    <div>
        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Username</label>
        <input type="text" name="username" value="{{ old('username', $admin->username ?? '') }}" required
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
    </div>
    <div>
        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Email <span class="text-maroon-400 font-normal">(optional)</span></label>
        <input type="email" name="email" value="{{ old('email', $admin->email ?? '') }}"
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
        <p class="text-xs text-maroon-400 mt-1.5">If set, this admin also gets order-cancellation alerts by email.</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-maroon-700 mb-1.5">
            Password @if(isset($admin)) <span class="text-maroon-400 font-normal">(leave blank to keep current)</span> @endif
        </label>
        <input type="password" name="password" minlength="8" {{ isset($admin) ? '' : 'required' }}
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
        <p class="text-xs text-maroon-400 mt-1.5">At least 8 characters.</p>
    </div>
</div>

<div class="mt-5 rounded-lg border border-gold-200/60 bg-cream/50 p-4">
    <label class="flex items-center gap-2.5">
        <input type="checkbox" name="is_super_admin" value="1" @checked(old('is_super_admin', $admin->is_super_admin ?? false))
               class="w-4 h-4 rounded border-gold-300 text-gold-500 focus:ring-gold-400">
        <span class="text-sm font-medium text-maroon-700">🛡️ Super Admin</span>
    </label>
    <p class="text-xs text-maroon-400 mt-1.5 ml-6">Super Admins can create, edit, and delete other admin accounts (including granting or revoking this same permission). Regular admins can't see or reach this page at all.</p>
</div>

<div class="mt-7 flex items-center gap-3">
    <button type="submit" class="btn-gold">{{ isset($admin) ? 'Save Changes' : 'Create Admin' }}</button>
    <a href="{{ route('admin.admins.index') }}" class="text-maroon-500 hover:text-maroon-700 text-sm font-medium">Cancel</a>
</div>

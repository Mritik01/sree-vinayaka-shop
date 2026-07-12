@if ($errors->any())
    <div class="mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div x-data="{
        isMaster: {{ old('is_master_coupon', isset($coupon) && $coupon->isMasterCoupon() ? '1' : '0') === '1' ? 'true' : 'false' }},
        usageType: '{{ old('usage_type', $coupon->usage_type ?? 'once_per_user') }}',
     }">
    <div class="grid md:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Code</label>
            <input type="text" name="code" value="{{ old('code', $coupon->code ?? '') }}" required
                   class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 uppercase tracking-wide focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
        </div>
        <div>
            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Description</label>
            <input type="text" name="description" placeholder="e.g. 10% off your first order" value="{{ old('description', $coupon->description ?? '') }}"
                   class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
        </div>
        <div>
            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Discount Type</label>
            <select name="discount_type" required class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                <option value="percent" @selected(old('discount_type', $coupon->discount_type ?? '') === 'percent')>Percent (%)</option>
                <option value="flat" @selected(old('discount_type', $coupon->discount_type ?? '') === 'flat')>Flat (₹)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Discount Value</label>
            <input type="number" name="discount_value" min="1" value="{{ old('discount_value', $coupon->discount_value ?? '') }}" required
                   class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
        </div>
        <div>
            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Usage Type</label>
            <select name="usage_type" required x-model="usageType" class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                <option value="once_per_user" @selected(old('usage_type', $coupon->usage_type ?? '') === 'once_per_user')>Once per user (e.g. FIRST10)</option>
                <option value="single_use" @selected(old('usage_type', $coupon->usage_type ?? '') === 'single_use')>Single use (works once, ever)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Expiry Date</label>
            <input type="date" name="expires_at"
                   value="{{ old('expires_at', isset($coupon) ? $coupon->expires_at->format('Y-m-d') : '') }}" required
                   class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
        </div>
    </div>

    <div class="mt-5 rounded-lg border border-gold-200/60 bg-cream/50 p-4">
        <div class="flex items-center gap-2.5">
            <input type="checkbox" name="is_master_coupon" id="is_master_coupon" value="1" x-model="isMaster"
                   class="w-4 h-4 rounded border-gold-300 text-gold-500 focus:ring-gold-400">
            <label for="is_master_coupon" class="text-sm font-medium text-maroon-700">Master coupon — auto-assign to new customers</label>
        </div>
        <p class="text-xs text-maroon-400 mt-1.5 ml-6">When a new customer signs up, they'll automatically get this coupon attached — first come, first served — until the slots below run out.</p>

        <div x-show="isMaster" x-cloak class="mt-3.5 ml-6 max-w-xs">
            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Slots (e.g. first 100 signups)</label>
            <input type="number" name="auto_assign_limit" min="1"
                   value="{{ old('auto_assign_limit', $coupon->auto_assign_limit ?? '') }}"
                   class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">

            <p x-show="usageType === 'single_use'" x-cloak class="text-xs text-red-600 mt-2">
                Switch Usage Type to "Once per user" above — a single-use master coupon would only ever reach one of the new signups.
            </p>

            @if (isset($coupon) && $coupon->isMasterCoupon())
                <p class="text-xs text-maroon-400 mt-2">{{ $coupon->assignedUsers()->count() }} of {{ $coupon->auto_assign_limit }} slots claimed so far.</p>
            @endif
        </div>
    </div>
</div>

<div class="mt-5 flex items-center gap-2.5">
    <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $coupon->is_active ?? true))
           class="w-4 h-4 rounded border-gold-300 text-gold-500 focus:ring-gold-400">
    <label for="is_active" class="text-sm text-maroon-700">Active</label>
</div>

<div class="mt-7 flex items-center gap-3">
    <button type="submit" class="btn-gold">{{ isset($coupon) ? 'Save Changes' : 'Create Coupon' }}</button>
    <a href="{{ route('admin.coupons.index') }}" class="text-maroon-500 hover:text-maroon-700 text-sm font-medium">Cancel</a>
</div>

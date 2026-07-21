<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request)
    {
        $query = Coupon::withCount(['redeemers' => fn ($q) => $q->whereNotNull('coupon_redemptions.redeemed_at')])
            ->with('assignedUsers:id,name')->latest();

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $data = [
            'coupons' => $query->paginate($this->perPage($request))->withQueryString(),
            'search' => $search,
        ];

        return $request->ajax() ? view('admin.coupons._results', $data) : view('admin.coupons.index', $data);
    }

    public function create()
    {
        return view('admin.coupons.create');
    }

    // "who redeemed this?" — split into redeemed vs. still-eligible-but-hasn't-redeemed-yet.
    // For a restricted coupon the eligible pool is just its assigned customers; for an open
    // coupon (no assignments) it's every customer, since anyone can redeem it.
    public function show(Coupon $coupon)
    {
        $redeemed = $coupon->redeemers()->wherePivotNotNull('redeemed_at')->orderByDesc('coupon_redemptions.redeemed_at')->get();

        $eligible = $coupon->isRestricted() ? $coupon->assignedUsers() : User::query();
        $notRedeemed = $eligible->whereNotIn('users.id', $redeemed->pluck('id'))->orderBy('name')->get();

        return view('admin.coupons.show', [
            'coupon' => $coupon,
            'redeemed' => $redeemed,
            'notRedeemed' => $notRedeemed,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['code'] = strtoupper($data['code']);

        Coupon::create($data);

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon created.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', ['coupon' => $coupon]);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $this->validateData($request, $coupon->id);
        $data['code'] = strtoupper($data['code']);

        $coupon->update($data);

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon deleted.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => 'required|string|max:40|unique:coupons,code'.($ignoreId ? ",{$ignoreId}" : ''),
            'description' => 'nullable|string|max:150',
            'discount_type' => 'required|in:percent,flat',
            'discount_value' => 'required|integer|min:1',
            'usage_type' => 'required|in:single_use,once_per_user',
            'is_master_coupon' => 'sometimes|boolean',
            'auto_assign_limit' => 'nullable|required_if:is_master_coupon,1|integer|min:1',
            'expires_at' => 'required|date',
        ]);

        // a master coupon has to be shared across many new signups, so it can't also be
        // restricted to firing only once, ever — that combination would hand it to whichever
        // of them checks out first and leave the rest of the "first N" batch empty-handed
        if ($request->boolean('is_master_coupon') && $data['usage_type'] === 'single_use') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'auto_assign_limit' => 'A master coupon needs "Once per user" — "Single use" only ever works for one person.',
            ]);
        }

        $data['auto_assign_limit'] = $request->boolean('is_master_coupon') ? $data['auto_assign_limit'] : null;
        unset($data['is_master_coupon']);

        $data['is_active'] = $request->boolean('is_active');
        $data['expires_at'] = \Illuminate\Support\Carbon::parse($data['expires_at'])->endOfDay();

        return $data;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = $request->user();
        $isFirst = $user->addresses()->count() === 0;
        $makeDefault = $isFirst || ($data['is_default'] ?? false);

        $address = DB::transaction(function () use ($user, $data, $makeDefault) {
            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            return $user->addresses()->create([
                'label' => $data['label'] ?? null,
                'address_line' => $data['address_line'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'is_default' => $makeDefault,
            ]);
        });

        return response()->json(['ok' => true, 'address' => $this->present($address)]);
    }

    public function update(Request $request, $address)
    {
        $address = $request->user()->addresses()->findOrFail($address);
        $data = $this->validated($request, updating: true);
        $makeDefault = $data['is_default'] ?? false;

        DB::transaction(function () use ($address, $data, $makeDefault) {
            if ($makeDefault) {
                $address->user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }

            $address->fill(array_filter([
                'label' => $data['label'] ?? null,
                'address_line' => $data['address_line'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ], fn ($v) => $v !== null));

            if ($makeDefault) {
                $address->is_default = true;
            }

            $address->save();
        });

        return response()->json(['ok' => true, 'address' => $this->present($address->fresh())]);
    }

    public function destroy(Request $request, $address)
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($address);
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            // promote the most recently created remaining address so a default always exists when possible
            $user->addresses()->latest()->first()?->update(['is_default' => true]);
        }

        return response()->json(['ok' => true]);
    }

    public function setDefault(Request $request, $address)
    {
        $address = $request->user()->addresses()->findOrFail($address);
        $address->makeDefault();

        return response()->json(['ok' => true]);
    }

    private function validated(Request $request, bool $updating = false): array
    {
        $rule = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'label' => 'nullable|string|max:40',
            'address_line' => "$rule|string|min:10|max:500",
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'sometimes|boolean',
        ], [
            'address_line.required' => 'Please enter a complete delivery address.',
            'address_line.min' => 'Please enter a complete delivery address.',
        ]);
    }

    private function present(Address $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'address_line' => $address->address_line,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'is_default' => $address->is_default,
        ];
    }
}

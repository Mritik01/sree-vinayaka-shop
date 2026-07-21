<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RiderController extends Controller
{
    // "active" mirrors what's still open on the rider's own app — assigned but not yet
    // delivered or cancelled — while "delivered"/"cancelled" are their finished history.
    private const ACTIVE_STATUSES = ['confirmed', 'out_for_delivery'];

    public function index()
    {
        $riders = Rider::withCount([
            'orders as total_orders_count',
            'orders as active_orders_count' => fn ($q) => $q->whereIn('status', self::ACTIVE_STATUSES),
            'orders as delivered_orders_count' => fn ($q) => $q->where('status', 'delivered'),
            'orders as cancelled_orders_count' => fn ($q) => $q->where('status', 'cancelled'),
        ])->orderBy('name')->get();

        return view('admin.riders.index', ['riders' => $riders]);
    }

    // full breakdown for one rider — summary counts up top, every order they've ever been
    // assigned filterable by the same active/delivered/cancelled/all buckets below
    public function show(Request $request, Rider $rider)
    {
        $rider->loadCount([
            'orders as total_orders_count',
            'orders as active_orders_count' => fn ($q) => $q->whereIn('status', self::ACTIVE_STATUSES),
            'orders as delivered_orders_count' => fn ($q) => $q->where('status', 'delivered'),
            'orders as cancelled_orders_count' => fn ($q) => $q->where('status', 'cancelled'),
        ]);

        $statusFilter = $request->query('status', '');
        $ordersQuery = $rider->orders()->latest();

        if ($statusFilter === 'active') {
            $ordersQuery->whereIn('status', self::ACTIVE_STATUSES);
        } elseif (in_array($statusFilter, ['delivered', 'cancelled'], true)) {
            $ordersQuery->where('status', $statusFilter);
        }

        return view('admin.riders.show', [
            'rider' => $rider,
            'orders' => $ordersQuery->paginate(15)->withQueryString(),
            'statusFilter' => $statusFilter,
        ]);
    }

    public function create()
    {
        return view('admin.riders.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $photoPath = $this->storeCroppedImage($request);
        if ($photoPath) {
            $data['photo_path'] = $photoPath;
        }

        Rider::create($data);

        return redirect()->route('admin.riders.index')->with('status', 'Rider added.');
    }

    public function edit(Rider $rider)
    {
        return view('admin.riders.edit', ['rider' => $rider]);
    }

    public function update(Request $request, Rider $rider)
    {
        $data = $this->validateData($request, $rider->id);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($request->filled('cropped_image')) {
            $photoPath = $this->storeCroppedImage($request);
            if ($photoPath) {
                if ($rider->photo_path) {
                    @unlink(public_path($rider->photo_path));
                }
                $data['photo_path'] = $photoPath;
            }
        }

        $rider->update($data);

        return redirect()->route('admin.riders.index')->with('status', 'Rider updated.');
    }

    public function destroy(Rider $rider)
    {
        if ($rider->photo_path) {
            @unlink(public_path($rider->photo_path));
        }

        $rider->delete();

        return redirect()->route('admin.riders.index')->with('status', 'Rider removed.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:riders,username'.($ignoreId ? ",{$ignoreId}" : ''),
            'phone' => 'nullable|string|max:20',
            'password' => ($ignoreId ? 'nullable' : 'required').'|string|min:8',
            'cropped_image' => 'nullable|string',
        ]);

        unset($data['cropped_image']);

        return $data;
    }

    // same hardened flow as CategoryController::storeCroppedImage() — Cropper.js hands over a
    // base64 data URL in a hidden field; regex-check the label, then verify the decoded bytes
    // really are an image before writing (fixed .jpg extension, so never executable). A photo
    // is optional for a rider, so returning null here just means "no photo set/changed".
    private function storeCroppedImage(Request $request): ?string
    {
        if (!$request->filled('cropped_image')) {
            return null;
        }

        $dataUrl = $request->input('cropped_image');

        if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,(.+)$/', $dataUrl, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[2]);

        if ($binary === false || @getimagesizefromstring($binary) === false) {
            return null;
        }

        $filename = 'rider-'.Str::random(10).'.jpg';
        $directory = public_path('images/riders');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($directory.'/'.$filename, $binary);

        return 'images/riders/'.$filename;
    }
}

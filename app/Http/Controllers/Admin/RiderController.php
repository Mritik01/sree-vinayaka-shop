<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use Illuminate\Http\Request;

class RiderController extends Controller
{
    public function index()
    {
        return view('admin.riders.index', ['riders' => Rider::withCount('orders')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.riders.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

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

        $rider->update($data);

        return redirect()->route('admin.riders.index')->with('status', 'Rider updated.');
    }

    public function destroy(Rider $rider)
    {
        $rider->delete();

        return redirect()->route('admin.riders.index')->with('status', 'Rider removed.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:riders,username'.($ignoreId ? ",{$ignoreId}" : ''),
            'phone' => 'nullable|string|max:20',
            'password' => ($ignoreId ? 'nullable' : 'required').'|string|min:4',
        ]);

        return $data;
    }
}

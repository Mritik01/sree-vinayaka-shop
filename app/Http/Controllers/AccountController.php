<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function updateName(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $user = $request->user();
        $user->forceFill(['name' => trim($data['name'])])->save();

        return response()->json(['ok' => true, 'name' => $user->name]);
    }
}

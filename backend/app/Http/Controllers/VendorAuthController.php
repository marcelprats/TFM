<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class VendorAuthController extends Controller {
    public function registerVendor(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:vendors',
            'password' => 'required|min:6'
        ]);

        $vendor = Vendor::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return response()->json(['message' => 'Venedor registrat correctament!']);
    }

    public function loginVendor(Request $request) {
        if (!Auth::guard('vendor')->attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credencials incorrectes'], 401);
        }

        $vendor = Auth::guard('vendor')->user();
        return response()->json(['message' => 'Login correcte', 'vendor' => $vendor]);
    }
}

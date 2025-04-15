<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registre d'usuari (comprador)
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user], 201);
    }

    /**
     * Registre de venedor
     */
    public function registerVendor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:vendors',
            'password' => 'required|string|min:6',
        ]);

        $vendor = Vendor::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $vendor->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $vendor, 'role' => 'vendor'], 201);
    }

    /**
     * Login genèric (per usuaris i venedors)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'is_vendor' => 'required|boolean', // Indica si és venedor o no
        ]);
    
        if ($request->is_vendor) {
            // Utilitza el guard 'vendor' per buscar el venedor
            $user = \Auth::guard('vendor')->getProvider()->retrieveByCredentials(['email' => $request->email]);
        } else {
            $user = \Auth::guard('web')->getProvider()->retrieveByCredentials(['email' => $request->email]);
        }
    
        if (!$user || !\Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credencials incorrectes'], 401);
        }
    
        // Ara creem el token amb el guard corresponent
        if ($request->is_vendor) {
            $token = $user->createToken('auth_token')->plainTextToken;
        } else {
            $token = $user->createToken('auth_token')->plainTextToken;
        }
    
        return response()->json([
            'token' => $token,
            'user' => $user,
            'role' => $request->is_vendor ? 'vendor' : 'user',
        ], 200);
    }
    

    /**
     * Logout de l'usuari
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Sessió tancada correctament'], 200);
    }
}

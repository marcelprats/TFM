<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\RegisterVendorRequest;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registre d’un nou usuari (comprador).
     */
    public function registerUser(RegisterUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('user_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuari registrat correctament.',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    /**
     * Registre d’un nou venedor.
     */
    public function registerVendor(RegisterVendorRequest $request): JsonResponse
    {
        $data = $request->validated();

        $vendor = Vendor::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $vendor->createToken('vendor_token')->plainTextToken;

        return response()->json([
            'message' => 'Venedor registrat correctament.',
            'token'   => $token,
            'user'    => $vendor,
            'role'    => 'vendor',
        ], 201);
    }

    /**
     * Login genèric per a usuaris i venedors.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Seleccionem el model segons is_vendor
        $modelClass = $data['is_vendor'] ? Vendor::class : User::class;
        $user       = $modelClass::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Credencials incorrectes.',
            ], 401);
        }

        $tokenName = $data['is_vendor'] ? 'vendor_token' : 'user_token';
        $token     = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'message' => 'Login correcte.',
            'token'   => $token,
            'user'    => $user,
            'role'    => $data['is_vendor'] ? 'vendor' : 'user',
        ]);
    }

    /**
     * Logout i revocació de tots els tokens del usuari/venedor autenticat.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Sessió tancada correctament.',
        ]);
    }
}

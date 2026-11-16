<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'El correo electrónico o la contraseña son incorrectos.'], 401);
        }

        $expiresAt = now()->addDay();
        $token = $user->createToken('api-mantenimiento', ['read'], $expiresAt);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => new UserResource($user),
        ])->header('Cache-Control', 'no-store');
    }

    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión de API cerrada correctamente.']);
    }
}

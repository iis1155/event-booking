<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'role'     => 'customer', // default role on register
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse(
            data: [
                'user'         => $user,
                'access_token' => $token,
                'token_type'   => 'Bearer',
            ],
            message: 'Registration successful.',
            code: 201
        );
    }

    /**
     * POST /api/v1/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse(
                message: 'Invalid credentials. Please check your email and password.',
                code: 401
            );
        }

        $user  = Auth::user();

        // Revoke previous tokens (single session)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse(
            data: [
                'user'         => $user,
                'access_token' => $token,
                'token_type'   => 'Bearer',
            ],
            message: 'Login successful.'
        );
    }

    /**
     * POST /api/v1/logout
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current token only
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(
            data: null,
            message: 'Logged out successfully.'
        );
    }

    /**
     * GET /api/v1/me
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            data: $request->user(),
            message: 'Authenticated user retrieved.'
        );
    }
}

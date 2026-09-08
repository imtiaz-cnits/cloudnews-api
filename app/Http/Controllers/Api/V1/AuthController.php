<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GuestLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_guest' => false,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
        ], 'User registered successfully', 201);
    }

    /**
     * Authenticate an existing user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->errorResponse('Invalid email or password', 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Register or initialize a guest user session.
     */
    public function guest(GuestLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $guestUser = User::create([
            'name' => $validated['name'],
            'avatar_url' => $validated['avatar_url'] ?? null,
            'is_guest' => true,
        ]);

        $token = $guestUser->createToken('guest_token')->plainTextToken;

        return $this->successResponse([
            'user' => $guestUser,
            'token' => $token,
        ], 'Guest user created successfully', 201);
    }

    /**
     * Retrieve authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse($request->user(), 'User profile retrieved successfully');
    }

    /**
     * Log out current user and revoke token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }
}

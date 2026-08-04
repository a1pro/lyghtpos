<?php

namespace App\Services\Api\Clover\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CloverAuthService
{
    /**
     * Authenticate user credentials and generate token
     */
    public function authenticate(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        // Validate email and password
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!in_array($user->role_id, [2, 9, 10])) {
            throw ValidationException::withMessages([
                'email' => ['You are not authorized to access the POS system.'],
            ]);
        }

        // Verify account status
        if (isset($user->status) && $user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active.'],
            ]);
        }

        // Generate POS access token
        $token = $user->createToken('pos_auth_token', ['pos-access'])->plainTextToken;

        return [
            'user' => collect($user->toArray())->only(['first_name', 'email', 'location', 'phone'])->map(function ($value, $key) {
                return $value;
            }),
            'token' => $token,
            'message' => 'POS Login successful',
        ];
    }

    /**
     * Get authenticated user profile data
     */
    public function userProfile()
    {
        $user = auth()->user(0);

        return [
            'user' => collect($user->toArray())->only(['first_name', 'last_name', 'email', 'location', 'phone'])->map(function ($value, $key) {
                return $value;
            }),
        ];
    }

    /**
     * Logout user by deleting current access token
     */
    public function logout(User $user): bool
    {
        // Delete current access token
        $user->currentAccessToken()->delete();

        return true;
    }
}

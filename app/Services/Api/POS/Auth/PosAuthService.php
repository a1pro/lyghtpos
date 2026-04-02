<?php

namespace App\Services\Api\POS\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PosAuthService
{
    /**
     * Authenticate POS user
     *
     * @param  array  $credentials
     *
     * @throws ValidationException
     */
    public function register(array $data): array
    {
        if (User::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages(['email' => 'Email is already taken']);
        }

        if (User::where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages(['phone' => 'Phone number is already taken']);
        }

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'location' => $data['location'],
            'role_id' => 11,
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('pos_auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'message' => 'Registration successful',
        ];
    }

    public function authenticate(array $credentials): array
    {
        // Find user by email
        $user = User::where('email', $credentials['email'])->first();

        // Check if user exists and password matches
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user has POS role (role_id = 11)
        if ($user->role_id !== 11) {
            throw ValidationException::withMessages([
                'email' => ['You are not authorized to access the POS system.'],
            ]);
        }

        // Check if user is active (optional - add if you have status field)
        if (isset($user->status) && $user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active.'],
            ]);
        }

        // Generate token with POS abilities
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
     * Logout POS user
     */
    public function logout(User $user): bool
    {
        // Delete current access token
        $user->currentAccessToken()->delete();

        return true;
    }
}

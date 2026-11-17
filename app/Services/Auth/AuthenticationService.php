<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\CompanyStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticationService
{
    /**
     * Authenticate user credentials and create a personal access token.
     *
     * @param array{
     *     email: string,
     *     password: string,
     *     device_name?: string|null
     * } $credentials
     * @return array{
     *     user: User,
     *     token: string
     * }
     * @throws ValidationException
     */
    public function authenticate(array $credentials): array
    {
        $user = User::with('company')->where('email', $credentials['email'])->first();

        if ($user === null || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'email' => ['Your user account is suspended or inactive.'],
            ]);
        }

        if ($user->company !== null && $user->company->status !== CompanyStatus::Active) {
            throw ValidationException::withMessages([
                'email' => ['Your company account is currently inactive or suspended.'],
            ]);
        }

        $deviceName = $credentials['device_name'] ?? 'web-app';
        $token = $user->createToken($deviceName)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}


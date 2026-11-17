<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Events\CompanyRegistered;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyRegistrationService
{
    /**
     * Register a new company and its primary administrator within a transaction.
     *
     * @param array{
     *     company_name: string,
     *     company_email?: string|null,
     *     company_phone?: string|null,
     *     company_website?: string|null,
     *     admin_name: string,
     *     admin_email: string,
     *     password: string
     * } $data
     * @return array{
     *     company: Company,
     *     user: User,
     *     token: string
     * }
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $company = Company::create([
                'name' => $data['company_name'],
                'email' => $data['company_email'] ?? null,
                'phone' => $data['company_phone'] ?? null,
                'website' => $data['company_website'] ?? null,
                'status' => CompanyStatus::Active,
            ]);

            $user = User::create([
                'company_id' => $company->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['password']),
                'role' => UserRole::CompanyAdmin,
                'status' => UserStatus::Active,
            ]);

            event(new CompanyRegistered($company, $user));

            $token = $user->createToken('auth-token')->plainTextToken;

            return [
                'company' => $company,
                'user' => $user,
                'token' => $token,
            ];
        });
    }
}


<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Events\CompanyRegistered;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_new_company_with_admin(): void
    {
        Event::fake([CompanyRegistered::class]);

        $payload = [
            'company_name' => 'Acme Corporation',
            'company_email' => 'contact@acme.com',
            'company_phone' => '+1234567890',
            'company_website' => 'https://acme.com',
            'admin_name' => 'John Doe',
            'admin_email' => 'john@acme.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ];

        $response = $this->postJson('/api/register-company', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'company' => ['id', 'name', 'slug', 'status'],
                'user' => ['id', 'name', 'email', 'role', 'status'],
                'token',
            ])
            ->assertJsonPath('company.name', 'Acme Corporation')
            ->assertJsonPath('company.slug', 'acme-corporation')
            ->assertJsonPath('user.role', 'company_admin')
            ->assertJsonPath('user.email', 'john@acme.com');

        $this->assertDatabaseHas('companies', [
            'name' => 'Acme Corporation',
            'slug' => 'acme-corporation',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@acme.com',
            'role' => UserRole::CompanyAdmin->value,
        ]);

        Event::assertDispatched(CompanyRegistered::class);
    }

    public function test_can_login_with_valid_credentials(): void
    {
        $company = Company::create([
            'name' => 'Apex Solutions',
            'status' => CompanyStatus::Active,
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Jane Smith',
            'email' => 'jane@apex.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::CompanyAdmin,
            'status' => UserStatus::Active,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'jane@apex.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'user', 'token'])
            ->assertJsonPath('user.email', 'jane@apex.com');
    }

    public function test_inactive_company_cannot_login(): void
    {
        $company = Company::create([
            'name' => 'Suspended Inc',
            'status' => CompanyStatus::Suspended,
        ]);

        User::create([
            'company_id' => $company->id,
            'name' => 'Inactive User',
            'email' => 'inactive@suspended.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::CompanyAdmin,
            'status' => UserStatus::Active,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'inactive@suspended.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_access_me_profile(): void
    {
        $company = Company::create([
            'name' => 'Cloud Nine',
            'status' => CompanyStatus::Active,
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Alice Admin',
            'email' => 'alice@cloudnine.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::CompanyAdmin,
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'alice@cloudnine.com')
            ->assertJsonPath('user.company.name', 'Cloud Nine');
    }

    public function test_company_admin_can_create_and_view_staff_members(): void
    {
        $company = Company::create([
            'name' => 'Globex',
            'status' => CompanyStatus::Active,
        ]);

        $admin = User::create([
            'company_id' => $company->id,
            'name' => 'Admin Globex',
            'email' => 'admin@globex.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::CompanyAdmin,
            'status' => UserStatus::Active,
        ]);

        // Create manager
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/users', [
            'name' => 'Manager Mike',
            'email' => 'mike@globex.com',
            'password' => 'password123',
            'role' => 'manager',
            'phone' => '+1987654321',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.email', 'mike@globex.com')
            ->assertJsonPath('user.role', 'manager');

        // Check index
        $indexResponse = $this->actingAs($admin, 'sanctum')->getJson('/api/users');
        $indexResponse->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_tenant_isolation_prevents_viewing_other_company_users(): void
    {
        $companyA = Company::create(['name' => 'Company A', 'status' => CompanyStatus::Active]);
        $companyB = Company::create(['name' => 'Company B', 'status' => CompanyStatus::Active]);

        $adminA = User::create([
            'company_id' => $companyA->id,
            'name' => 'Admin A',
            'email' => 'admin@companya.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::CompanyAdmin,
            'status' => UserStatus::Active,
        ]);

        $employeeB = User::create([
            'company_id' => $companyB->id,
            'name' => 'Employee B',
            'email' => 'emp@companyb.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Employee,
            'status' => UserStatus::Active,
        ]);

        // Admin A lists users -> should only see user from Company A
        $response = $this->actingAs($adminA, 'sanctum')->getJson('/api/users');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'admin@companya.com');

        // Admin A tries to view employee B directly -> 403 Forbidden
        $directResponse = $this->actingAs($adminA, 'sanctum')->getJson('/api/users/' . $employeeB->id);
        $directResponse->assertStatus(403);
    }
}


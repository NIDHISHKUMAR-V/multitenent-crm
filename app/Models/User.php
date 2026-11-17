<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Company this user belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Determine if the user is a Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    /**
     * Determine if the user is a Company Admin.
     */
    public function isCompanyAdmin(): bool
    {
        return $this->role === UserRole::CompanyAdmin;
    }

    /**
     * Determine if the user is a Manager.
     */
    public function isManager(): bool
    {
        return $this->role === UserRole::Manager;
    }

    /**
     * Determine if the user is an Employee.
     */
    public function isEmployee(): bool
    {
        return $this->role === UserRole::Employee;
    }

    /**
     * Determine if user can manage company settings and staff.
     */
    public function canManageCompany(): bool
    {
        return $this->role?->canManageCompany() ?? false;
    }

    /**
     * Determine if user can assign leads/deals/tasks.
     */
    public function canAssignRecords(): bool
    {
        return $this->role?->canAssignRecords() ?? false;
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(UserRole|string $role): bool
    {
        if ($role instanceof UserRole) {
            return $this->role === $role;
        }

        return $this->role?->value === $role;
    }

    /**
     * Determine if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }
}

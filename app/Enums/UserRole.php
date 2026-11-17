<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case CompanyAdmin = 'company_admin';
    case Manager = 'manager';
    case Employee = 'employee';

    /**
     * Get display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::CompanyAdmin => 'Company Admin',
            self::Manager => 'Manager',
            self::Employee => 'Employee',
        };
    }

    /**
     * Determine if this role has system-wide super admin access.
     */
    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    /**
     * Determine if this role can manage company settings and staff.
     */
    public function canManageCompany(): bool
    {
        return in_array($this, [self::SuperAdmin, self::CompanyAdmin], true);
    }

    /**
     * Determine if this role can assign leads/deals/tasks.
     */
    public function canAssignRecords(): bool
    {
        return in_array($this, [self::SuperAdmin, self::CompanyAdmin, self::Manager], true);
    }
}


<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Authentication & Registration Routes
|--------------------------------------------------------------------------
*/
Route::post('/register-company', [AuthController::class, 'registerCompany'])->name('auth.register-company');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

/*
|--------------------------------------------------------------------------
| Protected Authenticated Routes (Multi-Tenant Scoped)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');

    // User & Staff Management
    Route::apiResource('users', UserController::class);
});

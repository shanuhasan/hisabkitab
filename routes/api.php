<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ReminderController;
use App\Http\Controllers\Api\V1\ShopController;
use App\Http\Controllers\Api\V1\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile App API — UdhaarHisab (v1)
|--------------------------------------------------------------------------
| All routes are prefixed with /api/v1. Authenticate with the Bearer token
| returned by /auth/login or /auth/register. See docs/API.md.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public: get a token
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
    });

    // Logged-in shop owners & staff
    Route::middleware(['auth:sanctum', 'api.shop', 'throttle:120,1'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('/customers/{id}/restore', [CustomerController::class, 'restore'])->whereNumber('id')->name('customers.restore');
        Route::get('/customers/{customer}/transactions', [CustomerController::class, 'transactions'])->name('customers.transactions');

        Route::get('/customers/{customer}/reminder', [ReminderController::class, 'templates'])->name('customers.reminder.templates');
        Route::post('/customers/{customer}/reminder', [ReminderController::class, 'send'])->name('customers.reminder.send');

        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
        Route::post('/transactions/{transaction}/reverse', [TransactionController::class, 'reverse'])->name('transactions.reverse');

        Route::get('/shop', [ShopController::class, 'show'])->name('shop.show');
        Route::put('/shop', [ShopController::class, 'update'])->name('shop.update');
    });
});

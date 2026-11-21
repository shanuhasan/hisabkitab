<?php

use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShopProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WhatsappController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — HisabKitab SaaS
|--------------------------------------------------------------------------
*/

// Public Landing Page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');
});

// Authenticated & Tenant-Active Routes
Route::middleware(['auth', 'tenant.active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Customer Management Module
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}/archive', [CustomerController::class, 'archive'])->name('customers.archive');
    Route::post('/customers/{id}/restore', [CustomerController::class, 'restore'])->name('customers.restore');
    Route::get('/customers/{customer}/ledger', [TransactionController::class, 'customerLedger'])->name('customers.ledger');
    Route::get('/customers/{customer}/statement/pdf', [CustomerController::class, 'statementPdf'])->name('customers.statement.pdf');
    Route::post('/customers/{customer}/whatsapp/remind', [WhatsappController::class, 'sendReminder'])->name('customers.whatsapp.remind');
    Route::get('/customers/{customer}/whatsapp/statement', [WhatsappController::class, 'shareStatement'])->name('customers.whatsapp.statement');

    // Financial Engine & Transaction Routes
    Route::get('/transactions/credit', [TransactionController::class, 'creditForm'])->name('transactions.credit');
    Route::get('/transactions/payment', [TransactionController::class, 'paymentForm'])->name('transactions.payment');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/history', [TransactionController::class, 'history'])->name('transactions.history');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::post('/transactions/{transaction}/reverse', [TransactionController::class, 'reverse'])->name('transactions.reverse');
    Route::get('/transactions/{transaction}/receipt/pdf', [TransactionController::class, 'receiptPdf'])->name('transactions.receipt.pdf');
    Route::get('/transactions/{transaction}/whatsapp/receipt', [WhatsappController::class, 'shareReceipt'])->name('transactions.whatsapp.receipt');

    // Reports & Analytics Routes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');

    // Shop Profile Management (Protected & Isolated)
    Route::get('/shop/profile', [ShopProfileController::class, 'edit'])->name('shop.profile');
    Route::post('/shop/profile', [ShopProfileController::class, 'update'])->name('shop.profile.update');

    // SaaS Subscription Management Routes
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/checkout/order', [SubscriptionController::class, 'createOrder'])->name('subscription.checkout.order');
    Route::post('/subscription/checkout/verify', [SubscriptionController::class, 'verifyPayment'])->name('subscription.checkout.verify');
    Route::post('/subscription/downgrade/free', [SubscriptionController::class, 'switchToFree'])->name('subscription.downgrade.free');
});

// Razorpay Webhook Handler (External, CSRF-exempt)
Route::post('/webhook/razorpay', [SubscriptionController::class, 'handleWebhook'])->name('webhook.razorpay');

// Super Admin Plan Management Routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/{plan}/edit', [AdminPlanController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
});

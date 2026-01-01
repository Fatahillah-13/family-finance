<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\HouseholdMemberController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified', 'active.household'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Budgets
    Route::middleware(['permission:budgets.read'])->group(function () {
        Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
    });
    Route::middleware(['permission:budgets.manage'])->group(function () {
        Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::patch('/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
        Route::post('/budgets/{budget}/toggle', [BudgetController::class, 'toggle'])->name('budgets.toggle');
    });

    Route::middleware(['permission:reports.read'])->group(function () {
        Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
    });

    // Accounts
    Route::middleware(['permission:accounts.read'])->group(function () {
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    });
    Route::middleware(['permission:accounts.manage'])->group(function () {
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::patch('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
        Route::post('/accounts/{account}/toggle', [AccountController::class, 'toggle'])->name('accounts.toggle');
    });

    // Categories
    Route::middleware(['permission:categories.read'])->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    });
    Route::middleware(['permission:categories.manage'])->group(function () {
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::post('/categories/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');
    });

    // Tags
    Route::middleware(['permission:tags.read'])->group(function () {
        Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
    });
    Route::middleware(['permission:tags.manage'])->group(function () {
        Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
        Route::patch('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
        Route::post('/tags/{tag}/toggle', [TagController::class, 'toggle'])->name('tags.toggle');
    });

    // Transactions
    Route::middleware(['permission:transactions.read'])->group(function () {
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    });

    Route::middleware(['permission:transactions.create'])->group(function () {
        Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    });

    Route::middleware(['permission:transactions.update'])->group(function () {
        Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
        Route::patch('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    });

    Route::middleware(['permission:transactions.delete'])->group(function () {
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
        Route::post('/transaction-attachments/{attachment}/delete', [TransactionController::class, 'deleteAttachment'])->name('transactions.attachments.delete');
    });

    Route::middleware(['permission:transactions.read'])->group(function () {
        Route::get('/transaction-attachments/{attachment}/download', [TransactionController::class, 'downloadAttachment'])->name('transactions.attachments.download');
    });
});

Route::middleware(['auth'])->group(function () {
    // ✅ Breeze profile routes (yang hilang)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/households', [HouseholdController::class, 'index'])->name('households.index');
    Route::post('/households', [HouseholdController::class, 'store'])->name('households.store');
    Route::post('/households/{household}/switch', [HouseholdController::class, 'switch'])->name('households.switch');

    Route::middleware(['active.household', 'permission:household.members.manage'])->group(function () {
        Route::get('/households/members', [HouseholdMemberController::class, 'index'])->name('households.members');
        Route::post('/households/members', [HouseholdMemberController::class, 'store'])->name('households.members.store');
        Route::delete('/households/members/{membership}', [HouseholdMemberController::class, 'destroy'])->name('households.members.destroy');
    });
});


require __DIR__ . '/auth.php';

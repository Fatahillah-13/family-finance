<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\HouseholdMemberController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified', 'active.household'])->group(function () {
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

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

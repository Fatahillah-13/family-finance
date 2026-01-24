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
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ImportTransactionController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\HouseholdInvitationController;
use App\Http\Controllers\ReceiptScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

// Route Google auth
Route::middleware('guest')->group(function () {
    Route::get('/auth/google', [SocialAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'callback'])->name('auth.google.callback');
});


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
        // JSON endpoint untuk AJAX
        Route::get('/transactions/data', [TransactionController::class, 'data'])->name('transactions.data');
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

    // Roles
    Route::middleware(['permission:roles.manage'])->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::post('/roles/{role}/toggle', [RoleController::class, 'toggle'])->name('roles.toggle');
    });

    Route::middleware(['permission:permissions.assign'])->group(function () {
        Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.permissions.sync');
    });

    // Update member role (assign role)
    Route::middleware(['permission:household.members.manage'])->group(function () {
        Route::patch('/households/members/{membership}/role', [HouseholdMemberController::class, 'updateRole'])
            ->name('households.members.role.update');
    });

    // Import Transaction
    Route::middleware(['permission:transactions.create'])->group(function () {
        Route::get('/imports/transactions', [ImportTransactionController::class, 'create'])->name('imports.create');
        Route::post('/imports/transactions', [ImportTransactionController::class, 'store'])->name('imports.store');
        Route::get('/imports/transactions/{import}/preview', [ImportTransactionController::class, 'preview'])->name('imports.preview');
        Route::post('/imports/transactions/{import}/commit', [ImportTransactionController::class, 'commit'])->name('imports.commit');
    });

    // Audit Logs
    Route::middleware(['permission:audit.read'])->group(function () {
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    });
});

Route::middleware(['auth'])->group(function () {
    // ✅ Breeze profile routes (yang hilang)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {

    // Households
    Route::get('/households', [HouseholdController::class, 'index'])->name('households.index');
    Route::post('/households', [HouseholdController::class, 'store'])->name('households.store');
    Route::post('/households/{household}/switch', [HouseholdController::class, 'switch'])->name('households.switch');

    // Household Members
    Route::middleware(['active.household', 'permission:household.members.manage'])->group(function () {
        Route::get('/households/members', [HouseholdMemberController::class, 'index'])->name('households.members');
        Route::delete('/households/members/{membership}', [HouseholdMemberController::class, 'destroy'])->name('households.members.destroy');
    });

    // inviter kirim invite (butuh active household + permission)
    Route::middleware(['active.household', 'permission:household.members.manage'])->group(function () {
        Route::post('/households/invitations', [HouseholdInvitationController::class, 'store'])->name('households.invitations.store');
    });

    // penerima buka link & accept/reject
    Route::get('/invitations/{token}', [HouseholdInvitationController::class, 'show'])->name('invitations.show');
    Route::post('/invitations/{token}/accept', [HouseholdInvitationController::class, 'accept'])->name('invitations.accept');
    Route::post('/invitations/{token}/reject', [HouseholdInvitationController::class, 'reject'])->name('invitations.reject');

    Route::post('/transactions/scan-receipt', ReceiptScanController::class)
        ->name('transactions.scan-receipt');
});


require __DIR__ . '/auth.php';

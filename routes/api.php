<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Api\AuthController;

// Management
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CustomerController;

// Dashboard & Profile
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProfileController;

// Loans
use App\Http\Controllers\Api\Loan\LoanController;
use App\Http\Controllers\Api\Loan\LoanApplicationController;
use App\Http\Controllers\Api\Loan\LoanApprovalController;
use App\Http\Controllers\Api\Loan\LoanPaymentController;

// Transactions
use App\Http\Controllers\Api\Transaction\TransactionController;
use App\Http\Controllers\Api\Transaction\GoldPurchaseController;
use App\Http\Controllers\Api\Transaction\GoldSaleController;
use App\Http\Controllers\Api\Transaction\CashTransactionController;

// Finance
use App\Http\Controllers\Api\Finance\BranchBalanceController;
use App\Http\Controllers\Api\Finance\ExpenseController;
use App\Http\Controllers\Api\Finance\ReportController;
use App\Http\Controllers\Api\Finance\ReconciliationController;

// Communication
use App\Http\Controllers\Api\Communication\NotificationController;
use App\Http\Controllers\Api\Communication\WhatsAppController;
use App\Http\Controllers\Api\Communication\SMSController;

// Chat (Web controller returns JSON — safe to reuse)
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| API Routes — Ngemba Mining Management System (Mobile App)
|--------------------------------------------------------------------------
*/

// ============================================
// PUBLIC — Auth entry point
// ============================================
Route::prefix('auth')->group(function () {

    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('password-reset', [AuthController::class, 'resetPassword']);
        Route::post('2fa/verify',     [AuthController::class, 'verifyTwoFactor']);
        Route::post('2fa/resend',     [AuthController::class, 'resendTwoFactor']);
    });
});

// ============================================
// AUTHENTICATED — Full access token required
// ============================================
Route::middleware('auth:sanctum')->group(function () {

    // ---------- Auth / Profile ----------
    Route::prefix('auth')->group(function () {
        Route::post('logout',             [AuthController::class, 'logout']);
        Route::get('profile',             [AuthController::class, 'profile']);
        Route::post('change-password',    [AuthController::class, 'changePassword']);
        Route::post('update-profile',     [AuthController::class, 'updateProfile']);
        Route::post('devices/logout-all', [AuthController::class, 'logoutAllDevices']);
    });

    // ---------- Dashboard ----------
    Route::prefix('dashboard')->group(function () {
        Route::get('/',              [DashboardController::class, 'index']);
        Route::get('/stats',         [DashboardController::class, 'stats']);
        Route::get('/chart-data',    [DashboardController::class, 'chartData']);
        Route::get('/notifications', [DashboardController::class, 'notifications']);
    });

    // ---------- Profile ----------
    Route::prefix('profile')->group(function () {
        Route::get('/',             [ProfileController::class, 'show']);
        Route::put('/',             [ProfileController::class, 'update']);
        Route::put('/password',     [ProfileController::class, 'updatePassword']);
        Route::post('/avatar',      [ProfileController::class, 'updateAvatar']);
        Route::post('/2fa/enable',  [ProfileController::class, 'twoFactorEnable']);
        Route::post('/2fa/disable', [ProfileController::class, 'twoFactorDisable']);
    });

    // ============================================
    // MANAGEMENT
    // ============================================
    Route::prefix('management')->group(function () {

        // Users
        Route::middleware('can:manage_user')->group(function () {
            Route::get('users',                       [UserController::class, 'list']);
            Route::post('users',                      [UserController::class, 'store']);
            Route::get('users/{id}',                  [UserController::class, 'profile']);
            Route::put('users/{id}',                  [UserController::class, 'update']);
            Route::delete('users/{id}',               [UserController::class, 'delete']);
            Route::post('users/{id}/role',            [UserController::class, 'changeRole']);
            Route::post('users/{id}/reset-password',  [UserController::class, 'resetPassword']);
            Route::post('users/{id}/toggle-status',   [UserController::class, 'toggleStatus']);
        });

        // Roles
        Route::middleware('can:manage_role|manage_user')->group(function () {
            Route::get('roles',                    [RolesController::class, 'list']);
            Route::post('roles',                   [RolesController::class, 'store']);
            Route::get('roles/{id}',               [RolesController::class, 'show']);
            Route::put('roles/{id}',               [RolesController::class, 'update']);
            Route::delete('roles/{id}',            [RolesController::class, 'delete']);
            Route::post('roles/{id}/permissions',  [RolesController::class, 'changePermissions']);
        });

        // Permissions
        Route::middleware('can:manage_permission|manage_user')->group(function () {
            Route::get('permissions',         [PermissionController::class, 'list']);
            Route::post('permissions',        [PermissionController::class, 'store']);
            Route::get('permissions/{id}',    [PermissionController::class, 'show']);
            Route::delete('permissions/{id}', [PermissionController::class, 'delete']);
        });

        // Branches
        Route::middleware('can:manage_branch|manage_user')->group(function () {
            Route::get('branches',              [BranchController::class, 'list']);
            Route::post('branches',             [BranchController::class, 'store']);
            Route::get('branches/{id}',         [BranchController::class, 'show']);
            Route::put('branches/{id}',         [BranchController::class, 'update']);
            Route::delete('branches/{id}',      [BranchController::class, 'delete']);
            Route::get('branches/{id}/balance', [BranchController::class, 'balance']);
        });
    });

    // ============================================
    // SUPPLIERS
    // ============================================
    Route::prefix('suppliers')->group(function () {
        Route::get('/',       [SupplierController::class, 'list']);
        Route::post('/',      [SupplierController::class, 'store']);
        Route::get('{id}',    [SupplierController::class, 'show']);
        Route::put('{id}',    [SupplierController::class, 'update']);
        Route::delete('{id}', [SupplierController::class, 'delete']);
    });

    // ============================================
    // CUSTOMERS
    // ============================================
    Route::prefix('customers')->group(function () {
        Route::get('/',       [CustomerController::class, 'list']);
        Route::post('/',      [CustomerController::class, 'store']);
        Route::get('{id}',    [CustomerController::class, 'show']);
        Route::put('{id}',    [CustomerController::class, 'update']);
        Route::delete('{id}', [CustomerController::class, 'delete']);
    });

    // ============================================
    // LOANS
    // ============================================
    Route::prefix('loans')->group(function () {

        Route::prefix('applications')->group(function () {
            Route::get('/',       [LoanApplicationController::class, 'index']);
            Route::post('/',      [LoanApplicationController::class, 'store']);
            Route::get('{id}',    [LoanApplicationController::class, 'show']);
            Route::put('{id}',    [LoanApplicationController::class, 'update']);
            Route::delete('{id}', [LoanApplicationController::class, 'destroy']);
        });

        Route::prefix('approvals')->group(function () {
            Route::get('/',             [LoanApprovalController::class, 'index']);
            Route::get('pending',       [LoanApprovalController::class, 'pending']);
            Route::get('{id}/review',   [LoanApprovalController::class, 'review']);
            Route::post('{id}/approve', [LoanApprovalController::class, 'approve']);
            Route::post('{id}/reject',  [LoanApprovalController::class, 'reject']);
            Route::post('{id}/hold',    [LoanApprovalController::class, 'hold']);
        });

        Route::prefix('payments')->group(function () {
            Route::get('/',            [LoanPaymentController::class, 'index']);
            Route::post('/',           [LoanPaymentController::class, 'store']);
            Route::get('{id}',         [LoanPaymentController::class, 'show']);
            Route::post('{id}/verify', [LoanPaymentController::class, 'verify']);
            Route::delete('{id}',      [LoanPaymentController::class, 'destroy']);
        });

        Route::get('active',        [LoanController::class, 'active']);
        Route::get('completed',     [LoanController::class, 'completed']);
        Route::get('overdue',       [LoanController::class, 'overdue']);
        Route::get('search',        [LoanController::class, 'search']);
        Route::get('status-counts', [LoanController::class, 'statusCounts']);

        Route::get('/',             [LoanController::class, 'index']);
        Route::get('{id}',          [LoanController::class, 'show']);
        Route::get('{id}/payments', [LoanController::class, 'getPayments']);
        Route::post('{id}/remind',  [LoanController::class, 'sendReminder']);
    });

    // ============================================
    // TRANSACTIONS
    // ============================================
    Route::prefix('transactions')->group(function () {

        Route::prefix('gold-purchases')->group(function () {
            Route::get('/',             [GoldPurchaseController::class, 'index']);
            Route::post('/',            [GoldPurchaseController::class, 'store']);
            Route::get('{id}',          [GoldPurchaseController::class, 'show']);
            Route::put('{id}',          [GoldPurchaseController::class, 'update']);
            Route::delete('{id}',       [GoldPurchaseController::class, 'destroy']);
            Route::post('{id}/receive', [GoldPurchaseController::class, 'receive']);
        });

        Route::prefix('gold-sales')->group(function () {
            Route::get('/',              [GoldSaleController::class, 'index']);
            Route::post('/',             [GoldSaleController::class, 'store']);
            Route::get('{id}',           [GoldSaleController::class, 'show']);
            Route::put('{id}',           [GoldSaleController::class, 'update']);
            Route::delete('{id}',        [GoldSaleController::class, 'destroy']);
            Route::post('{id}/complete', [GoldSaleController::class, 'complete']);
        });

        Route::prefix('cash')->group(function () {
            Route::get('/',          [CashTransactionController::class, 'index']);
            Route::post('deposit',   [CashTransactionController::class, 'storeDeposit']);
            Route::post('withdraw',  [CashTransactionController::class, 'storeWithdraw']);
            Route::get('transfers',  [CashTransactionController::class, 'transfers']);
            Route::post('transfers', [CashTransactionController::class, 'storeTransfer']);
        });

        Route::get('summary',       [TransactionController::class, 'summary']);
        Route::get('search',        [TransactionController::class, 'search']);
        Route::get('daily-summary', [TransactionController::class, 'dailySummary']);
        Route::get('gold-price',    [TransactionController::class, 'getGoldPrice']);

        Route::get('/',             [TransactionController::class, 'index']);
        Route::get('{id}',          [TransactionController::class, 'show']);
        Route::post('{id}/reverse', [TransactionController::class, 'reverse']);
        Route::delete('{id}',       [TransactionController::class, 'destroy']);
    });

    // ============================================
    // FINANCE
    // ============================================
    Route::prefix('finance')->group(function () {

        // Balances
        Route::prefix('balances')->group(function () {
            Route::get('summary',     [BranchBalanceController::class, 'summary']);
            Route::get('history',     [BranchBalanceController::class, 'history']);
            Route::get('/',           [BranchBalanceController::class, 'index']);
            Route::get('branch/{id}', [BranchBalanceController::class, 'branch']);
        });

        // Expenses
        Route::prefix('expenses')->group(function () {
            Route::get('/',       [ExpenseController::class, 'index']);
            Route::post('/',      [ExpenseController::class, 'store']);
            Route::get('{id}',    [ExpenseController::class, 'show']);
            Route::put('{id}',    [ExpenseController::class, 'update']);
            Route::delete('{id}', [ExpenseController::class, 'destroy']);

            Route::get('categories',    [ExpenseController::class, 'categories']);
            Route::get('stats',         [ExpenseController::class, 'getStats']);
            Route::get('pending-count', [ExpenseController::class, 'pendingApprovals']);

            Route::prefix('approvals')->group(function () {
                Route::get('pending',       [ExpenseController::class, 'pendingApprovals']);
                Route::post('{id}/approve', [ExpenseController::class, 'approve']);
                Route::post('{id}/reject',  [ExpenseController::class, 'reject']);
                Route::post('{id}/hold',    [ExpenseController::class, 'hold']);
            });
        });

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('daily',         [ReportController::class, 'daily']);
            Route::get('weekly',        [ReportController::class, 'weekly']);
            Route::get('monthly',       [ReportController::class, 'monthly']);
            Route::get('branch',        [ReportController::class, 'branch']);
            Route::get('cash-flow',     [ReportController::class, 'cashFlow']);
            Route::get('profit-loss',   [ReportController::class, 'profitLoss']);
            Route::get('balance-sheet', [ReportController::class, 'balanceSheet']);
        });

        // Reconciliations
        Route::prefix('reconciliations')->group(function () {
            Route::get('/',                 [ReconciliationController::class, 'index']);
            Route::post('cash',             [ReconciliationController::class, 'storeCash']);
            Route::post('gold',             [ReconciliationController::class, 'storeGold']);
            Route::post('cash/{id}/verify', [ReconciliationController::class, 'verifyCash']);
            Route::post('cash/{id}/reject', [ReconciliationController::class, 'rejectCash']);
        });
    });

    // ============================================
    // COMMUNICATION
    // ============================================
    Route::prefix('notifications')->group(function () {
        Route::get('/',          [NotificationController::class, 'index']);
        Route::get('unread',     [NotificationController::class, 'unread']);
        Route::get('count',      [NotificationController::class, 'getUnreadCounts']);
        Route::post('read-all',  [NotificationController::class, 'markAllAsRead']);
        Route::post('{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('{id}',    [NotificationController::class, 'destroy']);
    });

    Route::prefix('chat')->group(function () {
        Route::get('contacts',   [ChatController::class, 'getContacts']);
        Route::get('available',  [ChatController::class, 'getAvailableContacts']);
        Route::get('messages',   [ChatController::class, 'getMessages']);
        Route::post('send',      [ChatController::class, 'sendMessage']);
        Route::post('mark-read', [ChatController::class, 'markAsRead']);
        Route::get('updates',    [ChatController::class, 'getUpdates']);
        Route::post('status',    [ChatController::class, 'updateStatus']);
        Route::get('unread',     [ChatController::class, 'getUnreadCounts']);
    });

    Route::prefix('whatsapp')->group(function () {
        Route::get('/',              [WhatsAppController::class, 'index']);
        Route::post('send-text',     [WhatsAppController::class, 'sendText']);
        Route::post('send-template', [WhatsAppController::class, 'sendTemplate']);
    });

    Route::prefix('sms')->group(function () {
        Route::get('/',     [SMSController::class, 'index']);
        Route::post('send', [SMSController::class, 'send']);
    });

    // ============================================
    // LOOKUPS (dropdown data)
    // ============================================
    Route::prefix('lookup')->group(function () {
        Route::get('branches',           [BranchController::class, 'lookup']);
        Route::get('users',              [UserController::class, 'lookup']);
        Route::get('customers',          [CustomerController::class, 'lookup']);
        Route::get('suppliers',          [SupplierController::class, 'lookup']);
        Route::get('expense-categories', [ExpenseController::class, 'lookupCategories']);
    });
});

// ============================================
// WHATSAPP WEBHOOK (public, unauthenticated)
// ============================================
Route::prefix('whatsapp')->group(function () {
    Route::get('webhook',  [WhatsAppController::class, 'verifyWebhook']);
    Route::post('webhook', [WhatsAppController::class, 'receiveWebhook']);
});
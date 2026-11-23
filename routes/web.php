<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\SmsPasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Email\WebmailController;
use App\Http\Controllers\Luna\LunaController;

// Loan Module Controllers
use App\Http\Controllers\Loan\LoanController;
use App\Http\Controllers\Loan\LoanApplicationController;
use App\Http\Controllers\Loan\LoanApprovalController;
use App\Http\Controllers\Loan\LoanPaymentController;
use App\Http\Controllers\Loan\LoanDocumentController;

// Transaction Module Controllers
use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Controllers\Transaction\GoldPurchaseController;
use App\Http\Controllers\Transaction\GoldSaleController;
use App\Http\Controllers\Transaction\CashTransactionController;

// Communication Module Controllers
use App\Http\Controllers\Communication\SMSController;
use App\Http\Controllers\Communication\EmailController;
use App\Http\Controllers\Communication\WhatsAppController;
use App\Http\Controllers\Communication\NotificationController;

// Finance Module Controllers
use App\Http\Controllers\Finance\BranchBalanceController;
use App\Http\Controllers\Finance\ReconciliationController;
use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\Finance\ReportController;
use App\Http\Controllers\Finance\TrialBalanceController;
use App\Http\Controllers\Finance\AssetController;

// HR Module Controllers
use App\Http\Controllers\HR\HRController;

// Management Controllers
use App\Http\Controllers\Management\UserController;
use App\Http\Controllers\Management\RolesController;
use App\Http\Controllers\Management\PermissionController;
use App\Http\Controllers\Management\BranchController;
use App\Http\Controllers\Management\SupplierController;
use App\Http\Controllers\Management\CustomerController;

// Branch Controller for My Branch
use App\Http\Controllers\Branch\BranchController as MyBranchController;

// Live View Controllers
use App\Http\Controllers\Live\LiveController;

/*
|--------------------------------------------------------------------------
| Web Routes - Ngemba Mining Management System
|--------------------------------------------------------------------------
*/

// ============================================
// PUBLIC ROUTES (No Authentication Required)
// ============================================
Route::get('/', function () {
    return redirect()->route('login');
});

// ============================================
// AUTHENTICATION ROUTES
// ============================================
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login'])->name('login.post');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Password Reset Routes — Email flow
Route::get('password/forget', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

// Registration (if enabled)
Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register'])->name('register.post');

// ============================================
// PASSWORD RESET — SMS FLOW
// ============================================
Route::prefix('password')->name('password.')->middleware('guest')->group(function () {
    Route::post('/sms/send',   [SmsPasswordResetController::class, 'sendCode'])->name('sms.send');
    Route::get('/sms/verify',  [SmsPasswordResetController::class, 'showVerifyForm'])->name('sms.verify.form');
    Route::post('/sms/verify', [SmsPasswordResetController::class, 'verifyCode'])->name('sms.verify');
    Route::get('/sms/reset',   [SmsPasswordResetController::class, 'showResetForm'])->name('sms.reset.form');
    Route::post('/sms/reset',  [SmsPasswordResetController::class, 'resetPassword'])->name('sms.reset');
    Route::post('/sms/resend', [SmsPasswordResetController::class, 'resendCode'])->name('sms.resend');
});

// ============================================
// TWO-FACTOR AUTHENTICATION — CHALLENGE
// ============================================
Route::middleware('guest')->prefix('2fa')->name('2fa.')->group(function () {
    Route::get('/challenge',  [TwoFactorController::class, 'showChallenge'])->name('challenge');
    Route::post('/verify',    [TwoFactorController::class, 'verify'])->name('verify');
    Route::post('/resend',    [TwoFactorController::class, 'resendSms'])->name('resend');
});

// ============================================
// AUTHENTICATED ROUTES
// ============================================
Route::group(['middleware' => ['auth']], function() {

    // 2FA MANAGEMENT
    Route::prefix('2fa')->name('2fa.')->group(function () {
        Route::get('/setup',           [TwoFactorController::class, 'setup'])->name('setup');
        Route::post('/confirm',        [TwoFactorController::class, 'confirm'])->name('confirm');
        Route::post('/confirm-sms',    [TwoFactorController::class, 'confirmSms'])->name('confirm.sms');
        Route::get('/recovery-codes',  [TwoFactorController::class, 'showRecoveryCodes'])->name('recovery-codes');
        Route::post('/disable',        [TwoFactorController::class, 'disable'])->name('disable');
    });

    // DASHBOARD & PROFILE
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');
    Route::get('/clear-cache', [DashboardController::class, 'clearCache'])->name('clear.cache');

    Route::prefix('profile')->name('profile.')->group(function() {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::put('/update', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])->name('avatar');

        // Enable / disable SMS two-factor from the profile page
        Route::post('/two-factor/send',    [ProfileController::class, 'twoFactorSend'])->name('2fa.send');
        Route::post('/two-factor/enable',  [ProfileController::class, 'twoFactorEnable'])->name('2fa.enable');
        Route::post('/two-factor/cancel',  [ProfileController::class, 'twoFactorCancel'])->name('2fa.cancel');
        Route::post('/two-factor/disable', [ProfileController::class, 'twoFactorDisable'])->name('2fa.disable');
    });

    // CHAT
    Route::prefix('chat')->name('chat.')->group(function() {
        Route::get('/contacts', [ChatController::class, 'getContacts'])->name('contacts');
        Route::get('/messages', [ChatController::class, 'getMessages'])->name('messages');
        Route::post('/send', [ChatController::class, 'sendMessage'])->name('send');
        Route::post('/mark-read', [ChatController::class, 'markAsRead'])->name('mark-read');
        Route::get('/updates', [ChatController::class, 'getUpdates'])->name('updates');
        Route::post('/status', [ChatController::class, 'updateStatus'])->name('status');
        Route::get('/available', [ChatController::class, 'getAvailableContacts'])->name('available');
        Route::get('/unread', [ChatController::class, 'getUnreadCounts'])->name('unread');
    });

    // EMAIL / WEBMAIL
    Route::prefix('email')->name('email.')->group(function() {
        Route::get('/', [WebmailController::class, 'index'])->name('index');
        Route::get('/settings', [WebmailController::class, 'settings'])->name('settings');
        Route::post('/settings', [WebmailController::class, 'updateSettings'])->name('settings.update');
        Route::post('/test', [WebmailController::class, 'testConnection'])->name('test');

        Route::prefix('api')->name('api.')->group(function() {
            Route::get('/folder', [WebmailController::class, 'getFolder'])->name('folder');
            Route::get('/message', [WebmailController::class, 'getMessage'])->name('message');
            Route::post('/send', [WebmailController::class, 'send'])->name('send');
            Route::delete('/delete', [WebmailController::class, 'delete'])->name('delete');
            Route::post('/move', [WebmailController::class, 'move'])->name('move');
        });
    });

    // LUNA OS
    Route::prefix('luna')->name('luna.')->group(function() {
        Route::get('/', [LunaController::class, 'index'])->name('index');
        Route::post('/send', [LunaController::class, 'sendMessage'])->name('send');
        Route::get('/conversations', [LunaController::class, 'getConversations'])->name('conversations');
        Route::get('/conversation/{id}', [LunaController::class, 'getConversation'])->name('conversation');
        Route::delete('/conversation/{id}', [LunaController::class, 'deleteConversation'])->name('conversation.delete');
        Route::get('/suggestions', [LunaController::class, 'getSuggestions'])->name('suggestions');
    });

    // LIVE VIEW
    Route::prefix('live')->name('live.')->group(function() {
        Route::get('/', [LiveController::class, 'index'])->name('index');

        Route::prefix('cameras')->name('cameras.')->group(function() {
            Route::get('/', [LiveController::class, 'cameras'])->name('index');
            Route::get('/stream/{id}', [LiveController::class, 'stream'])->name('stream');
            Route::get('/snapshot/{id}', [LiveController::class, 'snapshot'])->name('snapshot');
            Route::post('/control/{id}', [LiveController::class, 'control'])->name('control');
            Route::get('/layout/{layout}', [LiveController::class, 'layout'])->name('layout');
            Route::get('/grid', [LiveController::class, 'grid'])->name('grid');
        });

        Route::prefix('charts')->name('charts.')->group(function() {
            Route::get('/', [LiveController::class, 'charts'])->name('index');
            Route::get('/transactions', [LiveController::class, 'chartTransactions'])->name('transactions');
            Route::get('/sales', [LiveController::class, 'chartSales'])->name('sales');
            Route::get('/purchases', [LiveController::class, 'chartPurchases'])->name('purchases');
            Route::get('/expenses', [LiveController::class, 'chartExpenses'])->name('expenses');
            Route::get('/loans', [LiveController::class, 'chartLoans'])->name('loans');
            Route::get('/branch/{branch_id}', [LiveController::class, 'chartBranch'])->name('branch');
            Route::get('/realtime', [LiveController::class, 'chartRealtime'])->name('realtime');
            Route::get('/export', [LiveController::class, 'chartExport'])->name('export');
        });

        Route::prefix('data')->name('data.')->group(function() {
            Route::get('/dashboard', [LiveController::class, 'apiDashboard'])->name('dashboard');
            Route::get('/transactions', [LiveController::class, 'apiTransactions'])->name('transactions');
            Route::get('/cameras/status', [LiveController::class, 'apiCameraStatus'])->name('cameras.status');
            Route::get('/notifications', [LiveController::class, 'apiNotifications'])->name('notifications');
        });
    });

    // ============================================
    // LOANS
    // ============================================
    Route::prefix('loans')->name('loans.')->group(function() {
        Route::prefix('applications')->name('applications.')->group(function() {
            Route::get('/', [LoanApplicationController::class, 'index'])->name('index');
            Route::get('/create', [LoanApplicationController::class, 'create'])->name('create');
            Route::post('/', [LoanApplicationController::class, 'store'])->name('store');
            Route::get('/{id}', [LoanApplicationController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [LoanApplicationController::class, 'edit'])->name('edit');
            Route::put('/{id}', [LoanApplicationController::class, 'update'])->name('update');
            Route::delete('/{id}', [LoanApplicationController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('approvals')->name('approvals.')->group(function() {
            Route::get('/', [LoanApprovalController::class, 'index'])->name('index');
            Route::get('/pending', [LoanApprovalController::class, 'pending'])->name('pending');
            Route::post('/{id}/approve', [LoanApprovalController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [LoanApprovalController::class, 'reject'])->name('reject');
            Route::post('/{id}/hold', [LoanApprovalController::class, 'hold'])->name('hold');
            Route::get('/{id}/review', [LoanApprovalController::class, 'review'])->name('review');
        });

        Route::prefix('payments')->name('payments.')->group(function() {
            Route::get('/', [LoanPaymentController::class, 'index'])->name('index');
            Route::get('/create/{loan_id?}', [LoanPaymentController::class, 'create'])->name('create');
            Route::post('/', [LoanPaymentController::class, 'store'])->name('store');
            Route::get('/{id}', [LoanPaymentController::class, 'show'])->name('show');
            Route::post('/{id}/verify', [LoanPaymentController::class, 'verify'])->name('verify');
            Route::delete('/{id}', [LoanPaymentController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('documents')->name('documents.')->group(function() {
            Route::get('/{loan_id}', [LoanDocumentController::class, 'index'])->name('index');
            Route::post('/upload', [LoanDocumentController::class, 'upload'])->name('upload');
            Route::delete('/{id}', [LoanDocumentController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/download', [LoanDocumentController::class, 'download'])->name('download');
        });

        // 🔧 FIXED: static paths MUST come before /{id} wildcard
        Route::get('/active', [LoanController::class, 'active'])->name('active');
        Route::get('/completed', [LoanController::class, 'completed'])->name('completed');
        Route::get('/overdue', [LoanController::class, 'overdue'])->name('overdue');
        Route::get('/reports', [LoanController::class, 'reports'])->name('reports');
        Route::get('/export', [LoanController::class, 'export'])->name('export');
        Route::get('/api/loan-details/{id}', [LoanPaymentController::class, 'getLoanDetails'])->name('api.loan.details');

        // Wildcard LAST
        Route::get('/', [LoanController::class, 'index'])->name('index');
        Route::get('/{id}', [LoanController::class, 'show'])->name('show');
        Route::post('/{id}/remind', [LoanController::class, 'sendReminder'])->name('remind');
    });

    // ============================================
    // TRANSACTIONS
    // 🔧 FIXED: added destroy route, reordered statics before wildcards
    // ============================================
    Route::prefix('transactions')->name('transactions.')->group(function() {

        // ----- Gold Purchases -----
        Route::prefix('gold-purchases')->name('gold.purchases.')->group(function() {
            Route::get('/reports', [GoldPurchaseController::class, 'reports'])->name('reports');
            Route::get('/create', [GoldPurchaseController::class, 'create'])->name('create');
            Route::post('/', [GoldPurchaseController::class, 'store'])->name('store');

            Route::get('/', [GoldPurchaseController::class, 'index'])->name('index');
            Route::get('/{id}', [GoldPurchaseController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [GoldPurchaseController::class, 'edit'])->name('edit');
            Route::put('/{id}', [GoldPurchaseController::class, 'update'])->name('update');
            Route::delete('/{id}', [GoldPurchaseController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/receive', [GoldPurchaseController::class, 'receive'])->name('receive');
        });

        // ----- Gold Sales -----
        Route::prefix('gold-sales')->name('gold.sales.')->group(function() {
            Route::get('/reports', [GoldSaleController::class, 'reports'])->name('reports');
            Route::get('/create', [GoldSaleController::class, 'create'])->name('create');
            Route::post('/', [GoldSaleController::class, 'store'])->name('store');

            Route::get('/', [GoldSaleController::class, 'index'])->name('index');
            Route::get('/{id}', [GoldSaleController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [GoldSaleController::class, 'edit'])->name('edit');
            Route::put('/{id}', [GoldSaleController::class, 'update'])->name('update');
            Route::delete('/{id}', [GoldSaleController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/complete', [GoldSaleController::class, 'complete'])->name('complete');
        });

        // ----- Cash -----
        Route::prefix('cash')->name('cash.')->group(function() {
            Route::get('/', [CashTransactionController::class, 'index'])->name('index');
            Route::get('/deposit', [CashTransactionController::class, 'depositForm'])->name('deposit');
            Route::post('/deposit', [CashTransactionController::class, 'storeDeposit'])->name('deposit.store');
            Route::get('/withdraw', [CashTransactionController::class, 'withdrawForm'])->name('withdraw');
            Route::post('/withdraw', [CashTransactionController::class, 'storeWithdraw'])->name('withdraw.store');
            Route::get('/transfers', [CashTransactionController::class, 'transfers'])->name('transfers');
            Route::post('/transfers', [CashTransactionController::class, 'storeTransfer'])->name('transfers.store');
        });

        // ----- Generic transactions (statics BEFORE wildcards) -----
        Route::get('/export',  [TransactionController::class, 'export'])->name('export');
        Route::get('/summary', [TransactionController::class, 'summary'])->name('summary');

        Route::get('/',              [TransactionController::class, 'index'])->name('index');
        Route::get('/{id}',          [TransactionController::class, 'show'])->name('show');
        Route::post('/{id}/reverse', [TransactionController::class, 'reverse'])->name('reverse');
        Route::delete('/{id}',       [TransactionController::class, 'destroy'])->name('destroy'); // 🔧 FIXED
    });

    // ============================================
    // COMMUNICATION MODULE
    // ============================================
    Route::prefix('communication')->name('communication.')->group(function() {

        // ---------- SMS ----------
        Route::prefix('sms')->name('sms.')->group(function() {
            Route::get('/send', [SMSController::class, 'sendForm'])->name('send');
            Route::get('/templates', [SMSController::class, 'templates'])->name('templates');
            Route::post('/templates', [SMSController::class, 'storeTemplate'])->name('templates.store');
            Route::put('/templates/{id}', [SMSController::class, 'updateTemplate'])->name('templates.update');
            Route::delete('/templates/{id}', [SMSController::class, 'deleteTemplate'])->name('templates.delete');
            Route::get('/settings', [SMSController::class, 'settings'])->name('settings');
            Route::post('/settings', [SMSController::class, 'updateSettings'])->name('settings.update');
            Route::post('/send', [SMSController::class, 'send'])->name('send.post');
            Route::get('/logs', [SMSController::class, 'logs'])->name('logs');
            Route::get('/balance', [SMSController::class, 'checkBalance'])->name('balance');
            Route::post('/bulk', [SMSController::class, 'bulkSend'])->name('bulk');
            Route::get('/balance/refresh', [SMSController::class, 'refreshBalance'])->name('balance.refresh');
            Route::get('/sms/debug', [SMSController::class, 'debugBalance']);

            Route::get('/', [SMSController::class, 'index'])->name('index');
        });

        // ---------- EMAIL ----------
        Route::prefix('email')->name('email.')->group(function() {
            Route::get('/compose', [EmailController::class, 'compose'])->name('compose');
            Route::post('/send', [EmailController::class, 'send'])->name('send');
            Route::get('/inbox', [EmailController::class, 'inbox'])->name('inbox');
            Route::get('/sent', [EmailController::class, 'sent'])->name('sent');
            Route::get('/templates', [EmailController::class, 'templates'])->name('templates');
            Route::post('/templates', [EmailController::class, 'storeTemplate'])->name('templates.store');
            Route::get('/settings', [EmailController::class, 'settings'])->name('settings');
            Route::post('/settings', [EmailController::class, 'updateSettings'])->name('settings.update');

            Route::get('/', [EmailController::class, 'index'])->name('index');
            Route::get('/{id}', [EmailController::class, 'show'])->name('show');
            Route::post('/{id}/reply', [EmailController::class, 'reply'])->name('reply');
        });

        // ---------- WHATSAPP ----------
        Route::prefix('whatsapp')->name('whatsapp.')->group(function() {
            Route::get('/logs',    [WhatsAppController::class, 'index'])->name('logs');
            Route::get('/send',    [WhatsAppController::class, 'sendForm'])->name('send');
            Route::post('/send',   [WhatsAppController::class, 'send'])->name('send.post');
            Route::post('/bulk',   [WhatsAppController::class, 'bulkSend'])->name('bulk');
            Route::get('/templates',        [WhatsAppController::class, 'templates'])->name('templates');
            Route::post('/templates',       [WhatsAppController::class, 'storeTemplate'])->name('templates.store');
            Route::get('/settings',  [WhatsAppController::class, 'settings'])->name('settings');
            Route::post('/settings', [WhatsAppController::class, 'updateSettings'])->name('settings.update');
            Route::get('/scheduled',           [WhatsAppController::class, 'scheduled'])->name('scheduled');
            Route::post('/scheduled',          [WhatsAppController::class, 'storeScheduled'])->name('scheduled.store');
            Route::delete('/scheduled/{id}',   [WhatsAppController::class, 'deleteScheduled'])->name('scheduled.delete');
            Route::get('/status/refresh', [WhatsAppController::class, 'refreshStatus'])->name('status.refresh');

            Route::get('/',        [WhatsAppController::class, 'index'])->name('index');
        });

        // ---------- NOTIFICATIONS ----------
        // 🔧 FIXED: merged the "missing" routes in — added /count and
        // kept BOTH old + new naming styles as aliases to avoid breaking views.
        Route::prefix('notifications')->name('notifications.')->group(function() {
            // Static segments first (avoids being swallowed by /{id})
            Route::get('/unread',         [NotificationController::class, 'unread'])->name('unread');
            Route::get('/count',          [NotificationController::class, 'getUnreadCounts'])->name('count'); // 🔧 NEW
            Route::post('/read-all',      [NotificationController::class, 'markAllAsRead'])->name('read.all');
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read'); // 🔧 alias
            Route::get('/broadcast',      [NotificationController::class, 'showBroadcastForm'])->name('broadcast');
            Route::post('/broadcast',     [NotificationController::class, 'broadcast'])->name('broadcast.send');
            Route::post('/broadcast',     [NotificationController::class, 'broadcast'])->name('broadcast.store'); // 🔧 alias

            // Wildcards last
            Route::post('/{id}/read',      [NotificationController::class, 'markAsRead'])->name('read');
            Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('mark-read'); // 🔧 alias
            Route::delete('/{id}',         [NotificationController::class, 'destroy'])->name('destroy');

            Route::get('/', [NotificationController::class, 'index'])->name('index');
        });
    });

    // ============================================
    // FINANCE MODULE
    // ============================================
    Route::prefix('finance')->name('finance.')->group(function() {

        Route::prefix('balances')->name('balances.')->group(function() {
            Route::get('/summary', [BranchBalanceController::class, 'summary'])->name('summary');
            Route::get('/history', [BranchBalanceController::class, 'history'])->name('history');
            Route::get('/branch/{branch_id}', [BranchBalanceController::class, 'branch'])->name('branch');
            Route::post('/adjust/{branch_id}', [BranchBalanceController::class, 'adjust'])->name('adjust');
            Route::get('/', [BranchBalanceController::class, 'index'])->name('index');
        });

        Route::prefix('reconciliations')->name('reconciliations.')->group(function() {
            Route::get('/print', [ReconciliationController::class, 'print'])->name('print');
            Route::get('/export', [ReconciliationController::class, 'export'])->name('export');
            Route::get('/export-pdf', [ReconciliationController::class, 'exportPdf'])->name('export-pdf');
            Route::get('/reports', [ReconciliationController::class, 'reports'])->name('reports');
            Route::get('/cash/create', [ReconciliationController::class, 'createCash'])->name('cash.create');
            Route::post('/cash', [ReconciliationController::class, 'storeCash'])->name('cash.store');
            Route::get('/cash/{id}', [ReconciliationController::class, 'showCash'])->name('cash.show');
            Route::post('/cash/{id}/verify', [ReconciliationController::class, 'verifyCash'])->name('cash.verify');
            Route::post('/cash/{id}/reject', [ReconciliationController::class, 'rejectCash'])->name('cash.reject');
            Route::get('/gold/create', [ReconciliationController::class, 'createGold'])->name('gold.create');
            Route::post('/gold', [ReconciliationController::class, 'storeGold'])->name('gold.store');
            Route::get('/gold/{id}', [ReconciliationController::class, 'showGold'])->name('gold.show');
            Route::post('/gold/{id}/verify', [ReconciliationController::class, 'verifyGold'])->name('gold.verify');
            Route::post('/gold/{id}/reject', [ReconciliationController::class, 'rejectGold'])->name('gold.reject');
            Route::get('/', [ReconciliationController::class, 'index'])->name('index');
        });

        Route::prefix('expenses')->name('expenses.')->group(function() {
            // Static segments first
            Route::get('/create', [ExpenseController::class, 'create'])->name('create');
            Route::get('/print', [ExpenseController::class, 'print'])->name('print');
            Route::get('/export', [ExpenseController::class, 'export'])->name('export');

            Route::get('/categories', [ExpenseController::class, 'categories'])->name('categories.index');
            Route::get('/categories/create', [ExpenseController::class, 'createCategory'])->name('categories.create');
            Route::post('/categories', [ExpenseController::class, 'storeCategory'])->name('categories.store');
            Route::get('/categories/{id}/edit', [ExpenseController::class, 'editCategory'])->name('categories.edit');
            Route::put('/categories/{id}', [ExpenseController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/categories/{id}', [ExpenseController::class, 'destroyCategory'])->name('categories.destroy');
            Route::post('/categories/{id}/toggle', [ExpenseController::class, 'toggleCategory'])->name('categories.toggle');

            Route::prefix('approvals')->name('approvals.')->group(function() {
                Route::get('/pending', [ExpenseController::class, 'pendingApprovals'])->name('pending');
                Route::post('/{id}/approve', [ExpenseController::class, 'approve'])->name('approve');
                Route::post('/{id}/reject', [ExpenseController::class, 'reject'])->name('reject');
                Route::post('/{id}/hold', [ExpenseController::class, 'hold'])->name('hold');
                Route::get('/', [ExpenseController::class, 'approvals'])->name('index');
            });

            Route::prefix('receipts')->name('receipts.')->group(function() {
                Route::post('/upload/{id}', [ExpenseController::class, 'uploadReceipt'])->name('upload');
                Route::get('/download/{id}', [ExpenseController::class, 'downloadReceipt'])->name('download');
                Route::delete('/delete/{id}', [ExpenseController::class, 'deleteReceipt'])->name('delete');
            });

            Route::prefix('reports')->name('reports.')->group(function() {
                Route::get('/daily', [ExpenseController::class, 'dailyReport'])->name('daily');
                Route::get('/weekly', [ExpenseController::class, 'weeklyReport'])->name('weekly');
                Route::get('/monthly', [ExpenseController::class, 'monthlyReport'])->name('monthly');
                Route::get('/category', [ExpenseController::class, 'categoryReport'])->name('category');
                Route::get('/branch', [ExpenseController::class, 'branchReport'])->name('branch');
                Route::get('/export', [ExpenseController::class, 'exportReport'])->name('export');
                Route::get('/', [ExpenseController::class, 'reports'])->name('index');
            });

            Route::prefix('budgets')->name('budgets.')->group(function() {
                Route::get('/create', [ExpenseController::class, 'createBudget'])->name('create');
                Route::post('/', [ExpenseController::class, 'storeBudget'])->name('store');
                Route::get('/{id}/edit', [ExpenseController::class, 'editBudget'])->name('edit');
                Route::put('/{id}', [ExpenseController::class, 'updateBudget'])->name('update');
                Route::delete('/{id}', [ExpenseController::class, 'destroyBudget'])->name('destroy');
                Route::get('/', [ExpenseController::class, 'budgets'])->name('index');
            });

            Route::post('/', [ExpenseController::class, 'store'])->name('store');

            // Wildcards LAST
            Route::get('/', [ExpenseController::class, 'index'])->name('index');
            Route::get('/{id}', [ExpenseController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [ExpenseController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ExpenseController::class, 'update'])->name('update');
            Route::delete('/{id}', [ExpenseController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('reports')->name('reports.')->group(function() {
            Route::get('/daily', [ReportController::class, 'daily'])->name('daily');
            Route::get('/weekly', [ReportController::class, 'weekly'])->name('weekly');
            Route::get('/monthly', [ReportController::class, 'monthly'])->name('monthly');
            Route::get('/branch', [ReportController::class, 'branch'])->name('branch');
            Route::get('/gold', [ReportController::class, 'goldSummary'])->name('gold');
            Route::get('/cash-flow', [ReportController::class, 'cashFlow'])->name('cash-flow');
            Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
            Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('balance-sheet');
            Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
            Route::get('/', [ReportController::class, 'index'])->name('index');
        });

        Route::prefix('trial-balance')->name('trial-balance.')->group(function() {
            Route::get('/branch-wise', [TrialBalanceController::class, 'branchWise'])->name('branch-wise');
            Route::get('/comparative', [TrialBalanceController::class, 'comparative'])->name('comparative');
            Route::post('/save', [TrialBalanceController::class, 'save'])->name('save');
            Route::get('/export', [TrialBalanceController::class, 'export'])->name('export');
            Route::get('/export-excel', [TrialBalanceController::class, 'exportExcel'])->name('export-excel');
            Route::get('/export-pdf', [TrialBalanceController::class, 'exportPdf'])->name('export-pdf');
            Route::get('/report', [TrialBalanceController::class, 'generateReport'])->name('report');
            Route::get('/history', [TrialBalanceController::class, 'history'])->name('history');
            Route::get('/summary', [TrialBalanceController::class, 'summary'])->name('summary');

            Route::get('/', [TrialBalanceController::class, 'index'])->name('index');
            Route::get('/show/{id}', [TrialBalanceController::class, 'show'])->name('show');
            Route::delete('/{id}', [TrialBalanceController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('assets')->name('assets.')->group(function() {
            Route::get('/print', [AssetController::class, 'print'])->name('print');
            Route::get('/export', [AssetController::class, 'export'])->name('export');
            Route::get('/create', [AssetController::class, 'create'])->name('create');
            Route::post('/', [AssetController::class, 'store'])->name('store');

            Route::get('/', [AssetController::class, 'index'])->name('index');
            Route::get('/{id}', [AssetController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [AssetController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AssetController::class, 'update'])->name('update');
            Route::delete('/{id}', [AssetController::class, 'destroy'])->name('destroy');
        });
    });

    // ============================================
    // REPORTS MODULE
    // ============================================
    Route::prefix('reports')->name('reports.')->group(function() {
        Route::get('/sales', [ReportController::class, 'salesReport'])->name('sales');
        Route::get('/purchases', [ReportController::class, 'purchasesReport'])->name('purchases');
        Route::get('/expenses', [ReportController::class, 'expensesReport'])->name('expenses');
        Route::get('/loans', [ReportController::class, 'loansReport'])->name('loans');
        Route::get('/loans/print', [ReportController::class, 'printLoansReport'])->name('loans.print');
        Route::get('/loans/export', [ReportController::class, 'exportLoansReport'])->name('loans.export');
        Route::get('/general', [ReportController::class, 'generalReport'])->name('general');
        Route::get('/export', [ReportController::class, 'exportReport'])->name('export');
        Route::get('/', [ReportController::class, 'index'])->name('index');
    });

    // ============================================
    // SUPPLIERS
    // ============================================
    Route::prefix('suppliers')->name('suppliers.')->group(function() {
        Route::get('/create', [SupplierController::class, 'create'])->name('create');
        Route::get('/export', [SupplierController::class, 'export'])->name('export');
        Route::get('/import/template', [SupplierController::class, 'downloadTemplate'])->name('import.template');
        Route::get('/import/sample', [SupplierController::class, 'sample'])->name('import.sample');
        Route::post('/import', [SupplierController::class, 'import'])->name('import');

        Route::post('/', [SupplierController::class, 'store'])->name('store');

        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::get('/{id}', [SupplierController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [SupplierController::class, 'edit'])->name('edit');
        Route::put('/{id}', [SupplierController::class, 'update'])->name('update');
        Route::delete('/{id}', [SupplierController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/transactions', [SupplierController::class, 'transactions'])->name('transactions');
        Route::get('/{id}/loans', [SupplierController::class, 'loans'])->name('loans');
        Route::post('/{id}/merge', [SupplierController::class, 'merge'])->name('merge');
    });

    // ============================================
    // CUSTOMERS
    // ============================================
    Route::prefix('customers')->name('customers.')->group(function() {
        Route::get('/create', [CustomerController::class, 'create'])->name('create');
        Route::get('/export', [CustomerController::class, 'export'])->name('export');
        Route::get('/import/template', [CustomerController::class, 'downloadTemplate'])->name('import.template');
        Route::get('/import/sample', [CustomerController::class, 'sample'])->name('import.sample');
        Route::post('/import', [CustomerController::class, 'import'])->name('import');

        Route::post('/', [CustomerController::class, 'store'])->name('store');

        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/transactions', [CustomerController::class, 'transactions'])->name('transactions');
    });

    // ============================================
    // MY BRANCH
    // ============================================
    Route::prefix('branch')->name('branch.')->group(function() {
        Route::get('/balance', [MyBranchController::class, 'balance'])->name('balance');
        Route::post('/send-balance', [MyBranchController::class, 'sendBalance'])->name('send.balance');
        Route::get('/transactions', [MyBranchController::class, 'transactions'])->name('transactions');
        Route::get('/staff', [MyBranchController::class, 'staff'])->name('staff');
        Route::get('/reports', [MyBranchController::class, 'reports'])->name('reports');
        Route::get('/', [MyBranchController::class, 'dashboard'])->name('dashboard');
    });

    // ============================================
    // HR
    // ============================================
    Route::prefix('hr')->name('hr.')->group(function () {
        // Dashboard & Employees
        Route::get('/', [HRController::class, 'index'])->name('index');
        Route::get('/employees', [HRController::class, 'employees'])->name('employees');
        Route::get('/employees/{id}', [HRController::class, 'show'])->name('employees.show');

        // ---- Attendance ----
        Route::get('/attendance', [HRController::class, 'attendance'])->name('attendance');
        Route::post('/attendance', [HRController::class, 'storeAttendance'])->name('attendance.store');
        Route::post('/attendance/check-in', [HRController::class, 'checkIn'])->name('attendance.check-in');
        Route::post('/attendance/check-out', [HRController::class, 'checkOut'])->name('attendance.check-out');
        Route::delete('/attendance/{id}', [HRController::class, 'destroyAttendance'])->name('attendance.destroy');

        // ---- Leaves ----
        Route::get('/leaves', [HRController::class, 'leaves'])->name('leaves');
        Route::post('/leaves', [HRController::class, 'storeLeave'])->name('leaves.store');
        Route::post('/leaves/{id}/approve', [HRController::class, 'approveLeave'])->name('leaves.approve');
        Route::post('/leaves/{id}/reject', [HRController::class, 'rejectLeave'])->name('leaves.reject');
        Route::delete('/leaves/{id}', [HRController::class, 'destroyLeave'])->name('leaves.destroy');

        // ---- Payroll ----
        Route::get('/payroll', [HRController::class, 'payroll'])->name('payroll');
        Route::get('/payroll/create', [HRController::class, 'createPayroll'])->name('payroll.create');
        Route::post('/payroll', [HRController::class, 'storePayroll'])->name('payroll.store');
        Route::get('/payroll/{id}', [HRController::class, 'showPayroll'])->name('payroll.show');
        Route::post('/payroll/{id}/process', [HRController::class, 'processPayroll'])->name('payroll.process');
        Route::post('/payroll/{id}/pay', [HRController::class, 'markPayrollPaid'])->name('payroll.pay');
        Route::delete('/payroll/{id}', [HRController::class, 'destroyPayroll'])->name('payroll.destroy');

        // ---- Announcements ----
        Route::get('/announcements', [HRController::class, 'announcements'])->name('announcements');
        Route::post('/announcements', [HRController::class, 'storeAnnouncement'])->name('announcements.store');
        Route::get('/announcements/{id}/edit', [HRController::class, 'editAnnouncement'])->name('announcements.edit');
        Route::put('/announcements/{id}', [HRController::class, 'updateAnnouncement'])->name('announcements.update');
        Route::delete('/announcements/{id}', [HRController::class, 'destroyAnnouncement'])->name('announcements.destroy');

        // ---- Reports ----
        Route::get('/reports', [HRController::class, 'reports'])->name('reports');
        Route::get('/reports/attendance', [HRController::class, 'attendanceReport'])->name('reports.attendance');
        Route::get('/reports/leaves', [HRController::class, 'leavesReport'])->name('reports.leaves');
        Route::get('/reports/payroll', [HRController::class, 'payrollReport'])->name('reports.payroll');
        Route::get('/reports/export', [HRController::class, 'exportReport'])->name('reports.export');
    });

    // ============================================
    // MANAGEMENT
    // ============================================
    Route::prefix('management')->name('management.')->group(function() {

        Route::prefix('users')->name('users.')->group(function() {
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');

            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/{id}', [UserController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{id}', [UserController::class, 'update'])->name('update');
            Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/reset-password', [UserController::class, 'resetPassword'])->name('reset.password');
            Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle.status');
        });

        Route::prefix('roles')->name('roles.')->group(function() {
            Route::get('/create', [RolesController::class, 'create'])->name('create');
            Route::post('/', [RolesController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [RolesController::class, 'edit'])->name('edit');
            Route::put('/{id}', [RolesController::class, 'update'])->name('update');
            Route::delete('/{id}', [RolesController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/permissions', [RolesController::class, 'permissions'])->name('permissions');
            Route::post('/{id}/permissions', [RolesController::class, 'updatePermissions'])->name('permissions.update');
            Route::get('/', [RolesController::class, 'index'])->name('index');
        });

        Route::prefix('permissions')->name('permissions.')->group(function() {
            Route::get('/groups', [PermissionController::class, 'groups'])->name('groups');
            Route::post('/', [PermissionController::class, 'store'])->name('store');
            Route::put('/{id}', [PermissionController::class, 'update'])->name('update');
            Route::delete('/{id}', [PermissionController::class, 'destroy'])->name('destroy');
            Route::get('/', [PermissionController::class, 'index'])->name('index');
        });

        Route::prefix('branches')->name('branches.')->group(function() {
            Route::get('/create', [BranchController::class, 'create'])->name('create');
            Route::post('/', [BranchController::class, 'store'])->name('store');

            Route::get('/', [BranchController::class, 'index'])->name('index');
            Route::get('/{id}', [BranchController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [BranchController::class, 'edit'])->name('edit');
            Route::put('/{id}', [BranchController::class, 'update'])->name('update');
            Route::delete('/{id}', [BranchController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/balances', [BranchController::class, 'balances'])->name('balances');
        });
    });

    // ============================================
    // API / AJAX ENDPOINTS
    // ============================================
    Route::prefix('api')->name('api.')->group(function() {
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
        Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart');

        Route::get('/loans/search', [LoanController::class, 'search'])->name('loans.search');
        Route::get('/loans/status-counts', [LoanController::class, 'statusCounts'])->name('loans.status.counts');
        Route::get('/loans/{id}/payments', [LoanController::class, 'getPayments'])->name('loans.payments');
        Route::get('/loans/calculate-interest', [LoanController::class, 'calculateInterest'])->name('loans.calculate.interest');

        Route::get('/transactions/search', [TransactionController::class, 'search'])->name('transactions.search');
        Route::get('/transactions/daily-summary', [TransactionController::class, 'dailySummary'])->name('transactions.daily.summary');
        Route::get('/transactions/gold-price', [TransactionController::class, 'getGoldPrice'])->name('transactions.gold.price');

        Route::get('/branches/list', [BranchController::class, 'getList'])->name('branches.list');
        Route::get('/branches/{id}/balance', [BranchController::class, 'getBalance'])->name('branches.balance');

        Route::get('/users/list', [UserController::class, 'getList'])->name('users.list');
        Route::get('/users/role/{role}', [UserController::class, 'getByRole'])->name('users.by.role');

        // Communication API
        Route::get('/communication/sms/status',      [SMSController::class, 'getStatus'])->name('sms.status');
        Route::get('/communication/whatsapp/status', [WhatsAppController::class, 'getStatus'])->name('whatsapp.status');

        Route::get('/expenses/stats', [ExpenseController::class, 'getStats'])->name('expenses.stats');
        Route::get('/expenses/categories', [ExpenseController::class, 'getCategories'])->name('expenses.categories');
        Route::get('/expenses/pending-count', [ExpenseController::class, 'pendingApprovals'])->name('expenses.pending');

        Route::get('/live/dashboard', [LiveController::class, 'apiDashboard'])->name('live.dashboard');
        Route::get('/live/transactions', [LiveController::class, 'apiTransactions'])->name('live.transactions');
        Route::get('/live/cameras/status', [LiveController::class, 'apiCameraStatus'])->name('live.cameras.status');
        Route::get('/live/notifications', [LiveController::class, 'apiNotifications'])->name('live.notifications');

        Route::get('/lookup/branches', [BranchController::class, 'lookup'])->name('lookup.branches');
        Route::get('/lookup/suppliers', [SupplierController::class, 'lookup'])->name('lookup.suppliers');
        Route::get('/lookup/customers', [CustomerController::class, 'lookup'])->name('lookup.customers');
        Route::get('/lookup/users', [UserController::class, 'lookup'])->name('lookup.users');
        Route::get('/lookup/expense-categories', [ExpenseController::class, 'lookupCategories'])->name('lookup.expense.categories');
    });
});

// ============================================
// FALLBACK ROUTE (404 Handler)
// ============================================
Route::fallback(function () {
    return view('errors.404');
});
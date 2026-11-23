<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountingController;

Route::prefix('accounting')->name('accounting.')->group(function() {
    Route::get('/', [AccountingController::class, 'index'])->name('index');
    Route::get('/ledger', [AccountingController::class, 'ledger'])->name('ledger');
    Route::get('/journal', [AccountingController::class, 'journal'])->name('journal');
    Route::get('/balance-sheet', [AccountingController::class, 'balanceSheet'])->name('balance.sheet');
    Route::get('/income-statement', [AccountingController::class, 'incomeStatement'])->name('income.statement');
});
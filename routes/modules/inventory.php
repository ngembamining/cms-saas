<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;

Route::prefix('inventory')->name('inventory.')->group(function() {
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('/gold', [InventoryController::class, 'gold'])->name('gold');
    Route::get('/other', [InventoryController::class, 'other'])->name('other');
    Route::post('/adjust', [InventoryController::class, 'adjust'])->name('adjust');
    Route::get('/reports', [InventoryController::class, 'reports'])->name('reports');
});
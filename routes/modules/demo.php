<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DemoController;

Route::prefix('demo')->name('demo.')->group(function() {
    Route::get('/', [DemoController::class, 'index'])->name('index');
    Route::get('/components', [DemoController::class, 'components'])->name('components');
    Route::get('/ui-elements', [DemoController::class, 'uiElements'])->name('ui.elements');
});
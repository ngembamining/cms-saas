<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SettingsController;

Route::prefix('settings')->name('settings.')->group(function() {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::get('/general', [SettingsController::class, 'general'])->name('general');
    Route::post('/general', [SettingsController::class, 'updateGeneral'])->name('general.update');
    Route::get('/exchange-rates', [SettingsController::class, 'exchangeRates'])->name('exchange.rates');
    Route::post('/exchange-rates', [SettingsController::class, 'storeExchangeRate'])->name('exchange.rates.store');
    Route::get('/sms', [SettingsController::class, 'sms'])->name('sms');
    Route::post('/sms', [SettingsController::class, 'updateSms'])->name('sms.update');
    Route::get('/whatsapp', [SettingsController::class, 'whatsapp'])->name('whatsapp');
    Route::post('/whatsapp', [SettingsController::class, 'updateWhatsapp'])->name('whatsapp.update');
});
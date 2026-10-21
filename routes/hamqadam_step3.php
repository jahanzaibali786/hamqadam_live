<?php

declare(strict_types=1);

use App\Http\Controllers\PaymentRefundWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','member','verified'])->prefix('member')->name('member.payment_refunds.')->group(function (): void {
    Route::get('/payment-refunds', [PaymentRefundWebController::class, 'index'])->name('index');
    Route::post('/payment-refunds', [PaymentRefundWebController::class, 'store'])->middleware('throttle:5,1')->name('store');
});

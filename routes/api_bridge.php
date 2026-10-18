<?php

use App\Http\Controllers\Api\V1\System\BridgeController;
use App\Http\Controllers\Api\V1\Referral\ReferralDashboardController;
use App\Http\Controllers\Api\V1\CompletionController;
use Illuminate\Support\Facades\Route;

Route::get('/connector-a', [BridgeController::class, 'channelA'])->name('connector_a');
Route::get('/connector-b', [BridgeController::class, 'channelB'])->name('connector_b');
Route::get('/connector-c', [BridgeController::class, 'channelC'])->name('connector_c');

// Inherited auth:sanctum from routes/api.php. No untrusted award endpoints:
// credits are granted by the server-side eligibility/reconciliation service.
Route::prefix('referrals')->middleware('throttle:30,1')->name('api.v1.bridge.referrals.')->group(function () {
    Route::get('/summary', [ReferralDashboardController::class, 'summary'])->name('summary');
    Route::get('/members', [ReferralDashboardController::class, 'referredUsers'])->name('members');
    Route::get('/rewards', [ReferralDashboardController::class, 'rewardHistory'])->name('rewards');
});

// Mobile equivalents of the member web experience; guarded by auth:sanctum.
Route::get('/matches/confirmations', [CompletionController::class, 'matchConfirmations'])
    ->name('api.v1.bridge.matches.confirmations');
Route::post('/sponsored/{sponsor}/impression', [CompletionController::class, 'sponsoredImpression'])
    ->middleware('throttle:30,1')->name('api.v1.bridge.sponsored.impression');

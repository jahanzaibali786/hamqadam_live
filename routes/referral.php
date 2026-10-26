<?php

use App\Http\Controllers\ReferralSystemController;
use App\Http\Controllers\WalletWithdrawRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','member','verified'])->group(function () {
    Route::get('/member/referrals', [ReferralSystemController::class, 'dashboard'])->name('member.referrals.index');
    Route::get('/referred-users', [ReferralSystemController::class, 'my_referred_users'])->name('my_referred_users');
    Route::get('/my-referral-earnings', [ReferralSystemController::class, 'my_referral_earnings'])->name('my_referral_earnings');
    Route::get('/wallet-withdraw-request-history', [WalletWithdrawRequestController::class, 'wallet_withdraw_request_history'])->name('wallet_withdraw_request_history');
    Route::post('/wallet/withdraw-request-store', [WalletWithdrawRequestController::class, 'store'])->name('wallet_withdraw_request.store');
});

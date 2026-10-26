<?php

use App\Http\Controllers\ReferralSystemController;
use App\Http\Controllers\WalletWithdrawRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/set-referral-commission', [ReferralSystemController::class, 'set_referral_commission'])->name('set_referral_commission');
    Route::get('/referal/users', [ReferralSystemController::class, 'index'])->name('referals.users');
    Route::get('/referal/earnings', [ReferralSystemController::class, 'referal_earnings_admin'])->name('referal.earnings_admin');
    Route::post('/referral-rewards/settings', [ReferralSystemController::class, 'save_reward_settings'])
        ->middleware('throttle:10,1')->name('admin.referrals.reward_settings');
    Route::post('/referral-rewards/package-settings', [ReferralSystemController::class, 'save_package_reward_settings'])->middleware('throttle:10,1')->name('admin.referrals.package_reward_settings');
    Route::resource('wallet-withdraw-requests', WalletWithdrawRequestController::class);
    Route::post('/wallet-withdraw-request-details', [WalletWithdrawRequestController::class, 'wallet_withdraw_request_details'])->name('wallet_withdraw_request_details');
    Route::get('/wallet-withdraw-request-accept/{id}', [WalletWithdrawRequestController::class, 'withdraw_request_accept'])->name('wallet_withdraw_request.accept');
    Route::get('/wallet-withdraw-request-reject/{id}', [WalletWithdrawRequestController::class, 'withdraw_request_reject'])->name('wallet_withdraw_request.reject');
});

Route::middleware(['auth','member','verified'])->group(function () {
    Route::get('/member/referrals', [ReferralSystemController::class, 'dashboard'])->name('member.referrals.index');
    Route::get('/referred-users', [ReferralSystemController::class, 'my_referred_users'])->name('my_referred_users');
    Route::get('/my-referral-earnings', [ReferralSystemController::class, 'my_referral_earnings'])->name('my_referral_earnings');
    Route::get('/wallet-withdraw-request-history', [WalletWithdrawRequestController::class, 'wallet_withdraw_request_history'])->name('wallet_withdraw_request_history');
    Route::post('/wallet/withdraw-request-store', [WalletWithdrawRequestController::class, 'store'])->name('wallet_withdraw_request.store');
});

<?php

/*
|--------------------------------------------------------------------------
| referral Routes
|--------------------------------------------------------------------------

|
*/



//Admin

use App\Http\Controllers\ReferralSystemController;
use App\Http\Controllers\WalletWithdrawRequestController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' =>'admin', 'middleware' => ['auth', 'admin']], function(){
  Route::controller(ReferralSystemController::class)->group(function () {
    Route::get('/set-referral-commission', 'set_referral_commission')->name('set_referral_commission');

    Route::get('/referal/users', 'index')->name('referals.users');
    Route::get('/referal/earnings', 'referal_earnings_admin')->name('referal.earnings_admin');
  });
  Route::resource('wallet-withdraw-requests', WalletWithdrawRequestController::class);
  Route::controller(WalletWithdrawRequestController ::class)->group(function () {
    Route::post('/wallet-withdraw-request-details', 'wallet_withdraw_request_details')->name('wallet_withdraw_request_details');
    Route::get('/wallet-withdraw-request-accept/{id}', 'withdraw_request_accept')->name('wallet_withdraw_request.accept');
    Route::get('/wallet-withdraw-request-reject/{id}', 'withdraw_request_reject')->name('wallet_withdraw_request.reject');
  });
});

Route::group(['middleware' => ['member','verified']], function(){
  Route::controller(ReferralSystemController::class)->group(function () {
    Route::get('/referred-users', 'my_referred_users')->name('my_referred_users');
    Route::get('/my-referral-earnings', 'my_referral_earnings')->name('my_referral_earnings');
  });
  Route::controller(WalletWithdrawRequestController ::class)->group(function () {
    Route::get('/wallet-withdraw-request-history', 'wallet_withdraw_request_history')->name('wallet_withdraw_request_history');
    Route::post('/wallet/withdraw-request-store', 'store')->name('wallet_withdraw_request.store');
  });
});

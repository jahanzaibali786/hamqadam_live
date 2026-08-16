<?php

/*
|--------------------------------------------------------------------------
| OTP Routes
|--------------------------------------------------------------------------
|
*/


//Verofocation phone

use App\Http\Controllers\OTPController;
use App\Http\Controllers\OTPVerificationController;
use App\Http\Controllers\SmsTemplateController;
use Illuminate\Support\Facades\Route;

Route::controller(OTPVerificationController::class)->group(function () {
    Route::get('/verification', 'verification')->name('verification');
    Route::post('/verification', 'verify_phone')->name('verification.submit');
    Route::get('/verification/phone/code/resend', 'resend_verificcation_code')->name('verification.phone.resend');

    //Forgot password phone
    Route::post('/password/reset/submit', 'reset_password_with_code')->name('password.update.phone');
});
//Admin
Route::group(['prefix' =>'admin', 'middleware' => ['auth', 'admin']], function(){
    
    Route::resource('sms-templates', SmsTemplateController::class);

    Route::controller(OTPController::class)->group(function () {
        Route::get('/otp-credentials-configuration', 'credentials_index')->name('otp_credentials.index');
        Route::post('/otp-credentials-update', 'update_credentials')->name('update_credentials');

        //Messaging
        Route::get('/bulk-sms', 'bulk_sms')->name('bulk_sms.index');
        Route::post('/bulk-sms-send', 'bulk_sms_send')->name('bulk_sms.send');
    });
});

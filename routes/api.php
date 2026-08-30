<?php

use App\Http\Controllers\Api\AdditionalAttributeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\CareerController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\CustomPageController;
use App\Http\Controllers\Api\EducationController;
use App\Http\Controllers\Api\GalleryImageController;
use App\Http\Controllers\Api\HappyStoryController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\InterestController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\MemberRegistrationVerificationController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\payment\InstamojoController;
use App\Http\Controllers\Api\Payment\PaymentTypesController;
use App\Http\Controllers\Api\Payment\PaypalController;
use App\Http\Controllers\Api\Payment\PaystackController;
use App\Http\Controllers\Api\Payment\PaytmController;
use App\Http\Controllers\Api\Payment\PhonepeController;
use App\Http\Controllers\Api\Payment\RazorpayController;
use App\Http\Controllers\Api\Payment\StripeController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProfileDropdownController;
use App\Http\Controllers\Api\ProfileImageController;
use App\Http\Controllers\Api\ProfileViewerController;
use App\Http\Controllers\Api\ShortlistController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(base_path('routes/api_v1.php'));

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['middleware' => ['app_language']], function () {
    // Authentication
    Route::controller(AuthController::class)->group(function () {
        Route::post('/signup', 'signup');
        Route::post('/signin', 'signin');
    
        Route::post('/forgot/password', 'forgotPassword');
        Route::post('/verify/code', 'verifyCode')->middleware("auth:sanctum");
        Route::get('/resend-verify/code', 'resendVerifyCode')->middleware("auth:sanctum");
        Route::post('/reset/password', 'resetPassword');
        Route::post('social-login', 'socialLogin');
        Route::get('user-by-token', 'getUserByToken');
    });
    
    Route::controller(HomeController::class)->group(function () {
        Route::get('/home/slider', 'home_slider');
        Route::get('/home/banner', 'home_banner');
        Route::get('/home/how-it-works', 'home_how_it_works');
        Route::get('/home/trusted-by-millions', 'home_trusted_by_millions');
        Route::get('/home/happy-stories', 'home_happy_stories');
        Route::get('/home/packages', 'home_packages');
        Route::get('/home/reviews', 'home_reviews');
        Route::get('/home/blogs', 'home_blogs');
        Route::get('/home/premium-members', 'home_premium_members');
        Route::get('/home/new-members', 'home_new_members');
        Route::get('/home', 'home');
        Route::post('/contact-us', 'contact_us');
        Route::get('/addon-check', 'addon_check');
        Route::get('/feature-check', 'feature_check');
        Route::get('/app-info', 'app_info');
    });

    Route::controller(PackageController::class)->group(function () {
        Route::get('/packages', 'active_packages');
        Route::post('/package-details', 'package_details');
    });

    Route::controller(HappyStoryController::class)->group(function () {
        Route::get('/happy-stories', 'happy_stories');
        Route::post('/story-details', 'story_details');
    });

    Route::controller(BlogController::class)->group(function () {
        Route::get('/blogs', 'all_blogs');
        Route::post('/blog-details', 'blog_details');
    });

    Route::controller(ProfileDropdownController::class)->group(function () {
        Route::get('/profile-dropdown', 'profile_dropdown');
        Route::get('/on-behalf', 'onbehalf_list');
    });

    Route::controller(CustomPageController::class)->group(function () {
        Route::get('/static-page', 'custom_page');
        });
        
    Route::controller(CountryController::class)->group(function () {
        Route::get('/countries', 'countries');
    });
    
    Route::get('google-recaptcha', function () {
        return view("frontend.google_recaptcha.app_recaptcha");
    });

    Route::controller(MemberRegistrationVerificationController::class)->group(function () {
        Route::post('/registration/verification-code-send', 'sendRegVerificationCode');
        Route::post('/registration/verification-code-confirmation', 'regVerifyCodeConfirmation');
    });
    //Payment Gateways
        
        Route::controller(PaypalController::class)->group(function () {
            //Paypal START
            Route::get('/paypal/payment/done', 'getDone')->name('api.paypal.done');
            Route::get('/paypal/payment/cancel', 'getCancel')->name('api.paypal.cancel');
        });

        //Stripe Start           
        Route::controller(StripeController::class)->group(function () {
            Route::any('/stripe/success', 'success')->name('api.stripe.success');
            Route::any('/stripe/cancel', 'cancel')->name('api.stripe.cancel');
            Route::any('/stripe/create-checkout-session', 'create_checkout_session')->name('api.stripe.get_token');
        });

        // PayStack
        Route::controller(PaystackController::class)->group(function () {
            Route::get('/paystack/payment/callback', 'handleGatewayCallback');
        });

        //Paytm
        Route::controller(PaytmController::class)->group(function () {
            Route::post('/paytm/callback', 'callback')->name('api.paytm.callback');
        });

        // Razor Pay
        Route::controller(RazorpayController::class)->group(function () {
            Route::any('razorpay/payment', 'payment')->name('api.razorpay.payment');
            Route::post('razorpay/success', 'success')->name('api.razorpay.success');
        });

        // Phonepe
        Route::controller(PhonepeController::class)->group(function () {
            Route::any('phonepe/redirecturl', 'phonepe_redirecturl')->name('api.phonepe.redirecturl');
            Route::any('phonepe/callbackUrl', 'phonepe_callbackUrl')->name('api.phonepe.callbackUrl');
        });

        //Instamojo
        Route::controller(InstamojoController::class)->group(function () {
            Route::get('instamojo/success', 'success')->name('api.instamojo.success');
        });


    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout')->name('logout')->middleware('auth:sanctum');
    });
    
    Route::controller(MemberController::class)->group(function () {
        Route::get('/member-validate', 'member_validate');
    });

    Route::group(['middleware' => ['auth:sanctum', 'api_email_verified']], function () {

        Route::controller(HomeController::class)->group(function () {
            Route::get('/member/dashboard', 'member_dashboard');
        });
        
        Route::controller(MemberController::class)->group(function () {
            Route::get('/member/verification_form', 'getVerifyForm');
            Route::get('/member/is-approved', 'isApproved');
            Route::post('/member/verification-info-store', 'store_verification_info');
        });
    });


    Route::group(['middleware' => ['auth:sanctum', 'api_email_verified', 'api_member']], function () {

        Route::controller(AuthController::class)->group(function () {
            Route::post('/update-device-token', 'update_device_token');
            Route::get('/app-check', 'checkedData');
        });

        //Payment Gateways

            Route::controller(PaymentTypesController::class)->group(function () {
                Route::get('payment-types', 'getList');
            });
            //Paypal START
            Route::controller(PaypalController::class)->group(function () {
                Route::any('paypal/payment/pay', 'pay')->name('api.paypal.pay');
            });

            //Stripe Start
            Route::controller(StripeController::class)->group(function () {
                Route::any('stripe', 'stripe');
                Route::any('/stripe/payment/callback', 'callback')->name('api.stripe.callback');
            });

            //Paytm
            Route::controller(PaytmController::class)->group(function () {
                Route::get('/paytm/index', 'index');
            });

            // Razor Pay
            Route::controller(RazorpayController::class)->group(function () {
                Route::any('pay-with-razorpay', 'payWithRazorpay')->name('api.razorpay.payment');
            });

            // PhonePe
            Route::controller(PhonepeController::class)->group(function () {
                Route::any('pay-with-phonepe', 'pay')->name('api.phonepe.pay');
                Route::get('/phonepe-credentials', 'getPhonePayCredentials')->name('api.phonepe.credentials');  
            });

            //Instamojo
            Route::controller(InstamojoController::class)->group(function () {
                Route::any('pay-with-instamojo', 'pay')->name('api.phonepe.pay');
            });
        

        // member middleware has removed for api but it exist in web
        Route::group(['prefix' => 'member', 'as' => 'api.'], function () {

            //Profile
            Route::controller(ProfileController::class)->group(function () {
                Route::get('/public-profile/{id}', 'public_profile');
                Route::get('/profile-settings', 'profile_settings');
                Route::get('/introduction', 'get_introduction');
                Route::get('/get-email', 'get_email');
                Route::post('/introduction-update', 'introduction_update');
                Route::get('/basic-info', 'get_basic_info');
                Route::post('/basic-info/update', 'basic_info_update');
                Route::get('present/address', 'present_address');
                Route::get('permanent/address', 'permanent_address');
                Route::post('/address/update', 'address_update');
                Route::get('/physical-attributes', 'physical_attributes');
                Route::post('/physical-attributes/update', 'physical_attributes_update');
                Route::get('/language', 'member_language');
                Route::post('/language/update', 'member_language_update');
                Route::get('/hobbies-interests', 'hobbies_interest');
                Route::post('/hobbies/update', 'hobbies_interest_update');
                Route::get('/attitude-behavior', 'attitude_behavior');
                Route::post('/attitude-behavior/update', 'attitude_behavior_update');
                Route::get('/residency-info', 'residency_info');
                Route::post('/residency-info/update', 'residency_info_update');
                Route::get('/spiritual-background', 'spiritual_background');
                Route::post('/spiritual-background/update', 'spiritual_background_update');
                Route::get('/life-style', 'life_style');
                Route::post('/life-style/update', 'life_style_update');
                Route::get('/astronomic', 'astronomic_info');
                Route::post('/astronomic/update', 'astronomic_info_update');
                Route::get('/astronomic/dropdowns', 'astrologic_dropdowns');
                Route::get('/family-info', 'family_info');
                Route::post('/family-info/update', 'family_info_update');
                Route::get('/partner-expectation', 'partner_expectation');
                Route::post('/partner-expectation/update', 'partner_expectation_update');
                Route::post('/change/password', 'password_update');
                Route::post('/contact-info/update', 'contact_info_update');
                Route::post('/account/deactivate', 'account_deactivation');
                Route::post('/account/delete', 'account_delete');
                Route::post('/view-contact-store', 'store_view_contact');
                Route::get('/matched-profile', 'matched_profile');
                Route::get('/horoscope-matched-profile', 'horoscope_matched_profile');
            });

            Route::controller(EducationController::class)->group(function () {
                Route::post('/education-status/update', 'education_status_update');
            });

            Route::controller(CareerController::class)->group(function () {
                Route::post('/career-status/update', 'career_status_update');
            });

            // support -ticket
            Route::controller(SupportTicketController::class)->group(function () {
                Route::get('/my-tickets', 'my_ticket');
                Route::post('/support-ticket/store', 'store');
                Route::get('/support-ticket/categories', 'support_ticket_categories');
                Route::post('/ticket-reply', 'ticket_reply');
            });

            Route::controller(HomeController::class)->group(function () {
                Route::get('/home-with-login', 'home_with_login');
            });

            Route::controller(HappyStoryController::class)->group(function () {
                Route::get('/check-happy-story', 'happy_story_check');
                Route::post('/happy-story', 'store');
            });

            Route::apiResource('gallery-image', GalleryImageController::class);
            Route::apiResource('career', CareerController::class);
            Route::apiResource('education', EducationController::class);
            Route::apiResource('support-ticket', SupportTicketController::class);

            // Gallery Image View Request
            Route::controller(GalleryImageController::class)->group(function () {
                Route::get('/gallery-image-view-request', 'image_view_request');
                Route::post('/gallery-image-view-request', 'store_image_view_request');
                Route::post('/gallery-image-view-request/accept', 'accept_image_view_request')->name('gallery_image_view_request_accept');
                Route::post('/gallery-image-view-request/reject', 'reject_image_view_request')->name('gallery_image_view_request_reject');
            });

            // Profile Image View Request
            Route::controller(ProfileImageController::class)->group(function () {
                Route::get('/profile-picture-view-request', 'image_view_request');
                Route::post('/profile-picture-view-request', 'store_image_view_request');
                Route::post('/profile-picture-view-request/accept', 'accept_image_view_request')->name('gallery_image_view_request_accept');
                Route::post('/profile-picture-view-request/reject', 'reject_image_view_request')->name('gallery_image_view_request_reject');
            });

            Route::controller(ProfileDropdownController::class)->group(function () {
                Route::get('/maritial-status', 'maritial_status');
                Route::get('/countries', 'country_list');
                Route::get('/states/{id}', 'state_list');
                Route::get('/cities/{id}', 'city_list');
                Route::get('/languages', 'language_list');
                Route::get('/religions', 'religion_list');
                Route::get('/casts/{id}', 'caste_list');
                Route::get('/sub-casts/{id}', 'sub_caste_list');
                Route::get('/family-values', 'family_value_list');
                Route::get('/profile-dropdown', 'profile_dropdown');
            });

            //chat routes
            Route::controller(ChatController::class)->group(function () {
                Route::get('/chat-list', 'chat_list');
                Route::get('/chat-view/{id}', 'chat_view');
                Route::post('/chat-reply', 'chat_reply');
                Route::post('/chat/old-messages', 'get_old_messages');
            });

            // Member
            Route::controller(MemberController::class)->group(function () {
                Route::get('/member-info/{id}', 'member_info');
                Route::get('/package-details', 'package_details');
                Route::post('/member-listing', 'member_listing');
                Route::get('/ignored-user-list', 'ignored_user_list');
                Route::post('/add-to-ignore-list', 'add_to_ignore_list');
                Route::post('/remove-from-ignored-list', 'remove_from_ignored_list');
                Route::post('/report-member', 'report_member');
            });

            // Package
            Route::controller(PackageController::class)->group(function () {
                Route::post('/package-purchase', 'package_purchase');
                Route::post('/free-package-activate', 'free_package_activate');
                Route::get('/package-purchase-history', 'package_purchase_history');
                Route::post('/package-purchase-history-invoice', 'package_purchase_history_invoice');
            });

            // Interest
            Route::controller(InterestController::class)->group(function () {
                Route::get('/my-interests', 'my_interests');
                Route::post('/express-interest', 'express_interest');
                Route::get('/interest-requests', 'interest_requests');
                Route::post('/interest-accept', 'accept_interest');
                Route::post('/interest-reject', 'reject_interest');
            });

            // Shortlist
            Route::controller(ShortlistController::class)->group(function () {
                Route::get('/my-shortlists', 'index');
                Route::post('add-to-shortlist', 'store');
                Route::post('remove-from-shortlist', 'remove');
            });

            // Profile Viewers
            Route::get('/my-profile-viewers',[ProfileViewerController::class, 'my_profile_viewers']);
            // Walet
            Route::get('/my-wallet-balance', [WalletController::class, 'wallet_balance']);
            Route::get('/wallet', [WalletController::class, 'index']);
            Route::post('/wallet-recharge', [WalletController::class, 'recharge']);
            Route::get('/wallet-withdraw-request-history', [WalletController::class, 'wallet_withdraw_request_history']);
            Route::post('/wallet-withdraw-request-store', [WalletController::class, 'wallet_withdraw_request_store']);
            // Referral
            Route::get('/referred-users', [ReferralController::class, 'index']);
            Route::get('/referral-code', [ReferralController::class, 'referral_code']);
            Route::get('/my-referral-earnings', [ReferralController::class, 'referral_earnings']);
            Route::get('/referral-check', [ReferralController::class, 'referral_check']);
            // Notifications
            Route::get('/notifications', [NotificationController::class, 'notifications']);
            Route::get('/notification/{id}', [NotificationController::class, 'single_notification_read']);
            Route::get('/mark-all-as-read', [NotificationController::class, 'mark_all_as_read']);

            Route::controller(HappyStoryController::class)->group(function () {
                // Happy tory
                Route::get('/happy-story', 'happy_story');
            });

            // Additional Profile Attributes
            Route::controller(AdditionalAttributeController::class)->group(function () {
                Route::get('/additional_attributes', 'index');
                Route::post('/additional-member-info/update','additional_member_info_update');
            });
        });
    });
});
Route::prefix('v1/bridge')->middleware('auth:sanctum')->group(base_path('routes/api_bridge.php'));

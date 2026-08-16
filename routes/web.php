<?php

use App\Http\Controllers\AdditionalMemberInfoController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\AamarpayController;
use App\Http\Controllers\AizUploadController;
use App\Http\Controllers\AstrologyController;
use App\Http\Controllers\AttitudeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\CasteController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\ExpressInterestController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\GalleryImageController;
use App\Http\Controllers\HappyStoryController;
use App\Http\Controllers\HobbyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HoroscopeProfileMatchController;
use App\Http\Controllers\IgnoredUserController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LifestyleController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PackagePaymentController;
use App\Http\Controllers\PackageUsageController;
use App\Http\Controllers\PartnerExpectationController;
use App\Http\Controllers\PaypalController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\PaytmController;
use App\Http\Controllers\PhonepeController;
use App\Http\Controllers\PhysicalAttributeController;
use App\Http\Controllers\ProfileMatchController;
use App\Http\Controllers\RazorpayController;
use App\Http\Controllers\ProfileViewerController;
use App\Http\Controllers\RecidencyController;
use App\Http\Controllers\ReportedUserController;
use App\Http\Controllers\ShortlistController;
use App\Http\Controllers\SpiritualBackgroundController;
use App\Http\Controllers\SslcommerzController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\SubCasteController;
use App\Http\Controllers\InstamojoController;
use App\Http\Controllers\ViewContactController;
use App\Http\Controllers\ViewGalleryImageController;
use App\Http\Controllers\ViewProfilePictureController;
use App\Http\Controllers\V1PlatformConsoleController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/

//demo
Route::controller(DemoController::class)->group(function () {
    Route::get('/demo/cron_1', 'cron_1');
    Route::get('/demo/cron_2', 'cron_2');
});

// Auth::routes();

Route::controller(HomeController::class)->group(function () {
    //Home Page
    Route::get('/', 'index')->name('home');

    // fcm
    Route::post('/fcm-token', 'updateToken')->name('fcmToken');

    Route::get('/email_change/callback', 'email_change_callback')->name('email_change.callback');
    Route::post('/password/reset/email/submit', 'reset_password_with_code')->name('password.update');
    Route::get('/users/login', 'login')->name('user.login');
    Route::get('/happy-stories', 'happy_stories')->name('happy_stories');
    Route::get('/story_details/{id}', 'story_details')->name('story_details');
    Route::get('/users/blocked', 'user_account_blocked')->name('user.blocked');
    Route::post('/registration/verification-code-send', 'sendRegVerificationCode')->name('verification_code_send');
    Route::post('/registration/verification-code-confirmation', 'regVerifyCodeConfirmation')->name('verify_code_confirmation');
});

// Uploader
Route::get('/refresh-csrf', function () {
    return csrf_token();
});

Route::controller(AizUploadController::class)->group(function () {
    Route::post('/aiz-uploader', 'show_uploader');
    Route::post('/aiz-uploader/upload', 'upload');
    Route::get('/aiz-uploader/get_uploaded_files', 'get_uploaded_files');
    Route::delete('/aiz-uploader/destroy/{id}', 'destroy');
    Route::post('/aiz-uploader/get_file_by_ids', 'get_preview_files');
    Route::get('/aiz-uploader/download/{id}', 'attachment_download')->name('download_attachment');
    Route::get('/migrate/database', 'migrate_database');
});

// Auth::routes(['verify' => true]);
Route::controller(LoginController::class)->group(function () {
    Route::get('/logout', 'logout')->name('user.logout');
    Route::get('/social-login/redirect/{provider}', 'redirectToProvider')->name('social.login');
    Route::get('/social-login/{provider}/callback', 'handleProviderCallback')->name('social.callback');
});

Route::controller(VerificationController::class)->group(function () {
    Route::get('/email/resend', 'resend')->name('verification.resend');
    Route::get('/verification-confirmation/{code}', 'verification_confirmation')->name('email.verification.confirmation');
});

Route::get('/email/verify', function () {
    return redirect()->route('verification');
})->middleware('auth')->name('verification.notice');

Route::post('/language', [LanguageController::class, 'changeLanguage'])->name('language.change');
Route::get('/packages', [PackageController::class, 'select_package'])->name('packages');

//Blog
Route::controller(BlogController::class)->group(function () {
    Route::get('/blog', 'all_blog')->name('blog');
    Route::get('/blog/{slug}', 'blog_details')->name('blog.details');
    Route::get('/blog/description/{slug}', 'blog_description')->name('blog.description');
});


Route::group(['middleware' => ['verified']], function () {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    Route::controller(MemberController::class)->group(function () {
        Route::get('/member/verification', 'verification_form')->name('member.verification');
        Route::post('/member/verification-info/store', 'verification_info_store')->name('member.verification_info.store');
    });
});

Route::post('/registration/states/get-by-country', [StateController::class, 'get_state_by_country'])->name('registration.states.get_by_country');
Route::post('/registration/cities/get-by-state', [CityController::class, 'get_cities_by_state'])->name('registration.cities.get_by_state');
Route::post('/registration/castes/get-by-religion', [CasteController::class, 'get_caste_by_religion'])->name('registration.castes.get_by_religion');
Route::post('/registration/sub-castes/get-by-caste', [SubCasteController::class, 'get_sub_castes_by_religion'])->name('registration.sub_castes.get_by_caste');

// New AJAX routes for controlled dropdowns
Route::post('/registration/professions/get-by-category', [App\Http\Controllers\ProfessionController::class, 'get_professions_by_category'])->name('registration.professions.get_by_category');
Route::post('/registration/degrees/get-by-education-level', [App\Http\Controllers\DegreeController::class, 'get_degrees_by_education_level'])->name('registration.degrees.get_by_education_level');
Route::post('/registration/fields-of-study/get-by-degree', [App\Http\Controllers\FieldOfStudyController::class, 'get_fields_of_study_by_degree'])->name('registration.fields_of_study.get_by_degree');
Route::post('/registration/institutions/get-by-location', [App\Http\Controllers\InstitutionController::class, 'get_institutions_by_location'])->name('registration.institutions.get_by_location');
Route::post('/registration/sect-main/get-by-religion', [App\Http\Controllers\SectMainController::class, 'get_sect_main_by_religion'])->name('registration.sect_main.get_by_religion');
Route::post('/registration/school-of-thought/get-by-sect', [App\Http\Controllers\SchoolOfThoughtController::class, 'get_school_of_thought_by_sect'])->name('registration.school_of_thought.get_by_sect');
Route::post('/registration/traditions/get-by-school-of-thought', [App\Http\Controllers\TraditionController::class, 'get_traditions_by_school_of_thought'])->name('registration.traditions.get_by_school_of_thought');

Route::group(['middleware' => ['member', 'verified']], function () {
    Route::get('/member/ai-dashboard', [V1PlatformConsoleController::class, 'member'])->name('member.v1_dashboard');
});


Route::group(['middleware' => ['member', 'verified', 'check.package']], function () {
    Route::controller(HomeController::class)->middleware('activated')->group(function () {
        Route::post('/new-user-email', 'update_email')->name('user.change.email');
        Route::post('/new-user-verification', 'new_verify')->name('user.new.verify');

        Route::any('/member-listing', 'member_listing')->name('member.listing');
        Route::get('/member-profile/{id}', 'view_member_profile')->name('member_profile');

        Route::post('/user/remaining_package_value', 'user_remaining_package_value')->name('user.remaining_package_value');
    });


    Route::controller(MemberController::class)->group(function () {
        // Password Change
        Route::middleware('activated')->group(function () {
            Route::get('/members/change-password', 'change_password')->name('member.change_password');
            Route::post('/member/password-update/{id}', 'password_update')->name('member.password_update');
            Route::get('/profile-settings', 'profile_settings')->name('profile_settings');
        });
        // Account deacticvation & deletation
        Route::post('/member/account-activation', 'update_account_deactivation_status')->name('member.account_deactivation');
        Route::post('/member/account-delete', 'account_delete')->name('member.account_delete');
    });

    Route::middleware('activated')->group(function () {
        Route::get('/package-payment-methods/{id}', [PackageController::class, 'package_payemnt_methods'])->name('package_payment_methods');
        Route::controller(PackagePaymentController::class)->group(function () {
            Route::post('/package-payment', 'store')->name('package.payment');
            Route::get('/package-purchase-history', 'package_purchase_history')->name('package_purchase_history');
            Route::get('/free-package-purchase/{id}', 'free_package_purchase')->name('free_package_purchase');

        });

        Route::controller(PackageUsageController::class)->group(function () {
            Route::get('/package-usage-history', 'index')->name('package_usage_history');
        });

        // Gallery Image
        Route::resource('/gallery-image', GalleryImageController::class)->except(['destroy']);
        Route::get('/gallery_image/destroy/{id}', [GalleryImageController::class, 'destroy'])->name('gallery_image.destroy');

        // Express Interest
        Route::resource('/express-interest', ExpressInterestController::class);
        Route::controller(ExpressInterestController::class)->group(function () {
            Route::get('/my-interests', 'index')->name('my_interests.index');
            Route::get('/interest/requests', 'interest_requests')->name('interest_requests');
            Route::post('/interest/accept', 'accept_interest')->name('accept_interest');
            Route::post('/interest/reject', 'reject_interest')->name('reject_interest');
            Route::post('/interest/remove', 'remove_interest')->name('remove_interest');
        });

        // Chat
        Route::controller(ChatController::class)->group(function () {
            Route::get('/chat', 'index')->name('all.messages');
            Route::get('/single-chat/{id}', 'chat_view')->name('chat_view');
            Route::post('/chat-reply', 'chat_reply')->name('chat.reply');
            Route::get('/chat/refresh/{id}', 'chat_refresh')->name('chat_refresh');
            Route::post('/chat/old-messages', 'get_old_messages')->name('get-old-message');
            Route::get('/chat/unread-count', 'unread_count')->name('chat.unread_count');
        });

        // ShortList list, Add, Remove
        Route::controller(ShortlistController::class)->group(function () {
            Route::get('/my-shortlists', 'index')->name('my_shortlists');
            Route::post('/member/add-to-shortlist', 'create')->name('member.add_to_shortlist');
            Route::post('/member/remove-from-shortlist', 'remove')->name('member.remove_from_shortlist');
        });

        // Ignore list, Add, Remove
        Route::controller(IgnoredUserController::class)->group(function () {
            Route::get('/ignored-list', 'index')->name('my_ignored_list');
            Route::post('/member/add-to-ignore-list', 'add_to_ignore_list')->name('member.add_to_ignore_list');
            Route::post('/member/remove-from-ignored-list', 'remove_from_ignored_list')->name('member.remove_from_ignored_list');
        });

        // Profile_picture view request 
        Route::resource('/profile-picture-view-request', ViewProfilePictureController::class);
        Route::controller(ViewProfilePictureController::class)->group(function () {
            Route::post('/profile-picture-view-request/accept', 'accept_request')->name('profile_picture_view_request_accept');
            Route::post('/profile-picture-view-request/reject', 'reject_request')->name('profile_picture_view_request_reject');
        });


        // Gallery Image View Request
        Route::resource('/gallery-image-view-request', ViewGalleryImageController::class);
        Route::controller(ViewGalleryImageController::class)->group(function () {
            Route::post('/gallery-image-view-request/accept', 'accept_request')->name('gallery_image_view_request_accept');
            Route::post('/gallery-image-view-request/reject', 'reject_request')->name('gallery_image_view_request_reject');
        });


        Route::resource('reportusers', ReportedUserController::class);
        Route::resource('view_contacts', ViewContactController::class);

        // Wallet
        Route::controller(WalletController::class)->group(function () {
            Route::get('/wallet', 'index')->name('wallet.index');
            Route::get('/wallet-recharge-methods', 'wallet_recharge_methods')->name('wallet.recharge_methods');
            Route::post('/recharge', 'recharge')->name('wallet.recharge');
        });

        Route::get('/member/notifications', [NotificationController::class, 'frontend_notify_listing'])->name('frontend.notifications');

        Route::controller(HappyStoryController::class)->group(function () {
            Route::get('/happy-story', 'create')->name('happy_story.member');
            Route::post('/happy-story/store', 'store')->name('happy_story.store');
        });

        Route::resource('profile-viewers', ProfileViewerController::class);
        Route::get('/matched-profiles', [ProfileMatchController::class, 'myMatchedProfiles'])->name('my_matched_profiles');
        Route::get('/horoscope-matched-profiles', [HoroscopeProfileMatchController::class, 'horoscopeMatchedProfiles'])->name('horoscope_matched_profiles');
        Route::get('/matched-refresh', [ProfileMatchController::class, 'matchedRefresh'])->name('match.refresh');
        Route::get('/horoscope-matched-refresh', [HoroscopeProfileMatchController::class, 'horoscopeMatchedRefresh'])->name('horoscope.match.refresh');
    });
});

Route::group(['middleware' => ['auth','activated']], function () {

    // member info edit
    Route::controller(MemberController::class)->group(function () {
        Route::post('/members/introduction_update/{id}', 'introduction_update')->name('member.introduction.update');
        Route::post('/members/basic_info_update/{id}', 'basic_info_update')->name('member.basic_info_update');
        Route::post('/members/language_info_update/{id}', 'language_info_update')->name('member.language_info_update');
    });


    Route::resource('/address', AddressController::class);

    // Member education
    Route::resource('/education', EducationController::class)->except(['create', 'edit', 'destroy']);
    Route::controller(EducationController::class)->group(function () {
        Route::post('/education/create', 'create')->name('education.create');
        Route::post('/education/edit', 'edit')->name('education.edit');
        Route::post('/education/update_education_present_status', 'update_education_present_status')->name('education.update_education_present_status');
        Route::post('/education/update-highest-degree', 'updateHighestDegree')->name('education.update_highest_degree');

        Route::get('/education/destroy/{id}', 'destroy')->name('education.destroy');
    });


    // Member Career
    Route::resource('/career', CareerController::class)->except(['create', 'edit', 'destroy']);
    Route::controller(CareerController::class)->group(function () {
        Route::post('/career/create', 'create')->name('career.create');
        Route::post('/career/edit', 'edit')->name('career.edit');
        Route::post('/career/update_career_present_status', 'update_career_present_status')->name('career.update_career_present_status');
        Route::get('/career/destroy/{id}', 'destroy')->name('career.destroy');
    });

    Route::resource('/physical-attribute', PhysicalAttributeController::class);
    Route::resource('/hobbies', HobbyController::class);
    Route::resource('/attitudes', AttitudeController::class);
    Route::resource('/recidencies', RecidencyController::class);
    Route::resource('/lifestyles', LifestyleController::class);
    Route::resource('/astrologies', AstrologyController::class);
    Route::resource('/families', FamilyController::class);
    Route::resource('/spiritual_backgrounds', SpiritualBackgroundController::class);
    Route::resource('/partner_expectations', PartnerExpectationController::class);
    Route::post('/additional-member-info/update', [AdditionalMemberInfoController::class, 'update'])->name('additional_member_info.update');

    Route::post('/states/get_state_by_country', [StateController::class, 'get_state_by_country'])->name('states.get_state_by_country');
    Route::post('/cities/get_cities_by_state', [CityController::class, 'get_cities_by_state'])->name('cities.get_cities_by_state');
    Route::post('/castes/get_caste_by_religion', [CasteController::class, 'get_caste_by_religion'])->name('castes.get_caste_by_religion');
    Route::post('/sub-castes/get_sub_castes_by_religion', [SubCasteController::class, 'get_sub_castes_by_religion'])->name('sub_castes.get_sub_castes_by_religion');

    Route::get('/package-payment-invoice/{id}', [PackagePaymentController::class, 'package_payment_invoice'])->name('package_payment.invoice');

    Route::controller(NotificationController::class)->group(function () {
        Route::get('/notification-view/{id}', 'notification_view')->name('notification_view');
        Route::get('/notification/mark-all-as-read', 'mark_all_as_read')->name('notification.mark_all_as_read');
    });
});

// Contact Us page
Route::controller(ContactUsController::class)->group(function () {
    Route::get('/contact-us/page', 'show_contact_us_page')->name('contact_us');
    Route::post('/contact-us', 'store')->name('contact-us.store');
});

// Payment gateway Redirect

//Paypal START
Route::get('/paypal/payment/done', [PaypalController::class, 'getDone'])->name('payment.done');
Route::get('/paypal/payment/cancel', [PaypalController::class, 'getCancel'])->name('payment.cancel');
//Paypal END

//amarpay

Route::post('/aamarpay/success', [AamarpayController::class, 'success'])->name('aamarpay.success');
Route::post('/aamarpay/fail', [AamarpayController::class, 'fail'])->name('aamarpay.fail');

// SSLCOMMERZ Start
Route::get('/sslcommerz/pay', [SslcommerzController::class, 'index']);
Route::any('/sslcommerz/success', [SslcommerzController::class, 'success'])->name('sslcommerz.success');
Route::any('/sslcommerz/fail', [SslcommerzController::class, 'fail']);
Route::any('/sslcommerz/cancel', [SslcommerzController::class, 'cancel']);
Route::post('/sslcommerz/ipn', [SslcommerzController::class, 'ipn']);


Route::get('/instamojo/payment/pay-success', [InstamojoController::class, 'success'])->name('instamojo.success');
Route::post('rozer/payment/pay-success', [RazorpayController::class, 'payment'])->name('payment.rozer');
Route::get('/paystack/payment/callback', [PaystackController::class, 'handleGatewayCallback']);

//Stipe Start
Route::controller(StripeController::class)->group(function () {
    Route::get('stripe', 'stripe');
    Route::post('/stripe/create-checkout-session', 'create_checkout_session')->name('stripe.get_token');
    Route::any('/stripe/payment/callback', 'callback')->name('stripe.callback');
    Route::get('/stripe/success', 'success')->name('stripe.success');
    Route::get('/stripe/cancel', 'cancel')->name('stripe.cancel');
});
//Stripe END

//Paytm
Route::get('/paytm/index', [PaytmController::class, 'index']);
Route::post('/paytm/callback', [PaytmController::class, 'callback'])->name('paytm.callback');

// phonepe
Route::controller(PhonepeController::class)->group(function () {
    Route::any('/phonepe/pay', 'pay')->name('phonepe.pay');
    Route::any('/phonepe/redirecturl', 'phonepe_redirecturl')->name('phonepe.redirecturl');
    Route::any('/phonepe/callbackUrl', 'phonepe_callbackUrl')->name('phonepe.callbackUrl');
});

Route::get('/customer-profile/admin', [HomeController::class, 'profile_edit'])->name('profile.edit');
Route::get('/check_for_package_invalid', [PackageController::class, 'check_for_package_invalid'])->name('member.check_for_package_invalid');

Route::get('/match_profiles', [ProfileMatchController::class, 'match_profiles'])->name('match_profiles');
Route::get('/migrate/profiles/', [ProfileMatchController::class, 'migrate_profiles']);

Route::get('/match_profiles', [HoroscopeProfileMatchController::class, 'match_profiles'])->name('horoscope_match_profiles');
Route::get('/migrate/profiles/', [HoroscopeProfileMatchController::class, 'migrate_profiles']);


require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/otp.php';
require __DIR__.'/referral.php';
require __DIR__.'/support_tickets.php';



// API Documentation Preview
Route::get('/api-docs', function () {
    $path = base_path('API_DOCUMENTATION.md');
    if (!file_exists($path)) {
        abort(404, 'API documentation file not found.');
    }
    $content = file_get_contents($path);
    return view('api_docs', [
        'content' => Illuminate\Support\Str::markdown($content),
        'app_url' => config('app.url'),
    ]);
})->name('api.docs');

//Custom page
Route::get('/{slug}', [PageController::class, 'show_custom_page'])->name('custom-pages.show_custom_page');

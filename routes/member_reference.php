<?php
use App\Http\Controllers\Member\ReferenceScreensController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','member','verified'])->prefix('member/reference')->name('member.reference.')->group(function (): void {
    Route::get('/home', [ReferenceScreensController::class,'home'])->name('home');
    Route::post('/settings/privacy', [ReferenceScreensController::class,'savePrivacy'])->name('settings.privacy');
    Route::post('/settings/notifications', [ReferenceScreensController::class,'saveNotificationSummary'])->name('settings.notifications');
    Route::get('/meetings', [ReferenceScreensController::class,'meetings'])->name('meetings');
    Route::post('/meetings', [ReferenceScreensController::class,'storeMeeting'])->middleware('throttle:5,1')->name('meetings.store');
    Route::get('/packages', [ReferenceScreensController::class,'packages'])->name('packages');
    Route::get('/assistant', [ReferenceScreensController::class,'assistant'])->name('assistant');
    Route::post('/assistant', [ReferenceScreensController::class,'assistantAction'])->middleware('throttle:10,1')->name('assistant.run');
    Route::get('/blogs', [ReferenceScreensController::class,'blogs'])->name('blogs');
    Route::get('/blogs/{slug}', [ReferenceScreensController::class,'blogDetail'])->name('blog.details');
    Route::get('/stories', [ReferenceScreensController::class,'stories'])->name('stories');
    Route::get('/settings', [ReferenceScreensController::class,'settings'])->name('settings');
});

<?php

use App\Http\Controllers\ClientScope\ClientScopeController;
use Illuminate\Support\Facades\Route;

// These authenticated browser endpoints intentionally do not change API/v1
// contracts, which are separately consumed by the mobile application.
Route::middleware(['auth'])->prefix('member/client-scope')->name('client.scope.')->group(function (): void {
    Route::get('/', [ClientScopeController::class, 'portal'])->name('portal');
    Route::get('/support', [ClientScopeController::class, 'support'])->name('support');
    Route::get('/notifications', [ClientScopeController::class, 'notifications'])->name('notifications');
    Route::post('/notifications', [ClientScopeController::class, 'saveNotifications'])->name('notifications.save');
    Route::get('/satisfaction', [ClientScopeController::class, 'satisfaction'])->name('satisfaction');
    Route::post('/satisfaction', [ClientScopeController::class, 'submitSatisfaction'])->middleware('throttle:5,1')->name('satisfaction.submit');
    Route::post('/got-match', [ClientScopeController::class, 'gotMatch'])->middleware('throttle:10,1')->name('got-match');
    Route::post('/sponsor/{sponsor}/impression', [ClientScopeController::class, 'sponsoredImpression'])->middleware('throttle:30,1')->name('sponsor.impression');
    Route::get('/sponsor/{sponsor}/visit', [ClientScopeController::class, 'sponsoredVisit'])->middleware('throttle:30,1')->name('sponsor.visit');
});

Route::middleware(['auth', 'admin'])->prefix('admin/client-scope')->name('admin.client.scope.')->group(function (): void {
    Route::get('/', [ClientScopeController::class, 'adminDashboard'])->name('dashboard');
    Route::get('/profiles', [ClientScopeController::class, 'adminProfiles'])->name('profiles');
    Route::get('/match-outcomes', [\App\Http\Controllers\Admin\MatchOutcomeReviewController::class, 'index'])->name('outcomes.index');
    Route::post('/match-outcomes/{outcome}', [\App\Http\Controllers\Admin\MatchOutcomeReviewController::class, 'review'])->name('outcomes.review');
    Route::post('/city-coordinates', [\App\Http\Controllers\Admin\CityGeoCoordinateController::class, 'store'])->name('city_coordinates.store');
    Route::post('/options', [ClientScopeController::class, 'setOptions'])->name('options');
    Route::post('/sponsors', [ClientScopeController::class, 'storeSponsor'])->name('sponsors.store');
    Route::put('/sponsors/{sponsor}', [ClientScopeController::class, 'updateSponsor'])->name('sponsors.update');
    Route::delete('/sponsors/{sponsor}', [ClientScopeController::class, 'removeSponsor'])->name('sponsors.disable');
    Route::post('/rewards/rules', [ClientScopeController::class, 'saveRewardRule'])->name('rewards.rules');
});

<?php

use App\Http\Controllers\Api\V1\System\BridgeController;
use Illuminate\Support\Facades\Route;

Route::get('/connector-a', [BridgeController::class, 'channelA'])->name('connector_a');
Route::get('/connector-b', [BridgeController::class, 'channelB'])->name('connector_b');

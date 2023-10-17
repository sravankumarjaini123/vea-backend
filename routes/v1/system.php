<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\System\SystemSettingsController;
use App\Http\Controllers\Notifications\JobStatusNotificationController;

/*
|--------------------------------------------------------------------------
| API Routes for System Settings
|--------------------------------------------------------------------------
|
*/

// Event Listener Route
Route::get('channels/subscribe/event/{id}',[JobStatusNotificationController::class, 'statusNotification'])
    ->name('posts.wordpress.events');

// Grab all the Notifications of the System
Route::get('system/notifications', [SystemSettingsController::class, 'systemNotifications'])
    ->name('system.notifications');

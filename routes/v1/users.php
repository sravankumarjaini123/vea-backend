<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Users\AuthenticationController;
use App\Http\Controllers\Users\RegistrationController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\TwoFactorAuthentication\TwoFactorController;

/*
|--------------------------------------------------------------------------
| USERS Routes
|--------------------------------------------------------------------------
|
| Here yu can find the routes for the Users which connects both
| master and existing system - we keep update with all the details
| of User from master.
|
*/

Route::group(['middleware' => config('fortify.middleware', ['web']),'prefix' => 'user'], function () {

    Route::post('register',[RegistrationController::class, 'register'])->name('user.register')->middleware('auth:api')->middleware(['role:users,1']);

    Route::post('login',[AuthenticationController::class, 'login'])->name('user.login');

    Route::post('twoFactorAuth/verify/{id}',[TwoFactorController::class, 'verify'])->name('2FA.verify');

    Route::post('loginAfter2FA/{id}',[AuthenticationController::class, 'loginAfter2FA'])->name('2FA.after.login');

});

Route::group(['prefix' =>'forgotPassword'],function (){

    Route::post('email', [UserController::class, 'emailConfirmationWithCode'])->name('user.emailConfirmation');

    Route::post('code', [UserController::class, 'codeConfirmation'])->name('user.codeConfirmation');

    Route::post('reset', [UserController::class, 'resetPassword'])->name('user.reset.password');
});

Route::group(['middleware' => ['auth:api'], 'prefix' => 'user'],function(){

    Route::post('updateEmail/{id}', [UserController::class, 'updateEmail'])->name('user.update-email')->middleware(['role:users,3']);

    Route::post('updatePassword/{id}', [UserController::class, 'updatePassword'])->name('user.update-password')->middleware(['role:users,3']);

    Route::post('personal/{id}', [UserController::class, 'updatePersonal'])->name('user.update-personal')->middleware(['role:users,3']);

    Route::post('role/{id}', [UserController::class, 'updateUserRoles'])->name('users.role.update')->middleware(['role:users,2']);

    Route::post('passwordConfirmation/{id}', [TwoFactorController::class, 'passwordConfirmation'])->name('password-confirm');

    Route::get('logout', [AuthenticationController::class, 'destroy'])->name('user.logout');

    Route::delete('{id}', [RegistrationController::class, 'destroy'])->name('users.destroy')->middleware(['role:users,4']);

    /* BEGIN -- TWO FACTOR AUTHENTICATION */
    Route::group(['prefix' =>'twoFactorAuth'],function (){

        Route::post('enable/{id}', [TwoFactorController::class, 'store'])->name('2FA.enable');

        Route::get('QRCode/{id}', [TwoFactorController::class, 'show'])->name('2FA.qrCode');

        Route::get('recoveryCodes/{id}',[TwoFactorController::class, 'index'])->name('2FA.recoveryCodes');

        Route::delete('disable/{id}',[TwoFactorController::class, 'destroy'])->name('2FA.disable');

    });
    /* END -- TWO FACTOR AUTHENTICATION */
});

Route::group(['middleware' => ['auth:api']], function () {

    Route::get('users', [UserController::class, 'index'])->name('user.index')->middleware(['role:users,1']);

    Route::get('user/profile', [UserController::class, 'getUserProfile'])->name('user.profile');

    Route::get('user/{id}', [UserController::class, 'show'])->name('user.show')->middleware(['role:users,1']);

    Route::get('user/notifications/{id}', [UserController::class, 'notifications'])->name('users.notifications');

    Route::delete('users/{id}', [UserController::class, 'destroy'])->name('users.destroys')->middleware(['role:users,4']);
});

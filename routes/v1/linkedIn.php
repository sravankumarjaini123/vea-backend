<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LinkedIn\LinkedInController;

/*
|--------------------------------------------------------------------------
| API Routes for LinkedIn
|--------------------------------------------------------------------------
| Post through our system to the LinkedIn on Use based
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'linkedIns'], function () {

    Route::get('',[LinkedInController::class, 'index'])->name('linkedIn.index')->middleware(['role:linkedin,1']);

    Route::post('',[LinkedInController::class, 'store'])->name('linkedIn.store')->middleware(['role:linkedin,2']);

    Route::get('{id}',[LinkedInController::class, 'show'])->name('linkedIn.show')->middleware(['role:linkedin,1']);

    Route::post('{id}',[LinkedInController::class, 'update'])->name('linkedIn.show')->middleware(['role:linkedin,3']);

    Route::get('authorizeURL/{id}',[LinkedInController::class, 'getAuthorizationURL'])->name('linkedIn.authorizeURL');

    Route::post('users/{id}/{user_id}',[LinkedInController::class, 'authorizeUserAccessToken'])->name('linkedIn.user');

    Route::post('users/refreshToken/{id}/{user_id}',[LinkedInController::class, 'refreshTokenWithAccessToken'])->name('linkedIn.refreshTokens');

    Route::post('posts/share/{id}/{user_id}',[LinkedInController::class, 'sharePosts'])->name('linkedIn.share')->middleware(['role:post,3']);

    Route::post('posts/reShare/{id}/{user_id}',[LinkedInController::class, 'reSharePosts'])->name('linkedIn.share')->middleware(['role:post,3']);

    Route::post('posts/share/update/{id}/{user_id}',[LinkedInController::class, 'updateSharesPosts'])->name('linkedIn.share.update')->middleware(['role:post,3']);

    Route::post('posts/share/delete/{id}/{user_id}',[LinkedInController::class, 'deleteSharesPosts'])->name('linkedIn.share.delete')->middleware(['role:post,3']);

    Route::delete('users/disconnect/{id}/{user_id}', [LinkedInController::class, 'disconnectUser'])->name('linkedIn.disconnect');

});

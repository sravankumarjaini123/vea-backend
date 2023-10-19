<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Twitter\TwitterController;
/*
|--------------------------------------------------------------------------
| API Routes for MasterData - TAGS
|--------------------------------------------------------------------------
| CRUD operations for complete File management
| Folders and File are handled here
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'twitters'], function () {

    Route::get('', [TwitterController::class, 'index'])->name('twitter.get')->middleware(['role:twitter,1']);

    Route::get('{id}',[TwitterController::class, 'show'])->name('twitter.show')->middleware(['role:twitter,1']);

    Route::get('users/shared/accounts',[TwitterController::class, 'showShareableAccounts'])->name('twitter.show.shareableAccounts')->middleware(['role:twitter,1']);

    Route::get('authorizeURL/{id}',[TwitterController::class, 'getAuthorizationURL'])->name('twitter.show');

    Route::get('metrics/{id}/{user_id}',[TwitterController::class, 'getMetrics'])->name('twitter.show');

    Route::post('', [TwitterController::class, 'store'])->name('twitter.store')->middleware(['role:twitter,2']);

    Route::post('{id}', [TwitterController::class, 'update'])->name('twitter.update')->middleware(['role:twitter,3']);

    Route::post('users/password/{id}', [TwitterController::class, 'shareablePassword'])->name('twitter.user.shareable.password');

    Route::post('posts/tweet/{id}', [TwitterController::class, 'tweetPosts'])->name('twitter.tweet.post')->middleware(['role:posts,3']);

    Route::post('posts/retweet/{id}', [TwitterController::class, 'reTweetPosts'])->name('twitter.reTweet.post')->middleware(['role:posts,3']);

    Route::post('refreshToken/{id}', [TwitterController::class, 'generateRefreshToken'])->name('twitter.refresh.token')->middleware(['role:twitter,3']);

    Route::post('posts/tweet/delete/{id}', [TwitterController::class, 'deleteTweetsPosts'])->name('twitter.tweet.post')->middleware(['role:posts,3']);

    Route::post('users/{id}/{user_id}', [TwitterController::class, 'authorizeUserAccessToken'])->name('twitter.user.authorize');

    Route::post('users/existing/{id}/{user_id}', [TwitterController::class, 'authenticateExistingUser'])->name('twitter.user.shareable.password');

    Route::delete('users/disconnect/{id}', [TwitterController::class, 'disconnectUser'])->name('twitter.disconnect.user');

    Route::delete('{id}', [TwitterController::class, 'destroy'])->name('twitter.destroy')->middleware(['role:twitter,4']);
});

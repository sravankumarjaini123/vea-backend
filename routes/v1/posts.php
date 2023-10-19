<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Posts\PostController;
use App\Http\Controllers\Posts\PostsSyncController;

/*
|--------------------------------------------------------------------------
| API Routes for POSTS
|--------------------------------------------------------------------------
| Status - when need to publish, inactive, drafted
| Details - Categories, tags, Groups
| Type - creation of different type of posts like image, video and audio
| CRUD Operations on required fields like title, files attachments
| Wordpress Sync and UnSync of the Posts on updating the content
| SEO operations for marketing
|
*/

Route::group(['middleware' => ['auth:api']], function () {

    /* BEGIN -- POSTS */

    Route::get('posts/overview/{search_keyword?}/{limit?}',[PostController::class, 'index'])->name('posts.index')->middleware(['role:posts,1']);

    Route::get('posts/wordpress/{id}',[PostController::class, 'getPostWordpress'])->name('posts.wordpress');

    Route::get('posts/retrieve',[PostController::class, 'retrieve'])->name('posts.retrieve')->middleware(['role:posts,1']);

    Route::post('posts',[PostController::class, 'store'])->name('posts.store')->middleware(['role:posts,2']);

    Route::get('status',[PostController::class, 'getStatus'])->name('posts.status');

    Route::group(['prefix' => 'posts'], function (){

        /* BEGIN -- POSTS - UPDATE */
        Route::post('duplicate/{id}',[PostController::class, 'duplicatePosts'])->name('posts.duplicate')->middleware(['role:posts,1']);

        Route::post('testing',[PostController::class, 'initiateFactory'])->name('posts.factors')->middleware(['role:posts,3']);

        Route::post('general/{id}',[PostController::class, 'updateGeneral'])->name('posts.general')->middleware(['role:posts,3']);

        Route::post('media/{id}',[PostController::class, 'updateMedia'])->name('posts.media')->middleware(['role:posts,3']);

        Route::post('gallery/{id}',[PostController::class, 'updateGalleries'])->name('posts.galleries')->middleware(['role:posts,3']);

        Route::post('related/{id}',[PostController::class, 'updateRelatedPosts'])->name('posts.relatedPosts')->middleware(['role:posts,3']);

        Route::post('relatedEvents/{id}',[PostController::class, 'updateRelatedEvents'])->name('posts.relatedEvents');

        Route::post('relatedCourses/{id}',[PostController::class, 'updateRelatedCourses'])->name('posts.relatedCourses');

        Route::post('files/{id}',[PostController::class, 'updateFiles'])->name('posts.files')->middleware(['role:posts,3']);

        Route::post('status/{id}',[PostController::class, 'updateStatus'])->name('posts.status')->middleware(['role:posts,3']);

        Route::post('topPost/{id}',[PostController::class, 'updateTopPost'])->name('posts.topPost')->middleware(['role:posts,3']);

        Route::post('metaDetails/{id}',[PostController::class, 'updateMetaDetails'])->name('posts.metaDetails')->middleware(['role:posts,3']);

        Route::post('type/{id}',[PostController::class, 'updateType'])->name('posts.type')->middleware(['role:posts,3']);

        Route::post('visibility/{id}',[PostController::class, 'updateVisibility'])->name('posts.visibility')->middleware(['role:posts,3']);

        Route::post('filter',[PostController::class, 'getFilterPosts'])->name('posts.filter')->middleware(['role:posts,3']);
        /* END -- POSTS - UPDATE */

        /* BEGIN -- POSTS - WORDPRESS */
        Route::post('channels/attach/{id}',[PostsSyncController::class, 'postsChannelsAttach'])->name('posts.attach.wordpress')->middleware(['role:posts,3']);

        Route::post('channels/detach/{id}',[PostsSyncController::class, 'postsChannelsDetach'])->name('posts.attach.wordpress')->middleware(['role:posts,3']);

        Route::post('channels/sync',[PostsSyncController::class, 'postsChannelsSync'])->name('posts.wordpress.sync')->middleware(['role:posts,3']);

        Route::post('channels/sync/{id}',[PostsSyncController::class, 'postsChannelsSyncById'])->name('posts.wordpress.sync')->middleware(['role:posts,3']);

        Route::post('channels/callbackURL/{id}',[PostsSyncController::class, 'getShareableURL'])->name('posts.shareable.url')->middleware(['role:posts,3']);
        /* END -- POSTS - WORDPRESS */

        /* BEGIN -- POSTS - ADDITIONAL */
        Route::post('groups/{id}',[PostController::class, 'updateGroups'])->name('posts.updateGroups')->middleware(['role:posts,3']);

        Route::post('categories/{id}',[PostController::class, 'updateCategories'])->name('posts.updateCategories')->middleware(['role:posts,3']);

        Route::post('tags/{id}',[PostController::class, 'updateTags'])->name('posts.updateTags')->middleware(['role:posts,3']);

        Route::post('authors/{id}',[PostController::class, 'updateAuthors'])->name('posts.updateAuthors')->middleware(['role:posts,3']);

        Route::post('shareableContent/{id}',[PostController::class, 'updateShareableContent'])->name('posts.update.shareableContent')->middleware(['role:posts,3']);

        Route::post('shareableDescription/{id}',[PostController::class, 'updateShareableDescription'])->name('posts.update.shareableDescription')->middleware(['role:posts,3']);
        /* END -- POSTS - ADDITIONAL */

        /* BEGIN -- POSTS - TRASH */
        Route::post('restore/{id}',[PostController::class, 'restore'])->name('posts.restore')->middleware(['role:posts,3']);

        Route::post('massRestore',[PostController::class, 'massRestore'])->name('posts.massRestore')->middleware(['role:posts,3']);

        Route::post('massDelete',[PostController::class, 'massDelete'])->name('posts.massDelete')->middleware(['role:posts,4']);

        Route::post('forceDelete/{id}',[PostController::class, 'forceDelete'])->name('posts.forceDelete')->middleware(['role:posts,4']);

        Route::post('massForceDelete',[PostController::class, 'massForceDelete'])->name('posts.massForceDelete')->middleware(['role:posts,4']);
        /* END -- POSTS - TRASH */
    });

    Route::delete('posts/{id}',[PostController::class, 'destroy'])->name('posts.destroy')->middleware(['role:posts,4']);
    /* END -- POSTS */
});

Route::get('posts/channels/subscribe/event/{id}',[JobStatusNotificationController::class, 'statusNotification'])
    ->name('posts.wordpress.events')->middleware(['cors']);

Route::get('posts/{id}/{channel?}/{contacts?}',[PostController::class, 'show'])->name('posts.show');

Route::post('posts/checkout/sessions/{id}', [StripeProductController::class, 'getCheckoutDetailsForWebAndApp'])->name('posts.products.getCheckoutURL');

Route::post('posts/checkout/paymentIntent/{id}', [StripeProductController::class, 'getCheckoutDetailsForWebAndApp'])->name('posts.products.getPaymentIntent');

Route::post('posts/checkout/offers/{id}', [StripeOfferController::class, 'getCheckoutOfferForPostsAndCourses'])->name('posts.products.getCheckoutOffer');


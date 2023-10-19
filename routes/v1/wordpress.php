<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Wordpress\WordpressController;

/*
|--------------------------------------------------------------------------
| API Routes for Wordpress
|--------------------------------------------------------------------------
| Save the credentials of the Word press to sync the settings
|
*/

Route::group(['middleware' => ['auth:api']], function () {

    /* BEGIN -- GROUPS */
    Route::get('wordpress',[WordpressController::class, 'index'])->name('wordpress.index')->middleware(['role:wordpress,1']);

    Route::get('wordpress/posts', [WordpressController::class, 'wordpressPosts'])->name('wordpress.posts');

    Route::post('wordpress',[WordpressController::class, 'store'])->name('wordpress.store')->middleware(['role:wordpress,2']);

    Route::get('wordpress/{id}',[WordpressController::class, 'show'])->name('wordpress.show')->middleware(['role:wordpress,1']);

    Route::post('wordpress/{id}',[WordpressController::class, 'update'])->name('wordpress.update')->middleware(['role:wordpress,3']);

    Route::delete('wordpress/{id}',[WordpressController::class, 'destroy'])->name('wordpress.destroy')->middleware(['role:wordpress,4']);
    /* END -- GROUPS */

});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Videos\ExternalVideosController;
/*
|--------------------------------------------------------------------------
| API Routes for External Videos
|--------------------------------------------------------------------------
| CRUD operations for complete external-videos
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'videos'], function () {

    /* BEGIN -- External Videos */
    Route::get('',[ExternalVideosController::class, 'index'])->name('external-videos.index')->middleware(['role:external-videos,1']);

    Route::post('',[ExternalVideosController::class, 'store'])->name('external-videos.store')->middleware(['role:external-videos,2']);

    Route::get('{id}',[ExternalVideosController::class, 'show'])->name('external-videos.show')->middleware(['role:external-videos,1']);

    Route::post('massDelete',[ExternalVideosController::class, 'massDelete'])->name('external-videos.massDestroy')->middleware(['role:external-videos,4']);

    Route::post('{id}',[ExternalVideosController::class, 'update'])->name('external-videos.update')->middleware(['role:external-videos,3']);

    Route::delete('{id}',[ExternalVideosController::class, 'destroy'])->name('external-videos.destroy')->middleware(['role:external-videos,4']);
    /* END -- External Videos */

});

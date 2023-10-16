<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tags\TagController;

/*
|--------------------------------------------------------------------------
| API Routes for MasterData - TAGS
|--------------------------------------------------------------------------
| CRUD operations for complete File management
| Folders and File are handled here
|
*/

Route::group(['middleware' => ['auth:api']], function (){

    /* BEGIN -- TAGS */
    Route::get('tags', [TagController::class, 'index'])->name('tags.index')->middleware(['role:tags,1']);

    Route::post('tags/filter',[TagController::class, 'filter'])->name('tags.filter');

    Route::post('tags', [TagController::class, 'store'])->name('tags.store')->middleware(['role:tags,2']);

    Route::get('tags/{id}', [TagController::class, 'show'])->name('tags.show')->middleware(['role:tags,1']);

    Route::post('tags/sorting', [TagController::class, 'sorting'])->name('tags.sorting')->middleware(['role:tags,3']);

    Route::post('tags/massDelete', [TagController::class, 'massDelete'])->name('tags.massDestroy')->middleware(['role:tags,4']);

    Route::post('tags/{id}', [TagController::class, 'update'])->name('tags.update')->middleware(['role:tags,3']);

    Route::delete('tags/{id}', [TagController::class, 'destroy'])->name('tags.destroy')->middleware(['role:tags,4']);
    /* END -- TAGS */

});

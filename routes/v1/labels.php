<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Labels\LabelController;
/*
|--------------------------------------------------------------------------
| API Routes for MasterData - Labels
|--------------------------------------------------------------------------
| CRUD operations for complete Labels - Especially for the Contacts and Partners
|
*/

Route::group(['middleware' => ['auth:api']], function () {

    /* BEGIN -- labels */
    Route::get('labels',[LabelController::class, 'index'])->name('labels.index')->middleware(['role:labels,1']);

    Route::post('labels',[LabelController::class, 'store'])->name('labels.store')->middleware(['role:labels,2']);

    Route::get('labels/{id}',[LabelController::class, 'show'])->name('labels.show')->middleware(['role:labels,1']);

    Route::post('labels/sorting', [LabelController::class, 'sorting'])->name('labels.sorting')->middleware(['role:labels,3']);

    Route::post('labels/massDelete',[LabelController::class, 'massDelete'])->name('labels.massDestroy')->middleware(['role:labels,4']);

    Route::post('labels/{id}',[LabelController::class, 'update'])->name('labels.update')->middleware(['role:labels,3']);

    Route::delete('labels/{id}',[LabelController::class, 'destroy'])->name('labels.destroy')->middleware(['role:labels,4']);
    /* END -- labels */

});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Measures\MeasureTypeController;

/*
|--------------------------------------------------------------------------
| API Routes for MasterData - Measures Types
|--------------------------------------------------------------------------
| CRUD operations for complete Measures Types
|
*/

Route::group(['middleware' => ['auth:api'],  'prefix' => 'measures'], function () {

    /* BEGIN -- Measures Types */
    Route::get('types',[MeasureTypeController::class, 'index'])->name('measureType.index')->middleware(['role:measure-masterdata,1']);

    Route::post('types',[MeasureTypeController::class, 'store'])->name('measureType.store')->middleware(['role:measure-masterdata,2']);

    Route::get('types/{id}',[MeasureTypeController::class, 'show'])->name('measureType.show')->middleware(['role:measure-masterdata,1']);

    Route::post('types/sorting', [MeasureTypeController::class, 'sorting'])->name('measureType.sorting')->middleware(['role:measure-masterdata,3']);

    Route::post('types/massDelete',[MeasureTypeController::class, 'massDelete'])->name('measureType.massDestroy')->middleware(['role:measure-masterdata,4']);

    Route::post('types/{id}',[MeasureTypeController::class, 'update'])->name('measureType.update')->middleware(['role:measure-masterdata,3']);

    Route::delete('types/{id}',[MeasureTypeController::class, 'destroy'])->name('measureType.destroy')->middleware(['role:measure-masterdata,4']);
    /* END -- Measures Types */

});

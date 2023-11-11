<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Measures\MeasureProcessorController;

/*
|--------------------------------------------------------------------------
| API Routes for MasterData - Measures Processors
|--------------------------------------------------------------------------
| CRUD operations for complete Measures Processors
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'measures'], function () {

    /* BEGIN -- Measures Processors */
    Route::get('processors',[MeasureProcessorController::class, 'index'])->name('measureProcessor.index')->middleware(['role:measure-masterdata,1']);

    Route::post('processors',[MeasureProcessorController::class, 'store'])->name('measureProcessor.store')->middleware(['role:measure-masterdata,2']);

    Route::get('processors/{id}',[MeasureProcessorController::class, 'show'])->name('measureProcessor.show')->middleware(['role:measure-masterdata,1']);

    Route::post('processors/sorting', [MeasureProcessorController::class, 'sorting'])->name('measureProcessor.sorting')->middleware(['role:measure-masterdata,3']);

    Route::post('processors/massDelete',[MeasureProcessorController::class, 'massDelete'])->name('measureProcessor.massDestroy')->middleware(['role:measure-masterdata,4']);

    Route::post('processors/{id}',[MeasureProcessorController::class, 'update'])->name('measureProcessor.update')->middleware(['role:measure-masterdata,3']);

    Route::delete('processors/{id}',[MeasureProcessorController::class, 'destroy'])->name('measureProcessor.destroy')->middleware(['role:measure-masterdata,4']);
    /* END -- Measures Processors */

});

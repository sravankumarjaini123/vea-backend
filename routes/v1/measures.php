<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Measures\MeasureController;

/*
|--------------------------------------------------------------------------
| API Routes for - MEASURES
|--------------------------------------------------------------------------
| CRUD operations for complete MEASURES
| API's to connect the Master Data of the measures and keep it sync.
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'measures'], function () {

    /* BEGIN -- MEASURE BODIES */
    Route::get('',[MeasureController::class, 'index'])->name('measures.index')->middleware(['role:measure,1']);

    Route::post('',[MeasureController::class, 'store'])->name('measures.store')->middleware(['role:measure,2']);

    Route::get('retrieve',[MeasureController::class, 'retrieve'])->name('measures.retrieve')->middleware(['role:measure,1']);

    Route::get('{id}',[MeasureController::class, 'show'])->name('measures.show')->middleware(['role:measure,1']);

    Route::post('massDelete',[MeasureController::class, 'massDelete'])->name('measures.massDestroy')->middleware(['role:measure,4']);

    Route::post('massRestore',[MeasureController::class, 'massRestore'])->name('measures.massRestore')->middleware(['role:measure,3']);

    Route::post('massForceDelete',[MeasureController::class, 'massForceDelete'])->name('measures.massForceDelete')->middleware(['role:measure,4']);

    Route::post('general/{id}',[MeasureController::class, 'updateGeneral'])->name('measures.updateGeneral')->middleware(['role:measure,3']);

    Route::post('status/{id}',[MeasureController::class, 'updateStatus'])->name('measures.updateStatus')->middleware(['role:measure,3']);

    Route::post('masterData/{id}',[MeasureController::class, 'updateMasterData'])->name('measures.updateMasterData')->middleware(['role:measure,3']);

    Route::post('investment/{id}',[MeasureController::class, 'updateInvestment'])->name('measures.updateInvestment')->middleware(['role:measure,3']);

    Route::post('additional/{id}',[MeasureController::class, 'updateAdditional'])->name('measures.updateInvestment')->middleware(['role:measure,3']);
    /* END -- MEASURE BODIES */

    /* BEGIN -- MEASURE - TRASH */
    Route::post('restore/{id}',[MeasureController::class, 'restore'])->name('measures.restore')->middleware(['role:measure,3']);

    Route::post('forceDelete/{id}',[MeasureController::class, 'forceDelete'])->name('measures.forceDelete')->middleware(['role:measure,4']);
    /* END -- MEASURE - TRASH */

    Route::delete('{id}',[MeasureController::class, 'destroy'])->name('measures.destroy')->middleware(['role:measure,4']);

});

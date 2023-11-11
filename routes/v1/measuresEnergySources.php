<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Measures\MeasureEnergySourceController;

/*
|--------------------------------------------------------------------------
| API Routes for MasterData - Measures Energy Sources
|--------------------------------------------------------------------------
| CRUD operations for complete Measures Energy Sources
|
*/

Route::group(['middleware' => ['auth:api'],  'prefix' => 'measures'], function () {

    /* BEGIN -- Measures Energy Sources */
    Route::get('energySources',[MeasureEnergySourceController::class, 'index'])->name('measureEnergySource.index')->middleware(['role:measure-masterdata,1']);

    Route::post('energySources',[MeasureEnergySourceController::class, 'store'])->name('measureEnergySource.store')->middleware(['role:measure-masterdata,2']);

    Route::get('energySources/{id}',[MeasureEnergySourceController::class, 'show'])->name('measureEnergySource.show')->middleware(['role:measure-masterdata,1']);

    Route::post('energySources/massDelete',[MeasureEnergySourceController::class, 'massDelete'])->name('measureEnergySource.massDestroy')->middleware(['role:measure-masterdata,4']);

    Route::post('energySources/{id}',[MeasureEnergySourceController::class, 'update'])->name('measureEnergySource.update')->middleware(['role:measure-masterdata,3']);

    Route::delete('energySources/{id}',[MeasureEnergySourceController::class, 'destroy'])->name('measureEnergySource.destroy')->middleware(['role:measure-masterdata,4']);
    /* END -- Measures Energy Sources */

});

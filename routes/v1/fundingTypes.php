<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Fundings\FundingTypeController;
/*
|--------------------------------------------------------------------------
| API Routes for MasterData - FUNDING TYPES
|--------------------------------------------------------------------------
| CRUD operations for complete FUNDING TYPES
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'fundings'], function () {

    /* BEGIN -- FUNDING TYPES */
    Route::get('types',[FundingTypeController::class, 'index'])->name('types.index')->middleware(['role:funding-masterdata,1']);

    Route::post('types',[FundingTypeController::class, 'store'])->name('types.store')->middleware(['role:funding-masterdata,2']);

    Route::get('types/{id}',[FundingTypeController::class, 'show'])->name('types.show')->middleware(['role:funding-masterdata,1']);

    Route::post('types/sorting', [FundingTypeController::class, 'sorting'])->name('types.sorting')->middleware(['role:funding-masterdata,3']);

    Route::post('types/massDelete',[FundingTypeController::class, 'massDelete'])->name('types.massDestroy')->middleware(['role:funding-masterdata,4']);

    Route::post('types/{id}',[FundingTypeController::class, 'update'])->name('types.update')->middleware(['role:funding-masterdata,3']);

    Route::delete('types/{id}',[FundingTypeController::class, 'destroy'])->name('types.destroy')->middleware(['role:funding-masterdata,4']);
    /* END -- FUNDING TYPES */

});

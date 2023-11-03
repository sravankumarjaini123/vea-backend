<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Fundings\FundingStateController;
/*
|--------------------------------------------------------------------------
| API Routes for MasterData - FUNDING STATES
|--------------------------------------------------------------------------
| CRUD operations for complete FUNDING STATES
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'fundings'], function () {

    /* BEGIN -- FUNDING STATES */
    Route::get('states',[FundingStateController::class, 'index'])->name('states.index')->middleware(['role:funding-masterdata,1']);

    Route::post('states',[FundingStateController::class, 'store'])->name('states.store')->middleware(['role:funding-masterdata,2']);

    Route::get('states/{id}',[FundingStateController::class, 'show'])->name('states.show')->middleware(['role:funding-masterdata,1']);

    Route::post('states/sorting', [FundingStateController::class, 'sorting'])->name('states.sorting')->middleware(['role:funding-masterdata,3']);

    Route::post('states/massDelete',[FundingStateController::class, 'massDelete'])->name('states.massDestroy')->middleware(['role:funding-masterdata,4']);

    Route::post('states/{id}',[FundingStateController::class, 'update'])->name('states.update')->middleware(['role:funding-masterdata,3']);

    Route::delete('states/{id}',[FundingStateController::class, 'destroy'])->name('states.destroy')->middleware(['role:funding-masterdata,4']);
    /* END -- FUNDING STATES */

});

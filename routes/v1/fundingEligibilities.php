<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Fundings\FundingEligibilityController;
/*
|--------------------------------------------------------------------------
| API Routes for MasterData - FUNDING ELIGIBILITIES
|--------------------------------------------------------------------------
| CRUD operations for complete FUNDING ELIGIBILITIES
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'fundings'], function () {

    /* BEGIN -- FUNDING ELIGIBILITIES */
    Route::get('eligibilities',[FundingEligibilityController::class, 'index'])->name('eligibilities.index')->middleware(['role:funding-masterdata,1']);

    Route::post('eligibilities',[FundingEligibilityController::class, 'store'])->name('eligibilities.store')->middleware(['role:funding-masterdata,2']);

    Route::get('eligibilities/{id}',[FundingEligibilityController::class, 'show'])->name('eligibilities.show')->middleware(['role:funding-masterdata,1']);

    Route::post('eligibilities/sorting', [FundingEligibilityController::class, 'sorting'])->name('eligibilities.sorting')->middleware(['role:funding-masterdata,3']);

    Route::post('eligibilities/massDelete',[FundingEligibilityController::class, 'massDelete'])->name('eligibilities.massDestroy')->middleware(['role:funding-masterdata,4']);

    Route::post('eligibilities/{id}',[FundingEligibilityController::class, 'update'])->name('eligibilities.update')->middleware(['role:funding-masterdata,3']);

    Route::delete('eligibilities/{id}',[FundingEligibilityController::class, 'destroy'])->name('eligibilities.destroy')->middleware(['role:funding-masterdata,4']);
    /* END -- FUNDING ELIGIBILITIES */

});

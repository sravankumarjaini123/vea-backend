<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Fundings\FundingRequirementController;
/*
|--------------------------------------------------------------------------
| API Routes for MasterData - FUNDING REQUIREMENTS
|--------------------------------------------------------------------------
| CRUD operations for complete FUNDING REQUIREMENTS
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'fundings'], function () {

    /* BEGIN -- FUNDING REQUIREMENTS */
    Route::get('requirements',[FundingRequirementController::class, 'index'])->name('requirements.index')->middleware(['role:funding-masterdata,1']);

    Route::post('requirements',[FundingRequirementController::class, 'store'])->name('requirements.store')->middleware(['role:funding-masterdata,2']);

    Route::get('requirements/{id}',[FundingRequirementController::class, 'show'])->name('requirements.show')->middleware(['role:funding-masterdata,1']);

    Route::post('requirements/sorting', [FundingRequirementController::class, 'sorting'])->name('requirements.sorting')->middleware(['role:funding-masterdata,3']);

    Route::post('requirements/massDelete',[FundingRequirementController::class, 'massDelete'])->name('requirements.massDestroy')->middleware(['role:funding-masterdata,4']);

    Route::post('requirements/{id}',[FundingRequirementController::class, 'update'])->name('requirements.update')->middleware(['role:funding-masterdata,3']);

    Route::delete('requirements/{id}',[FundingRequirementController::class, 'destroy'])->name('requirements.destroy')->middleware(['role:funding-masterdata,4']);
    /* END -- FUNDING REQUIREMENTS */

});

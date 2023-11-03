<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Fundings\FundingSubjectController;
/*
|--------------------------------------------------------------------------
| API Routes for MasterData - FUNDING SUBJECTS
|--------------------------------------------------------------------------
| CRUD operations for complete FUNDING SUBJECTS
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'fundings'], function () {

    /* BEGIN -- FUNDING subjects */
    Route::get('subjects',[FundingSubjectController::class, 'index'])->name('subjects.index')->middleware(['role:funding-masterdata,1']);

    Route::post('subjects',[FundingSubjectController::class, 'store'])->name('subjects.store')->middleware(['role:funding-masterdata,2']);

    Route::get('subjects/{id}',[FundingSubjectController::class, 'show'])->name('subjects.show')->middleware(['role:funding-masterdata,1']);

    Route::post('subjects/sorting', [FundingSubjectController::class, 'sorting'])->name('subjects.sorting')->middleware(['role:funding-masterdata,3']);

    Route::post('subjects/massDelete',[FundingSubjectController::class, 'massDelete'])->name('subjects.massDestroy')->middleware(['role:funding-masterdata,4']);

    Route::post('subjects/{id}',[FundingSubjectController::class, 'update'])->name('subjects.update')->middleware(['role:funding-masterdata,3']);

    Route::delete('subjects/{id}',[FundingSubjectController::class, 'destroy'])->name('subjects.destroy')->middleware(['role:funding-masterdata,4']);
    /* END -- FUNDING subjects */

});

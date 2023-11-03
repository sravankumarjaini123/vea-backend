<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Fundings\FundingBodyController;
/*
|--------------------------------------------------------------------------
| API Routes for MasterData - FUNDING BODIES
|--------------------------------------------------------------------------
| CRUD operations for complete FUNDING BODIES
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'fundings'], function () {

    /* BEGIN -- FUNDING BODIES */
    Route::get('bodies',[FundingBodyController::class, 'index'])->name('bodies.index')->middleware(['role:funding-masterdata,1']);

    Route::post('bodies',[FundingBodyController::class, 'store'])->name('bodies.store')->middleware(['role:funding-masterdata,2']);

    Route::get('bodies/{id}',[FundingBodyController::class, 'show'])->name('bodies.show')->middleware(['role:funding-masterdata,1']);

    Route::post('bodies/sorting', [FundingBodyController::class, 'sorting'])->name('bodies.sorting')->middleware(['role:funding-masterdata,3']);

    Route::post('bodies/massDelete',[FundingBodyController::class, 'massDelete'])->name('bodies.massDestroy')->middleware(['role:funding-masterdata,4']);

    Route::post('bodies/{id}',[FundingBodyController::class, 'update'])->name('bodies.update')->middleware(['role:funding-masterdata,3']);

    Route::delete('bodies/{id}',[FundingBodyController::class, 'destroy'])->name('bodies.destroy')->middleware(['role:funding-masterdata,4']);
    /* END -- FUNDING BODIES */

});

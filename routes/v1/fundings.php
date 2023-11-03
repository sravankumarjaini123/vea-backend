<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Fundings\FundingController;
/*
|--------------------------------------------------------------------------
| API Routes for MasterData - FUNDING BODIES
|--------------------------------------------------------------------------
| CRUD operations for complete FUNDING BODIES
| API's to connect the Master Data of the Funding and keep it sync.
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'fundings'], function () {

    /* BEGIN -- FUNDING BODIES */
    Route::get('',[FundingController::class, 'index'])->name('fundings.index')->middleware(['role:funding-masterdata,1']);

    Route::post('',[FundingController::class, 'store'])->name('fundings.store')->middleware(['role:funding-masterdata,2']);

    Route::get('{id}',[FundingController::class, 'show'])->name('fundings.show')->middleware(['role:funding-masterdata,1']);

    Route::post('massDelete',[FundingController::class, 'massDelete'])->name('fundings.massDestroy')->middleware(['role:funding-masterdata,4']);

    Route::post('{id}',[FundingController::class, 'update'])->name('fundings.update')->middleware(['role:funding-masterdata,3']);

    Route::delete('{id}',[FundingController::class, 'destroy'])->name('fundings.destroy')->middleware(['role:funding-masterdata,4']);
    /* END -- FUNDING BODIES */

    /* BEGIN -- MASTER DATA */

    /* END -- MASTER DATA */

});

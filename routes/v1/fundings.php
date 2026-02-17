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
    Route::post('filter',[FundingController::class, 'index'])->name('fundings.index')->middleware(['role:funding,1']);

    Route::post('',[FundingController::class, 'store'])->name('fundings.store')->middleware(['role:funding,2']);

    Route::get('retrieve',[FundingController::class, 'retrieve'])->name('fundings.retrieve')->middleware(['role:funding,1']);

    Route::get('{id}',[FundingController::class, 'show'])->name('fundings.show')->middleware(['role:funding,1']);

    Route::post('massDelete',[FundingController::class, 'massDelete'])->name('fundings.massDestroy')->middleware(['role:funding,4']);

    Route::post('massRestore',[FundingController::class, 'massRestore'])->name('fundings.massRestore')->middleware(['role:funding,3']);

    Route::post('massForceDelete',[FundingController::class, 'massForceDelete'])->name('fundings.massForceDelete')->middleware(['role:funding,4']);

    Route::post('{id}',[FundingController::class, 'update'])->name('fundings.updateGeneral')->middleware(['role:funding,3']);

    Route::post('masterData/{id}',[FundingController::class, 'updateMasterData'])->name('fundings.updateMasterData')->middleware(['role:funding,3']);


    /* END -- FUNDING BODIES */

    /* BEGIN -- POSTS - TRASH */
    Route::post('restore/{id}',[FundingController::class, 'restore'])->name('fundings.restore')->middleware(['role:funding,3']);

    Route::post('forceDelete/{id}',[FundingController::class, 'forceDelete'])->name('fundings.forceDelete')->middleware(['role:funding,4']);
    /* END -- POSTS - TRASH */

    Route::delete('{id}',[FundingController::class, 'destroy'])->name('fundings.destroy')->middleware(['role:funding,4']);

});

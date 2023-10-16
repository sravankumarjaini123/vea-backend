<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Sectors\SectorsController;
/*
|--------------------------------------------------------------------------
| API Routes for MasterData - SECTORS WITH GROUPS
|--------------------------------------------------------------------------
| CRUD Operations for the sectors with respective to the Groups
| Used for assigning to the Partners
|
*/
Route::group(['middleware' => ['auth:api']], function () {

    Route::get('sectors',[SectorsController::class, 'index'])->name('sectors.index')->middleware(['role:industries-and-sectors,1']);

    Route::post('sectors/groups',[SectorsController::class, 'storeGroups'])->name('sectors.groups.store')->middleware(['role:industries-and-sectors,2']);

    Route::post('sectors/{group_id}',[SectorsController::class, 'storeSectors'])->name('sectors.store')->middleware(['role:industries-and-sectors,2']);

    Route::post('sectors/groups/{group_id}',[SectorsController::class, 'updateGroups'])->name('sectors.groups.update')->middleware(['role:industries-and-sectors,3']);

    Route::post('sectors/update/{id}',[SectorsController::class, 'updateSectors'])->name('sectors.update')->middleware(['role:industries-and-sectors,3']);

    Route::delete('sectors/groups/{group_id}',[SectorsController::class, 'destroyGroups'])->name('sectors.groups.delete')->middleware(['role:industries-and-sectors,4']);

    Route::delete('sectors/{id}',[SectorsController::class, 'destroySectors'])->name('sectors.delete')->middleware(['role:industries-and-sectors,4']);

});

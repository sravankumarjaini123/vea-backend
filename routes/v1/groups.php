<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Groups\GroupController;

/*
|--------------------------------------------------------------------------
| API Routes for MasterData - GROUPS
|--------------------------------------------------------------------------
| CRUD operations for complete File management
| Folders and File are handled here
|
*/

Route::group(['middleware' => ['auth:api']], function () {

    /* BEGIN -- GROUPS */
    Route::get('groups',[GroupController::class, 'index'])->name('groups.index')->middleware(['role:groups,1']);

    Route::post('groups/filter',[GroupController::class, 'filter'])->name('groups.filter');

    Route::post('groups',[GroupController::class, 'store'])->name('groups.store')->middleware(['role:groups,2']);

    Route::get('groups/{id}',[GroupController::class, 'show'])->name('groups.show')->middleware(['role:groups,1']);

    Route::post('groups/sorting', [GroupController::class, 'sorting'])->name('groups.sorting')->middleware(['role:groups,3']);

    Route::post('groups/massDelete',[GroupController::class, 'massDelete'])->name('groups.massDestroy')->middleware(['role:groups,4']);

    Route::post('groups/{id}',[GroupController::class, 'update'])->name('groups.update')->middleware(['role:groups,3']);

    Route::delete('groups/{id}',[GroupController::class, 'destroy'])->name('groups.destroy')->middleware(['role:groups,4']);
    /* END -- GROUPS */

});

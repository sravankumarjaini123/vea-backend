<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Measures\MeasureCategoryController;
/*
|--------------------------------------------------------------------------
| API Routes for MasterData - Measures Categories
|--------------------------------------------------------------------------
| CRUD operations for complete Measures Categories
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'measures'], function () {

    /* BEGIN -- Measures Categories */
    Route::get('categories',[MeasureCategoryController::class, 'index'])->name('measureCategory.index')->middleware(['role:measure-masterdata,1']);

    Route::post('categories',[MeasureCategoryController::class, 'store'])->name('measureCategory.store')->middleware(['role:measure-masterdata,2']);

    Route::get('categories/{id}',[MeasureCategoryController::class, 'show'])->name('measureCategory.show')->middleware(['role:measure-masterdata,1']);

    Route::post('categories/sorting', [MeasureCategoryController::class, 'sorting'])->name('measureCategory.sorting')->middleware(['role:measure-masterdata,3']);

    Route::post('categories/massDelete',[MeasureCategoryController::class, 'massDelete'])->name('measureCategory.massDestroy')->middleware(['role:measure-masterdata,4']);

    Route::post('categories/{id}',[MeasureCategoryController::class, 'update'])->name('measureCategory.update')->middleware(['role:measure-masterdata,3']);

    Route::delete('categories/{id}',[MeasureCategoryController::class, 'destroy'])->name('measureCategory.destroy')->middleware(['role:measure-masterdata,4']);
    /* END -- Measures Categories */

});

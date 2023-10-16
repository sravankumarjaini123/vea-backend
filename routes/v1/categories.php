<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Categories\CategoryController;

/*
|--------------------------------------------------------------------------
| API Routes for MasterData - CATEGORIES
|--------------------------------------------------------------------------
| CRUD operations for complete Categories
|
*/

Route::group(['middleware' => ['auth:api']], function () {

    /* BEGIN -- CATEGORIES */
    Route::get('categories',[CategoryController::class, 'index'])->name('categories.index')->middleware(['role:categories,1']);

    Route::post('categories/filter',[CategoryController::class, 'filter'])->name('categories.filter');

    Route::post('categories',[CategoryController::class, 'store'])->name('categories.store')->middleware(['role:categories,2']);

    Route::get('categories/{id}',[CategoryController::class, 'show'])->name('categories.show')->middleware(['role:categories,1']);

    Route::post('categories/sorting', [CategoryController::class, 'sorting'])->name('categories.sorting')->middleware(['role:categories,3']);

    Route::post('categories/massDelete',[CategoryController::class, 'massDelete'])->name('categories.massDestroy')->middleware(['role:categories,4']);

    Route::post('categories/{id}',[CategoryController::class, 'update'])->name('categories.update')->middleware(['role:categories,3']);

    Route::delete('categories/{id}',[CategoryController::class, 'destroy'])->name('categories.destroy')->middleware(['role:categories,4']);
    /* END -- CATEGORIES */

});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Authors\AuthorsController;

/*
|--------------------------------------------------------------------------
| API Routes for - AUTHORS
|--------------------------------------------------------------------------
| CRUD operations for complete Authors will be handled here
|
*/

Route::group(['middleware' => ['auth:api']], function () {

    Route::get('authors',[AuthorsController::class, 'index'])->name('authors.index')->middleware(['role:authors,1']);

    Route::get('authors/active',[AuthorsController::class, 'getActiveAuthors'])->name('authors.active.index');

    Route::post('authors/filter',[AuthorsController::class, 'filter'])->name('authors.filter');

    Route::post('authors',[AuthorsController::class, 'store'])->name('authors.store')->middleware(['role:authors,2']);

    Route::post('authors/sorting', [AuthorsController::class, 'sorting'])->name('authors.sorting')->middleware(['role:authors,3']);

    Route::post('authors/massDelete',[AuthorsController::class, 'massDelete'])->name('authors.massDestroy')->middleware(['role:authors,4']);

    Route::post('authors/status/{id}',[AuthorsController::class, 'updateStatus'])->name('authors.updateStatus')->middleware(['role:authors,3']);

    Route::post('authors/general/{id}',[AuthorsController::class, 'updateGeneral'])->name('authors.updateGeneral')->middleware(['role:authors,3']);

    Route::post('authors/description/{id}',[AuthorsController::class, 'updateDescription'])->name('authors.updateDescription')->middleware(['role:authors,3']);

    Route::post('authors/picture/{id}',[AuthorsController::class, 'updatePicture'])->name('authors.updatePicture')->middleware(['role:authors,3']);

    Route::delete('authors/{id}',[AuthorsController::class, 'destroy'])->name('authors.destroy')->middleware(['role:authors,4']);

});

Route::get('authors/{id}',[AuthorsController::class, 'show'])->name('authors.show');

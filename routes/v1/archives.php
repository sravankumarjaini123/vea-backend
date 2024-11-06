<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Videos\ArchivesController;
/*
|--------------------------------------------------------------------------
| API Routes for External Videos
|--------------------------------------------------------------------------
| CRUD operations for complete archives
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'archives'], function () {

    /* BEGIN -- External Videos */
    Route::get('',[ArchivesController::class, 'index'])->name('archives.index')->middleware(['role:archives,1']);

    Route::post('',[ArchivesController::class, 'store'])->name('archives.store')->middleware(['role:archives,2']);

    Route::get('{id}',[ArchivesController::class, 'show'])->name('archives.show')->middleware(['role:archives,1']);

    Route::post('massDelete',[ArchivesController::class, 'massDelete'])->name('archives.massDestroy')->middleware(['role:archives,4']);

    Route::post('{id}',[ArchivesController::class, 'update'])->name('archives.update')->middleware(['role:archives,3']);

    Route::delete('{id}',[ArchivesController::class, 'destroy'])->name('archives.destroy')->middleware(['role:archives,4']);
    /* END -- External Videos */

});

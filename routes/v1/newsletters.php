<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Newsletters\NewslettersController;

    Route::group(['middleware' => ['auth:api']], function () {
        Route::group(['prefix' => 'newsletters/interests'], function (){

            /* BEGIN -- NEWSLETTERS INTERESTS */

            Route::get('',[NewslettersController::class, 'index'])->name('newsletters.interests.index')->middleware(['role:newsletters,1']);

            Route::post('',[NewslettersController::class, 'store'])->name('newsletters.interests.store')->middleware(['role:newsletters,2']);

            Route::post('filter',[NewslettersController::class, 'filter'])->name('newsletters.interests.filter');

            Route::get('{id}',[NewslettersController::class, 'show'])->name('newsletters.interests.show')->middleware(['role:newsletters,1']);

            Route::post('sorting', [NewslettersController::class, 'sorting'])->name('newsletters.interests.sorting')->middleware(['role:newsletters,3']);

            Route::post('massDelete',[NewslettersController::class, 'massDelete'])->name('newsletters.interests.massDestroy')->middleware(['role:newsletters,4']);

            Route::post('{id}',[NewslettersController::class, 'update'])->name('newsletters.interests.update')->middleware(['role:newsletters,3']);

            Route::delete('{id}',[NewslettersController::class, 'destroy'])->name('newsletters.interests.destroy')->middleware(['role:newsletters,4']);
            /* END -- NEWSLETTERS INTERESTS */
        });
    });

    Route::group(['prefix' => 'newsletters'], function() {
        /* BEGIN -- NEWSLETTERS USERS */
        Route::post('users/index', [NewslettersController::class, 'usersIndex'])->name('newsletters.users.index');

        Route::post('subscribe/{id}', [NewslettersController::class, 'usersStore'])->name('newsletters.users.store');

        /* END -- NEWSLETTERS USERS*/
    });

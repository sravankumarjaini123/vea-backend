<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Partners\PartnersController;

/*
|--------------------------------------------------------------------------
| API Routes for COMPANIES
|--------------------------------------------------------------------------
| CRUD operations for complete companies
| Invitation concept of the company registrationn.
|
*/

Route::group(['middleware' => ['auth:api']], function () {

    Route::get('partners', [PartnersController::class, 'index'])->name('partners.index')->middleware(['role:partners,1']);

    Route::post('partners',[PartnersController::class, 'store'])->name('partners.store')->middleware(['role:partners,2']);

    Route::get('partners/random',[PartnersController::class, 'randomPartners'])->name('partners.random')->middleware(['role:partners,1']);

    Route::get('partners/groupBySectors',[PartnersController::class, 'indexGroupBySectorsIndex'])->name('partners.groupBy')->middleware(['role:partners,1']);

    Route::get('partners/{id}',[PartnersController::class, 'show'])->name('partners.show')->middleware(['role:partners,1']);

    Route::get('partners/contacts/{id}', [PartnersController::class, 'getContacts'])->name('partners.contacts')->middleware(['role:partners,1']);

    Route::group(['prefix' => 'partners'], function () {

        Route::post('filter', [PartnersController::class, 'getFilterPartners'])->name('partners.filter')->middleware(['role:partners,1']);

        // Route::post('import', [PartnersController::class, 'importProductionPartners'])->name('partners.filter')->middleware(['role:partners,1']);

        Route::post('importResource', [PartnersController::class, 'importPartnersResources'])->name('partners.filter')->middleware(['role:partners,1']);

        Route::post('general/{id}', [PartnersController::class, 'updateGeneral'])->name('partners.general')->middleware(['role:partners,3']);

        Route::post('logos/{id}', [PartnersController::class, 'storeLogos'])->name('partners.logos')->middleware(['role:partners,3']);

        Route::post('logos/delete/{id}', [PartnersController::class, 'deleteLogos'])->name('partners.logos.delete')->middleware(['role:partners,3']);

        Route::post('finance/{id}', [PartnersController::class, 'updateFinance'])->name('partners.finance')->middleware(['role:partners,3']);

        Route::post('notes/{id}', [PartnersController::class, 'updateNotes'])->name('partners.notes')->middleware(['role:partners,3']);

        Route::post('resources/{id}', [PartnersController::class, 'updateResources'])->name('partners.resources')->middleware(['role:partners,3']);

        /* BEGIN - MASTER DATA */

        Route::post('labels/{id}', [PartnersController::class, 'updateLabels'])->name('partners.updateLabels')->middleware(['role:partners,3']);

        Route::post('industries/sectors/{id}', [PartnersController::class, 'updateIndustriesSectors'])->name('partners.updateIndustriesSectors')->middleware(['role:partners,3']);

        /* END - MASTER DATA */

    });

    Route::post('partners/massDelete',[PartnersController::class, 'massDelete'])->name('partners.massDestroy')->middleware(['role:partners,4']);

    Route::delete('partners/{id}',[PartnersController::class, 'destroy'])->name('partners.destroy')->middleware(['role:partners,4']);

});


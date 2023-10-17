<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LegalTexts\LegalTextController;

/*
|--------------------------------------------------------------------------
| API Routes for Legal texts
|--------------------------------------------------------------------------
| CRUD operations for legal texts
| Trash option => can retrieve the records and restore the records as per our needs
|
*/

Route::group(['middleware' => ['auth:api']], function () {

    /* BEGIN -- LEGAL TEXTS */
    Route::get('legalTexts',[LegalTextController::class, 'index'])->name('legalTexts.index')->middleware(['role:legal-texts,1']);

    Route::get('legalTexts/retrieve',[LegalTextController::class, 'retrieve'])->name('legalTexts.retrieve')->middleware(['role:legal-texts,1']);

    Route::post('legalTexts',[LegalTextController::class, 'store'])->name('legalTexts.store')->middleware(['role:legal-texts,2']);

    Route::post('legalTexts/massDelete',[LegalTextController::class, 'massDelete'])->name('legalTexts.massDelete')->middleware(['role:legal-texts,4']);

    Route::post('legalTexts/massRestore',[LegalTextController::class, 'massRestore'])->name('legalTexts.massRestore')->middleware(['role:legal-texts,3']);

    Route::get('legalTexts/settings',[LegalTextController::class, 'getSettings'])->name('legalTexts.get.settings')->middleware(['role:legal-texts,3']);

    Route::get('legalTexts/active',[LegalTextController::class, 'showActive'])->name('legalTexts.show.active')->middleware(['role:legal-texts,1']);

    Route::get('legalTexts/{version_id}',[LegalTextController::class, 'show'])->name('legalTexts.show')->middleware(['role:legal-texts,1']);

    Route::get('legalTexts/{version_id}/{id}',[LegalTextController::class, 'showDetail'])->name('legalTexts.show.Detail')->middleware(['role:legal-texts,1']);

    Route::post('legalTexts/{version_id}',[LegalTextController::class, 'update'])->name('legalTexts.update')->middleware(['role:legal-texts,3']);

    Route::post('legalTexts/settings/create',[LegalTextController::class, 'addSettings'])->name('legalTexts.add.settings')->middleware(['role:legal-texts,3']);

    Route::post('legalTexts/settings/delete/{setting_id}',[LegalTextController::class, 'deleteSettings'])->name('legalTexts.add.settings')->middleware(['role:legal-texts,3']);

    Route::delete('legalTexts/{version_id}',[LegalTextController::class, 'destroy'])->name('legalTexts.destroy')->middleware(['role:legal-texts,4']);

    Route::post('legalTexts/restore/{version_id}',[LegalTextController::class, 'restore'])->name('legalTexts.restore')->middleware(['role:legal-texts,3']);
    /* END -- LEGAL TEXTS */

});

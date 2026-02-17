<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Emails\EmailTemplateController;
use App\Http\Controllers\Emails\EmailController;
use App\Http\Controllers\Emails\EmailSettingsController;
/*
|--------------------------------------------------------------------------
| API Routes for Emails Templates
|--------------------------------------------------------------------------
| CRUD operations for email templates
|
*/

Route::group(['middleware' => ['auth:api'], 'prefix' => 'emails'], function () {

    Route::get('existing/templates', [EmailTemplateController::class, 'emailTemplateSampleIndex'])->middleware(['role:emails,1']);

    Route::post('templates/add', [EmailTemplateController::class, 'storeSampleEmailTemplates'])->middleware(['role:emails,1']);

    Route::get('templates', [EmailTemplateController::class, 'index'])->name('emails.templates.index')->middleware(['role:emails,1']);

    Route::get('', [EmailController::class, 'indexEmails'])->name('emails.indexx')->middleware(['role:emails,1']);

    Route::get('settings', [EmailSettingsController::class, 'indexSettings'])->name('emails.index')->middleware(['role:emails,1']);

    Route::get('{id}', [EmailController::class, 'showEmails'])->name('emails.show')->middleware(['role:emails,1']);

    Route::post('', [EmailController::class, 'storeEmails'])->name('emails.store')->middleware(['role:emails,2']);

    Route::post('test', [EmailController::class, 'testSMTPConnection'])->name('emails.test');

    Route::post('templates', [EmailTemplateController::class, 'store'])->name('emails.templates.store')->middleware(['role:emails,2']);

    Route::get('templates/{id}', [EmailTemplateController::class, 'show'])->name('emails.templates.show')->middleware(['role:emails,1']);

    Route::post('update/settings', [EmailSettingsController::class, 'updateSettings'])->name('emails.settings.update')->middleware(['role:emails,3']);

    Route::post('templates/massDelete', [EmailTemplateController::class, 'massDelete'])->name('emails.templates.massDestroy')->middleware(['role:emails,4']);

    Route::post('massDelete', [EmailController::class, 'massDelete'])->name('emails.massDestroy')->middleware(['role:emails,4']);

    Route::post('{id}', [EmailController::class, 'updateEmails'])->name('emails.update')->middleware(['role:emails,3']);

    Route::post('templates/{id}', [EmailTemplateController::class, 'update'])->name('emails.templates.update')->middleware(['role:emails,3']);

    Route::delete('{id}', [EmailController::class, 'destroy'])->name('emails.destroy')->middleware(['role:emails,4']);

    Route::delete('templates/{id}', [EmailTemplateController::class, 'destroy'])->name('emails.templates.destroy')->middleware(['role:emails,4']);
});

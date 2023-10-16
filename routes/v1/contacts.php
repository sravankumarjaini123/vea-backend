<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Contacts\ContactController;
use App\Http\Controllers\Contacts\AuthenticateController;
use App\Http\Controllers\Contacts\LoginController;
use App\Http\Controllers\Users\UserController;

/*
|--------------------------------------------------------------------------
| API Routes for Customer
|--------------------------------------------------------------------------
| The user - Registration, Login, Update the details of customer
| Password confirmation of user
| Address, Comments, Grouping, Roles & Permissions
|
*/

Route::group(['middleware' =>  config('fortify.middleware', ['web']),'prefix' => 'contact'], function () {

    Route::post('register',[AuthenticateController::class, 'register'])->name('contact.register');

    Route::post('register/app',[AuthenticateController::class, 'register'])->name('contact.register');

//    Route::post('login',[LoginController::class, 'login'])->name('contact.login');

});

Route::group(['prefix' => 'contact'],function(){

    Route::get('logout', [AuthenticateController::class, 'destroy'])->name('contact.logout')->middleware('auth:api');

    /* BEGIN -- PERSONAL ADDRESS */
    Route::group(['prefix' =>'personalAddress'],function () {

        Route::post('create/{id}', [ContactController::class, 'storePersonal'])->name('contact.personal.store')->middleware(['role:contacts,3']);

        Route::post('delete/{id}', [ContactController::class, 'deletePersonal'])->name('contact.personal.delete')->middleware(['role:contacts,3']);
    });
    /* END -- PERSONAL ADDRESS */

    /* BEGIN -- COMPANY ADDRESS */
    Route::group(['prefix' =>'companyAddress'],function () {

        Route::post('create/{id}', [ContactController::class, 'storeCompany'])->name('contact.company.store')->middleware(['role:contacts,3']);

//         Route::post('update/{id}', [ContactController::class, 'updateCompany'])->name('contact.company.update')->middleware(['role:contacts,3']);

        Route::post('delete/{id}', [ContactController::class, 'deleteCompany'])->name('contact.company.delete')->middleware(['role:contacts,3']);

        Route::post('add/{id}', [ContactController::class, 'addAndAttachCompanyToContact'])->name('contact.company.add')->middleware(['role:contacts,3']);

        Route::post('update/{id}', [ContactController::class, 'updateAttachedCompany'])->name('contact.company.update')->middleware(['role:contacts,3']);

        Route::post('remove/{id}', [ContactController::class, 'removeAttachedCompany'])->name('contact.company.delete')->middleware(['role:contacts,3']);

    });
    /* END -- COMPANY ADDRESS */

});


/* AUTHENTICATION */
Route::group(['middleware' => ['auth:api']], function () {

    Route::group(['prefix' =>'contact'],function () {

        Route::get('getLoginsList/{id}', [UserController::class, 'contactsLoginsList'])->name('contact.loginlist')->middleware(['role:contacts,3']);

        Route::post('changeEmail/{id}', [UserController::class, 'changeEmail'])->name('contact.change-email')->middleware(['role:contacts,3']);

        Route::post('updateEmail/{id}', [UserController::class, 'updateEmail'])->name('contact.update-email')->middleware(['role:contacts,3']);

        Route::post('updatePassword/{id}', [UserController::class, 'updatePassword'])->name('contact.update-password')->middleware(['role:contacts,3']);

        Route::post('labels/{id}', [UserController::class, 'updateLabels'])->name('contact.update.labels')->middleware(['role:contacts,3']);

        Route::post('state/{id}', [ContactController::class, 'updateState'])->name('contact.update.block')->middleware(['role:contacts,1']);

        Route::post('verification/email/{id}', [ContactController::class, 'emailWithCodeConfirmation'])->name('contact.update.block')->middleware(['role:contacts,1']);

    });

    /* BEGIN -- CUSTOMER */
    Route::get('contacts', [ContactController::class, 'index'])->name('contact.index')->middleware(['role:contacts,1']);

    Route::post('contacts/filter', [ContactController::class, 'getFilterContacts'])->name('contact.filter')->middleware(['role:contacts,1']);

    Route::group(['prefix' =>'contact'],function () {

        Route::get('deleteContactProfilePhoto/{id}', [ContactController::class, 'deleteContactPhoto'])->name('contact.deletephoto');

        Route::get('profile', [ContactController::class, 'contactProfile']);

        Route::get('{id}', [ContactController::class, 'show'])->name('contact.show')->middleware(['role:contacts,1']);

        Route::post('personal/{id}', [UserController::class, 'updatePersonal'])->name('contacts.update')->middleware(['role:contacts,3']);

        Route::post('profilePicture/{id}', [ContactController::class, 'updateProfilePicture'])->name('contacts.update.profilePicture')->middleware(['role:contacts,3']);

        /* BEGIN -- Double OptIn */
        Route::post('doubleOptIn/email/{id}', [AuthenticateController::class, 'doubleOptInConfirmationMail'])->name('contact.doubleOptIn.confirmation');
        /* END -- Double OptIn */

    });

    /* END -- CUSTOMER */
});

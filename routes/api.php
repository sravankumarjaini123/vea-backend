<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\TokensController;
use App\Http\Controllers\Client\SalutationController;
use App\Http\Controllers\Client\TitleController;
use App\Http\Controllers\Users\AuthenticationController;
use App\Http\Controllers\Client\CountryController;
use App\Http\Controllers\Client\LanguageController;
use App\Http\Controllers\LegalTexts\LegalTextController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::group(['middleware' => 'log.entry'], function () {

    Route::prefix('v1')->group(function () {

        require __DIR__ . '/v1/users.php';

        require __DIR__ . '/v1/rolesAndPermissions.php';

        require __DIR__ . '/v1/partners.php';

        require __DIR__ . '/v1/emails.php';

        require __DIR__ . '/v1/labels.php';

        require __DIR__ . '/v1/sectors.php';

        require __DIR__ . '/v1/contacts.php';

        require __DIR__ . '/v1/newsletters.php';

        require __DIR__ . '/v1/categories.php';

        require __DIR__ . '/v1/groups.php';

        require __DIR__ . '/v1/tags.php';

        require __DIR__ . '/v1/legalTexts.php';

        require __DIR__ . '/v1/fileManagement.php';

        require __DIR__ . '/v1/authors.php';

        require __DIR__ . '/v1/system.php';

        require __DIR__ . '/v1/wordpress.php';

        require __DIR__ . '/v1/linkedIn.php';

        require __DIR__ . '/v1/twitter.php';

        require __DIR__ . '/v1/posts.php';

        require __DIR__ . '/v1/fundingStates.php';

        require __DIR__ . '/v1/fundingRequirements.php';

        require __DIR__ . '/v1/fundingBodies.php';

        require __DIR__ . '/v1/fundingEligibilities.php';

        require __DIR__ . '/v1/fundingTypes.php';

        require __DIR__ . '/v1/fundingSubjects.php';

        require __DIR__ . '/v1/fundings.php';

        require __DIR__ . '/v1/measuresCategories.php';

        require __DIR__ . '/v1/measuresProcessors.php';

        require __DIR__ . '/v1/measuresTypes.php';

        require __DIR__ . '/v1/measuresEnergySources.php';

        require __DIR__ . '/v1/measures.php';

    });
});

Route::group(['middleware' => config('fortify.middleware', ['web']),'prefix' => 'v1/client'], function () {

    Route::get('token', [TokensController::class, 'index'])->name('api.client.token');

    Route::get('countries', [CountryController::class , 'index'])->name('api.countries.index');

    Route::get('salutations',[SalutationController::class, 'index'])->name('api.salutations.index');

    Route::get('titles',[TitleController::class, 'index'])->name('api.titles.index');

    Route::get('v1/client/languages', [LanguageController::class, 'index']);

    Route::get('system/legalTexts/{id}', [LegalTextController::class, 'showDetailWithId'])->name('legalTexts.show.Detail.id');

    Route::post('refreshToken', [AuthenticationController::class, 'issueToken'])->name('api.client.refreshToken');


});

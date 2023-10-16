<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\TokensController;
use App\Http\Controllers\Client\SalutationController;
use App\Http\Controllers\Client\TitleController;
use App\Http\Controllers\Users\AuthenticationController;
use App\Http\Controllers\Client\CountryController;
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

Route::prefix('v1')->group(function () {

    require __DIR__ . '/v1/users.php';

    require __DIR__ . '/v1/rolesAndPermissions.php';

    require __DIR__ . '/v1/partners.php';

    require __DIR__ . '/v1/emails.php';

    require __DIR__ . '/v1/labels.php';

    require __DIR__ . '/v1/sectors.php';

    require  __DIR__ . '/v1/contacts.php';

    require __DIR__ . '/v1/newsletters.php';

});

Route::group(['middleware' => config('fortify.middleware', ['web']),'prefix' => 'v1/client'], function () {

    Route::get('token', [TokensController::class, 'index'])->name('api.client.token');

    Route::get('countries', [CountryController::class , 'index'])->name('api.countries.index');

    Route::get('salutations',[SalutationController::class, 'index'])->name('api.salutations.index');

    Route::get('titles',[TitleController::class, 'index'])->name('api.titles.index');

    Route::post('refreshToken', [AuthenticationController::class, 'issueToken'])->name('api.client.refreshToken');

});

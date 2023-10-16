<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolesPermissions\RoleController;
use App\Http\Controllers\RolesPermissions\PermissionController;
use App\Http\Controllers\RolesPermissions\ResourceController;
use App\Http\Controllers\RolesPermissions\RolesPermissionsController;

/*
|--------------------------------------------------------------------------
| API Routes for User - Roles and Permissions
|--------------------------------------------------------------------------
|
*/

Route::group(['middleware' => ['auth:api']], function () {

    Route::group(['prefix' => 'roles'], function (){

        Route::get('', [RoleController::class, 'index'])->name('roles.index')->middleware(['role:roles-and-permissions,1']);

        Route::post('', [RoleController::class, 'store'])->name('roles.store')->middleware(['role:roles-and-permissions,2']);

        Route::get('{id}', [RoleController::class, 'show'])->name('roles.show');

        Route::post('{id}', [RoleController::class, 'update'])->name('roles.update')->middleware(['role:roles-and-permissions,3']);

        Route::post('resources/{id}', [RoleController::class, 'updateRolesResources'])->name('roles.resources.update')->middleware(['role:roles-and-permissions,3']);

        Route::delete('{id}', [RoleController::class, 'destroy'])->name('roles.destroy')->middleware(['role:roles-and-permissions,4']);
    });

    Route::group(['prefix' => 'permissions'], function () {

        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');

    });

    Route::group(['prefix' => 'resources'], function () {

        Route::get('', [ResourceController::class, 'index'])->name('resources.index');

    });
});

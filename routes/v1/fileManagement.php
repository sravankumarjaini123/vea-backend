<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileManagement\FileManagementController;

/*
|--------------------------------------------------------------------------
| API Routes for FILE MANAGEMENT
|--------------------------------------------------------------------------
| CRUD operations for complete File management
| Folders and File are handled here
|
*/

Route::group(['middleware' => ['auth:api']], function () {

    Route::post('mediaManager/downloadZip', [FileManagementController::class, 'downloadZipFile'])->name('media.downloadZipFile');

    Route::get('mediaManager/folders', [FileManagementController::class, 'getFolders'])->name('media.getFolders')->middleware(['role:file-manager,1']);

    Route::get('mediaManager',[FileManagementController::class, 'index'])->name('media.index')->middleware(['role:file-manager,1']);

    Route::post('mediaManager/folder/{parent_id?}',[FileManagementController::class, 'storeFolder'])->name('media.folder.store')->middleware(['role:file-manager,2']);

    Route::post('mediaManager/files/{folder_id?}',[FileManagementController::class, 'storeFile'])->name('media.file.store')->middleware(['role:file-manager,2']);

    Route::get('mediaManager/{id}',[FileManagementController::class, 'show'])->name('api.media.show')->middleware(['role:file-manager,1']);

    Route::post('mediaManager/renameFolder/{id}/{parent_id?}', [FileManagementController::class, 'updateFolder'])->name('media.folder.update')->middleware(['role:file-manager,3']);

    Route::post('mediaManager/renameFile/{id}/{folder_id?}',[FileManagementController::class, 'updateFile'])->name('media.file.update')->middleware(['role:file-manager,3']);

    Route::post('mediaManager/optimizeFiles',[FileManagementController::class, 'optimizeFiles'])->name('media.file.optimize')->middleware(['role:file-manager,3']);

    Route::delete('mediaManager/folder/{id}', [FileManagementController::class,'destroyFolder'])->name('media.folder.delete')->middleware(['role:file-manager,4']);

    Route::delete('mediaManager/file/{id}', [FileManagementController::class,'destroyFile'])->name('media.file.delete')->middleware(['role:file-manager,4']);

    Route::post('mediaManager/massDelete',[FileManagementController::class, 'massDelete'])->name('media.massDelete')->middleware(['role:file-manager,4']);

    Route::post('mediaManager/move',[FileManagementController::class, 'moveFilesFolders'])->name('media.move.filesFolders')->middleware(['role:file-manager,3']);

    Route::get('mediaManager/getFile/{file_id}', [FileManagementController::class, 'getFile'])->name('media.getFile')->middleware(['role:file-manager,1']);

});

Route::get('mediaManager/downloadFile/{id}',[FileManagementController::class, 'downloadFile'])->name('media.file.download');


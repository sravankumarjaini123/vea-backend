<?php

namespace App\Http\Controllers\FileManagement;

use App\Http\Controllers\Controller;
use App\Jobs\ImageOptimization;
use App\Models\Folders;
use App\Models\FoldersFiles;
use App\Models\Notifications;
use App\Models\User;
use App\Models\Wordpress;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Nette\Schema\ValidationException;
use DateTime;

class FileManagementController extends Controller
{
    /**
     * Method allow to display list of all media at root level.
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $folders = DB::table('folders')
                ->whereNull('parents_id')
                ->orderBy('name')
                ->get();

            $files_db = DB::table('folders_files')
                ->whereNull('folders_id')
                ->whereNull('store_type')
                ->orderBy('name')
                ->get();
            $files = $this->getFileDetails($files_db);
            $total_size = FoldersFiles::whereNull('store_type')->sum('size');
            $items = FoldersFiles::whereNull('store_type')->get();
            $items_count = $items->count();

            return response()->json([
                'total_size' => $total_size,
                'total_items' => $items_count,
                'folders' => $folders,
                'files' => $files,
            ]);

        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to store or create the new Folder.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function storeFolder(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string'
            ]);
            if ($request->parent_id != null){
                if (DB::table('folders')->where('id',$request->parent_id)->exists()){
                    $createFolder = $this->createFolder($request->name, $request->parent_id);
                } else {
                    return response()->json([
                        'Status' => 'Error',
                        'message' => 'There is no relevant information for selected query'
                    ], 210);
                }
            } else {
                $createFolder = $this->createFolder($request->name);
            }
            if ($createFolder == 'success'){
                return response()->json([
                    'Status' => 'Success',
                    'message' => 'Folder is created successfully'
                ], 200);
            } elseif ($createFolder == 'name_exists') {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'The Folder with name already exists'
                ], 422);
            } else {
                return response()->json([
                    'Status' => 'Error',
                    'message' => 'There is an error in creating the folder, Please try again after some time!'
                ], 400);
            }
        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to store or create the new Folder.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function  storeFile(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'medias' => 'required',
                'medias.*' => 'mimes:jpg,jpeg,png,pdf,mp4,mov,avi,aac,mp3,wav'
            ]);
            $medias = $request->file('medias');
            $medias_duration = $request->medias_duration;

            if ($request->folder_id == null )
            {
                $createFile = $this->createFile($medias, $medias_duration, $request->notified_by);
            } else {
                if (DB::table('folders')->where('id',$request->folder_id)->exists()){
                    $createFile = $this->createFile($medias, $medias_duration, $request->notified_by, $request->folder_id);
                } else {
                    return response()->json([
                        'Status' => 'Error',
                        'message' => 'There is no relevant information for selected query'
                    ], 210);
                }
            }
            if ($createFile == 'success') {
                return response()->json([
                    'Status' => 'Success',
                    'message' => 'File(s) are uploaded successfully'
                ], 200);
            } elseif ($createFile == 'name_exists'){
                return response()->json([
                    'Status' => 'Error',
                    'message' => 'One or more more files trying to upload with name already existing'
                ], 422);
            } else {
                return response()->json([
                    'Status' => 'Error',
                    'message' => $createFile
                ], 400);
            }

        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Helper Method allow to store or create the new Folder into the database.
     * @param $name
     * @param null $id
     * @return string
     * @throws Exception
     */
    public function createFolder($name, $id = null):string
    {
        try {
            $result = '';
            $name_exists = DB::table('folders')
                ->where('parents_id','=',$id)
                ->where('name','=',$name)
                ->exists();
            if (!$name_exists){
                $folder = DB::table('folders')->insert([
                    'parents_id' => $id,
                    'name' => $name,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
                $result = 'success';
            } else {
                $result = 'name_exists';
            }
            return $result;
        } catch (Exception $exception)
        {
            return $result = 'exception';
        }
    } // End Function

    /**
     * Helper Method allow to store or create the new Folder into the database.
     * @param $medias
     * @param null $id
     * @return string
     * @throws Exception
     */
    public function createFile($medias, $medias_duration, $users_id, $id = null):string
    {
        try {
            $files = Storage::disk('media')->files();
            /*foreach ($files as $file){
                File::move(storage_path('app/public/media/'.$file), storage_path().'/volume/mnt/'.env('DISK_VOLUME').'/media/'.$file);
            }*/
            $url = URL::to('/');
            File::delete(public_path('storage'));
            if (env('DISK_DRIVER') === 'mounted'){
                Config::set('filesystems.links.'.public_path('storage'), storage_path().'/volume/mnt/'.env('DISK_VOLUME'));
                symlink(storage_path().'/volume/mnt/'.env('DISK_VOLUME'), public_path('storage'));
                // Artisan::call('storage:link');
                $destination_path = '';
            } else {
                Artisan::call('storage:link');
                $destination_path = 'public/media';
            }
            $count_medias = count($medias);
            $num = 0;
            foreach ($medias as $media)
            {
                $name = $media->getClientOriginalName();
                $filename = pathinfo($name, PATHINFO_FILENAME);
                $type = $media->extension();
                $name_exists = DB::table('folders_files')
                    ->where('folders_id','=',$id)
                    ->where('name','=',$filename)
                    ->where('type','=', $type)
                    ->exists();
                if (!$name_exists){
                    $num++;
                }
            }
            if ($num == $count_medias){
                foreach ($medias as $key => $media)
                {
                    if ($medias_duration != null){
                        $duration = (int)$medias_duration[$key];
                    } else {
                        $duration = null;
                    }
                    $name = $media->getClientOriginalName();
                    $filename = pathinfo($name, PATHINFO_FILENAME);
                    $size = $media->getSize();
                    $type = $media->extension();
                    $hash_name = $media->hashName();
                    $file_path = $url. '/storage/media/' .$hash_name;
                    if (env('DISK_DRIVER') === 'mounted'){
                        $path = $media->storeAs($destination_path, $hash_name, 'volume');
                    } else {
                        $path = $media->storeAs($destination_path, $hash_name);
                    }
                    $file_id = DB::table('folders_files')->insertGetId([
                        'folders_id' => $id,
                        'name' => $filename,
                        'size' => $size,
                        'type' => $type,
                        'hash_name' => $hash_name,
                        'file_path' => $file_path,
                        'duration' => $duration,
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                    $file_details = FoldersFiles::where('id', $file_id)->first();
                    if (!empty($media->duration)){
                        $file_details->duration = $media->duration;
                        $file_details->save();
                    }
                    // Optimization of the files if it is the image.
                    if ($type === 'jpg' || $type === 'jpeg' || $type === 'png'){
                        $request = new Request();
                        $request->setMethod('post');
                        $request->request->add([
                            'notified_by' => $users_id,
                            'files_id' => [$file_id]
                        ]);
                        $this->optimizeFiles($request);
                    }
                }
                $result = 'success';
            } else {
                $result = 'name_exists';
            }
            return $result;
        } catch (Exception $exception)
        {
            return $exception->getMessage();
        }
    } // End Function

    /**
     * Method allow to update the name of the particular Folder.
     * @param Request $request
     * @param $id
     * @param null $parent_id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateFolder(Request $request, $id):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string'
            ]);

            if ($request->parent_id == null){
                $name_exists = DB::table('folders')
                    ->whereNull('parents_id')
                    ->where('name','=',$request->name)
                    ->exists();
                if (!$name_exists){
                    if (Folders::where('id',$id)->exists()){
                        Folders::where('id',$id)->update(['name' => $request->name]);
                        return response()->json([
                            'Status' => 'Success',
                            'message' => 'Folder name is changed successfully'
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 'No Content',
                            'message' => 'There is no relevant information for selected query'
                        ],210);
                    }
                } else{
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'The Folder with name already exists'
                    ], 400);
                }
            } else {
                $name_exists = DB::table('folders')
                    ->where('parents_id','=',$request->parent_id)
                    ->where('name','=',$request->name)
                    ->exists();
                if (!$name_exists){
                    if (Folders::where('id',$id)->exists()){
                        Folders::where('id',$id)->update(['name' => $request->name]);
                        return response()->json([
                            'Status' => 'Success',
                            'message' => 'Folder name is changed successfully'
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 'No Content',
                            'message' => 'There is no relevant information for selected query'
                        ],210);
                    }
                } else{
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'The Folder with name already exists'
                    ], 400);
                }
            }
        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to update the name of the particular Folder.
     * @param Request $request
     * @param $id
     * @param $folder_id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateFile(Request $request, $id):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string'
            ]);
            if ($request->folder_id == null)
            {
                $name_exists = DB::table('folders_files')
                    ->whereNull('folders_id')
                    ->where('id','!=',$id)
                    ->where('name','=',$request->name)
                    ->exists();
                if (!$name_exists)
                {
                    if (FoldersFiles::where('id',$id)->exists()){
                        FoldersFiles::where('id',$id)->update([
                            'name' => $request->name,
                            'copyright_text' => $request->copyright_text ?? null,
                        ]);
                        // Check the wordpress sites are existing or not and continue
                        $wordpress = Wordpress::all();
                        if (!empty($wordpress)){
                            $sync = new FileSyncController();
                            $update = $sync->updateSyncFile($id);
                        }
                        return response()->json([
                            'Status' => 'Success',
                            'message' => 'File Attributes are changed successfully',
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 'No Content',
                            'message' => 'There is no relevant information for selected query',
                        ],210);
                    }
                } else{
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'The File with name already exists',
                    ], 400);
                }
            } else {
                $name_exists = DB::table('folders_files')
                    ->where('folders_id','=',$request->folder_id)
                    ->where('id','!=',$id)
                    ->where('name','=',$request->name)
                    ->exists();

                if (!$name_exists){
                    if (FoldersFiles::where('id',$id)->exists()){
                        FoldersFiles::where('id',$id)->update([
                            'name' => $request->name,
                            'copyright_text' => $request->copyright_text ?? null,
                        ]);
                        return response()->json([
                            'Status' => 'Success',
                            'message' => 'File Attributes are changed successfully',
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 'No Content',
                            'message' => 'There is no relevant information for selected query',
                        ],210);
                    }
                } else{
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'The File with name already exists',
                    ], 400);
                }
            }
        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to show the particular folder structure.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            $final_breadcrumbs = array();
            $finals = array();

            $breadcrumbs = Folders::findOrFail($id)->ancestorsAndSelf;
            $breadcrumbs = $breadcrumbs->reverse();
            foreach ($breadcrumbs as $breadcrumb)
            {
                $finals[] = $breadcrumb;
            }
            foreach ($finals as $final)
            {
                $final_breadcrumbs[] = [
                    'id' => $final->id,
                    'parents_id' => $final->parents_id,
                    'name' => $final->name,
                    'created_at' => $final->created_at,
                    'updated_at' => $final->updated_at
                ];
            }
            $folders = Folders::where('parents_id',$id)->get();
            $files_details = FoldersFiles::where('folders_id',$id)->whereNull('store_type')
                ->orderBy('name')
                ->get();
            $files = $this->getFileDetails($files_details);
            $total_size = FoldersFiles::sum('size');
            $items = FoldersFiles::whereNull('store_type')->get();
            $items_count = $items->count();
            return response()->json([
                'total_size' => $total_size,
                'total_items' => $items_count,
                'breadcrumbs' => $final_breadcrumbs,
                'folders' => $folders,
                'files' => $files,
            ]);
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to structure the array for the files.
     * @param $files
     * @return array
     */
    public function getFileDetails($files):array
    {
        $result = array();
        if (!empty($files)) {
            foreach ($files as $file) {
                $resolution_files = $this->getFilesResolutionDetails($file->id);
                $result[] = [
                    'id' => $file->id,
                    'folders_id' => $file->folders_id,
                    'name' => $file->name,
                    'size' => $file->size,
                    'type' => $file->type,
                    'file_path' => $file->file_path,
                    'copyright_text' => $file->copyright_text,
                    'duration' => $file->duration,
                    'optimizing_status' => $file->optimizing_status,
                    'resolutions' => $resolution_files,
                    'created_at' => $file->created_at,
                ];
            }
        }
        return $result;
    } // End Function

    /**
     * Method allow to delete the particular Folder with dependencies.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroyFolder($id):JsonResponse
    {
        try {
            if (Folders::where('id',$id)->exists()){
                $dependentFolders = DB::table('folders')->where('parents_id',$id)->get();
                if (!empty($dependentFolders)){
                    Folders::where('parents_id',$id)->delete();
                }
                $dependentFiles = DB::table('folders_files')->where('folders_id',$id)->get();
                if (env('DISK_DRIVER') === 'mounted'){
                    $disk = 'volume';
                } else {
                    $disk = 'media';
                }
                if (!empty($dependentFiles)){
                    foreach ($dependentFiles as $dependentFile){
                        $file_delete = $this->deleteFilesWithResolution($disk, $dependentFile);
                    }
                    FoldersFiles::where('folders_id',$id)->delete();
                }
                Folders::where('id',$id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Folder is deleted successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to delete the particular File with dependencies.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroyFile($id):JsonResponse
    {
        try {
            if (FoldersFiles::where('id',$id)->exists()){
                // Check the wordpress sites are existing or not and continue
                $wordpress = Wordpress::all();
                if (!$wordpress->isEmpty()){
                    $sync = new FileSyncController();
                    $delete = $sync->deleteSyncFile($id);
                }
                $file = FoldersFiles::where('id',$id)->first();
                if (env('DISK_DRIVER') === 'mounted'){
                    $disk = 'volume';
                } else {
                    $disk = 'media';
                }
                $file_delete = $this->deleteFilesWithResolution($disk, $file);
                if ($file_delete->getStatusCode() === 200){
                    return response()->json([
                        'status' => 'Success',
                        'message' => 'File is deleted successfully',
                    ],200);
                } else {
                    return response()->json([
                        'status' => 'Success',
                        'message' => 'There is some issue in deleting the file, Please try again after some time.',
                    ],422);
                }
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function deleteFilesWithResolution($disk, $file):JsonResponse
    {
        try {
            $storage = Storage::disk($disk)->exists($file->hash_name);
            if($storage) {
                Storage::disk($disk)->delete($file->hash_name);
                FoldersFiles::where('id', $file->id)->delete();
                $resolution_files = FoldersFiles::where('optimized_parent_id', $file->id)->get();
                foreach ($resolution_files as $resolution_file){
                    Storage::disk($disk)->delete($resolution_file->hash_name);
                }
                FoldersFiles::where('optimized_parent_id', $file->id)->delete();
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'File is deleted successfully',
            ],200);
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to download the particular File.
     * @param $id
     * @throws Exception
     */
    public function downloadFile($id)
    {
        try {
            if (FoldersFiles::where('id',$id)->exists()) {
                $file = FoldersFiles::where('id', $id)->first();
                if (env('DISK_DRIVER') === 'mounted'){
                    $disk = 'volume';
                } else {
                    $disk = 'media';
                }
                $storage = Storage::disk($disk)->exists($file->hash_name);
                if ($storage) {
                    return Storage::disk($disk)->download($file->hash_name,$file->name);
                } else {
                    return response()->json([
                        'status' => 'Warning',
                        'message' => 'There is some issue while downloading the File.',
                    ],204);
                }
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to download the particular File.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            $folders_delete = false;
            $files_delete = false;
            if (env('DISK_DRIVER') === 'mounted'){
                $disk = 'volume';
            } else {
                $disk = 'media';
            }
            if (!empty($request->folders_id)){
                foreach ($request->folders_id as $folder_id){
                    if (Folders::where('id',$folder_id)->exists()){
                        $dependentFolders = DB::table('folders')->where('parents_id',$folder_id)->get();
                        if (!empty($dependentFolders)){
                            Folders::where('parents_id',$folder_id)->delete();
                        }
                        $dependentFiles = DB::table('folders_files')->where('folders_id',$folder_id)->get();
                        if (!empty($dependentFiles)){
                            foreach ($dependentFiles as $dependentFile){
                                $this->deleteFilesWithResolution($disk, $dependentFile);
                            }
                            FoldersFiles::where('folders_id',$folder_id)->delete();
                        }
                        Folders::where('id',$folder_id)->delete();
                    }
                }
                $folders_delete = true;
            }
            if (!empty($request->files_id)){
                foreach ($request->files_id as $file_id){
                    if (FoldersFiles::where('id',$file_id)->exists()){
                        $files = FoldersFiles::where('id',$file_id)->first();
                        $this->deleteFilesWithResolution($disk, $files);
                    }
                }
                $files_delete = true;
            }
            if ($folders_delete == true || $files_delete == true){
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The selected folders or files are deleted successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'There is some issue with deleting selected files',
                ],400);
            }

        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to download the particular File.
     * @param $file_id
     * @return JsonResponse
     * @throws Exception
     */
    public function getFile($file_id):JsonResponse
    {
        try {
            if (FoldersFiles::where('id',$file_id)->exists()){
                $files = FoldersFiles::where('id',$file_id)->first();
                $resolutions = $this->getFilesResolutionDetails($file_id);
                $files_details = [
                    'id' => $files->id,
                    'file_url' => $files->file_path,
                    'file_type' => $files->type,
                    'resolutions' => $resolutions,
                ];
                return response()->json([
                    'fileDetails' => $files_details,
                    'status' => 'Success',
                ],200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to move the files and folders for other destination
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function moveFilesFolders(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'destination_id' => 'nullable|integer'
            ]);
            // Move Files if not empty
            if (!empty($request->files_id)){
                foreach ($request->files_id as $id){
                    $file_details = FoldersFiles::where('id', $id)->update([
                        'folders_id' => $request->destination_id,
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                }
            }
            // Move Folders if not empty
            if (!empty($request->folders_id)){
                foreach ($request->folders_id as $id){
                    $file_details = Folders::where('id', $id)->update([
                        'parents_id' => $request->destination_id,
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                }
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'The Folders or Files are moved successfully'
            ],200);
        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to convert the files & folders of the system to Zip.
     * @throws Exception
     */
    public function downloadZipFile(Request $request)
    {
        try {
            $folders = $request['folders'];
            $files = $request['files'];
            $now = new DateTime();
            $now->format('Y-m-d H:i:s');
            $current_timestamp = $now->getTimestamp();
            $zip_file = $current_timestamp . '_file.zip'; // Name of our archive to download

            $zip = new \ZipArchive();
            $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            if (!empty($folders)) {
                foreach ($folders as $folder_value) {
                    $folder = Folders::where('id', $folder_value)->first();
                    $get_folders_files = FoldersFiles::where('folders_id', $folder_value)->get();
                    foreach ($get_folders_files as $single_file) {
                        if (env('DISK_DRIVER') === 'mounted'){
                            $file_name = storage_path().'/volume/mnt/'.env('DISK_VOLUME').'/media/'.$single_file->hash_name;
                        } else {
                            $file_name = storage_path('app/public/media/'.$single_file->hash_name);
                        }
                        $zip->addFile($file_name, $folder->name . '/' . $single_file->name . '.' . $single_file->type);
                    }
                }
            }
            if (!empty($files)) {
                foreach ($files as $files_value) {
                    $get_files = FoldersFiles::where('id', $files_value)->first();
                    if (env('DISK_DRIVER') === 'mounted'){
                        $file_name = storage_path().'/volume/mnt/'.env('DISK_VOLUME').'/media/'.$get_files->hash_name;
                    } else {
                        $file_name = storage_path('app/public/media/'.$get_files->hash_name);
                    }
                    $zip->addFile($file_name, $get_files->name . '.' . $get_files->type);
                }
            }
            $zip->close();
            $zip_file_path = public_path($zip_file);
            return response()->download($zip_file_path, 'test123.zip', array('Content-Type: application/zip'))->deleteFileAfterSend(true);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } //End function

    /**
     * Method allow to get the folders of the system.
     * @return JsonResponse
     * @throws Exception
     */
    public function getFolders():JsonResponse
    {
        try {
            $folders = Folders::all();
            $result = array();
            $result[] = [
                'id' => null,
                'name' => 'root',
            ];
            foreach ($folders as $folder){
                if ($folder->parents_id != null){
                    $folders_details = Folders::where('id', $folder->id)->first();
                    $parent_folder_details = Folders::where('id', $folders_details->parents_id)->first();
                    $parent_name = $parent_folder_details->name;
                } else {
                    $parent_name = 'root';
                }
                $result[] = [
                    'id' => $folder->id,
                    'parent_id' => $folder->parents_id,
                    'parent_name' => $parent_name,
                    'name' => $folder->name,
                ];
            }
            return response()->json([
                'folderDetails' => $result,
                'status' => 'Success'
            ],200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function optimizeFiles(Request $request):JsonResponse
    {
        try {
            if (!empty($request->files_id)){
                if (env('DISK_DRIVER') === 'mounted'){
                    $disk = 'volume';
                } else {
                    $disk = 'media';
                }
                $counter = 3;
                foreach ($request->files_id as $file_id) {
                    $file = FoldersFiles::where('id', $file_id)->first();
                    if (Storage::disk($disk)->exists($file->hash_name)) {
                        // Change the status of the file
                        $file->optimizing_status = 'processing';
                        $file->save();
                        $exiting_files = FoldersFiles::where('optimized_parent_id', $file->id)->get();
                        if (!$exiting_files->isEmpty()) {
                            foreach ($exiting_files as $existing_file) {
                                $storage = Storage::disk($disk)->exists($existing_file->hash_name);
                                if ($storage) {
                                    Storage::disk($disk)->delete($existing_file->hash_name);
                                }
                            }
                            FoldersFiles::where('optimized_parent_id', $file->id)->delete();
                        }
                        $notifications_id = DB::table('notifications')->insertGetId([
                            'data_id' => $file->id,
                            'data_name' => $file->name,
                            'notification_type' => 'image_optimization',
                            'data_channel' => 'Image Optimization',
                            'status' => 'processing',
                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        ]);
                        $user = User::where('id', $request->notified_by)->first();
                        // attach the user for the notification
                        $user->notifications()->attach($notifications_id, ['created_at' => Carbon::now()->format('Y-m-d H:i:s')]);
                        // Run thr JOB
                        ImageOptimization::dispatch($file->id, $notifications_id)->delay(now()->addSeconds($counter));
                    } else {
                        Notifications::where('id', $notifications_id)->update([
                            'status' => 'failed',
                            'error_message' => 'There is no such file in the System, Please try again'
                        ]);
                        $file->optimizing_status = 'lastOptimizationFailed';
                        $file->save();
                    }
                    $counter += 3;
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The optimization has been started. Will be notified once done!!!',
                ],200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no files for optimization, Please select at least one file to optimize'
                ],210);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function optimizeBatchFiles(Request $request):JsonResponse
    {
        try {
            if (!empty($request->files_id)) {
                if (env('DISK_DRIVER') === 'mounted'){
                    $disk = 'volume';
                } else {
                    $disk = 'media';
                }
                $notifications_id = DB::table('notifications')->insertGetId([
                    'data_id' => 1000001,
                    'data_name' => 'Batch Job',
                    'notification_type' => 'batch_image_optimization',
                    'data_channel' => 'Batch Image Optimization',
                    'status' => 'processing',
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
                $user = User::where('id', $request->notified_by)->first();
                // attach the user for the notification
                $user->notifications()->attach($notifications_id, ['created_at' => Carbon::now()->format('Y-m-d H:i:s')]);
                // Start looping the Jobs with 2 seconds time interval
                $array_string = '';
                $count_seconds = 3;
                foreach ($request->files_id as $key => $file_id){
                    if (empty($array_string)){
                        $array_string = (string)$file_id;
                    } else {
                        $array_string = $array_string . '-' . $file_id;
                    }
                    $count = count($request->files_id);
                    if ($count === $key + 1){
                        $type = null;
                    } else {
                        $type = 'batch';
                    }
                    $file = FoldersFiles::where('id', $file_id)->first();
                    $file->optimizing_status = 'processing';
                    $file->save();
                    if (Storage::disk($disk)->exists($file->hash_name)) {
                        $exiting_files = FoldersFiles::where('optimized_parent_id', $file->id)->get();
                        if (!$exiting_files->isEmpty()){
                            foreach ($exiting_files as $existing_file){
                                $storage = Storage::disk($disk)->exists($existing_file->hash_name);
                                if ($storage){
                                    Storage::disk($disk)->delete($existing_file->hash_name);
                                }
                            }
                            FoldersFiles::where('optimized_parent_id', $file->id)->delete();
                        }
                        ImageOptimization::dispatch($file->id, $notifications_id, $type)->delay(now()->addSeconds($count_seconds));
                        $count_seconds += 3;
                    }
                }
                // Update the name of the Notification record with ids of the Images
                Notifications::where('id', $notifications_id)->update(['data_name' => $array_string]);
                // Return the response of the result
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The optimization has been started. Will be notified once done!!!',
                ],200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'please select atleast one File for Optimizing',
                ],210);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

} // End Class

<?php

namespace App\Http\Controllers;

use App\Models\FoldersFiles;
use Carbon\Carbon;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function convertToEnglish($string):string
    {
        $string = str_replace("ä", "ae", $string);
        $string = str_replace("ü", "ue", $string);
        $string = str_replace("ö", "oe", $string);
        $string = str_replace("Ä", "Ae", $string);
        $string = str_replace("Ü", "Ue", $string);
        $string = str_replace("Ö", "Oe", $string);
        $string = str_replace("ß", "ss", $string);
        $string = str_replace("´", "", $string);
        return $string;

    } // End Function

    /**
     * Method allow get the pagination details for the settings
     * @param $details
     * @param $items_per_page
     * @param $actual_total_items
     * @return array
     */
    public function getPaginationDetails($details, $items_per_page, $actual_total_items):array
    {
        $original_count = count($details->get());
        $posts_settings = $details->paginate($items_per_page);
        // $post_details = $this->getPostList($posts_settings);
        $pagination_details = [
            'current_page' => $posts_settings->currentPage(),
            'number_of_pages' => $posts_settings->lastPage(),
            'total_items' => $original_count,
            'actual_total_items' => $actual_total_items,
        ];
        if ($posts_settings->nextPageUrl() != null) {
            $pagination_details = array_merge($pagination_details, ['next_page' => $posts_settings->withQueryString()->nextPageUrl()]);
        }
        if ($posts_settings->previousPageUrl() != null) {
            $pagination_details = array_merge($pagination_details, ['previous_page' => $posts_settings->withQueryString()->previousPageUrl()]);
        }
        return $pagination_details;
    } // End Function

    /**
     * Method allow to generate the random code for the different purposes in the system
     * @param $length
     * @return string
     */
    public function generateCode($length):string {

        $characters = '123456789ABCDEFGHIJKLMNPRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;

    } // End function

    /**
     * Method allow to store the temporary files in to the system only for the edited purpose
     * @param $media
     * @param $store_type
     * @return int
     */
    public function storeMediaFile($media, $store_type)
    {
        $url = URL::to('/');
        File::delete(public_path('storage'));
        if (env('DISK_DRIVER') === 'mounted') {
            Config::set('filesystems.links.'.public_path('storage'), storage_path().'/volume/mnt/'.env('DISK_VOLUME'));
            symlink(storage_path().'/volume/mnt/'.env('DISK_VOLUME'), public_path('storage'));
            $destination_path = '';
        } else {
            Artisan::call('storage:link');
            $destination_path = 'public/media';
        }

        $name = $media->getClientOriginalName();
        $filename = pathinfo($name, PATHINFO_FILENAME);
        $size = $media->getSize();
        $type = $media->extension();
        $hash_name = $media->hashName();

        if (env('DISK_DRIVER') === 'mounted'){
            $path = $media->storeAs($destination_path, $hash_name, 'volume');
        } else {
            $path = $media->storeAs($destination_path, $hash_name);
        }

        return $hash_name;
    }

    /**
     * Method allow to destroy Media files in Disks
     * @param $file_name
     * @return true
     */
    public function destroyMediaFile($file_name)
    {
        if(!empty($file_name)) {
            if (env('DISK_DRIVER') === 'mounted') {
                $storage = Storage::disk('volume')->exists($file_name);
                if($storage) {
                    Storage::disk('volume')->delete($file_name);
                }
            } else {
                $storage = Storage::disk('media')->exists($file_name);
                if($storage) {
                    Storage::disk('media')->delete($file_name);
                }
            }
        }
        return true;
    }

    /**
     * Method allow to get all the details of the MasterData and display
     * @param $data
     * @return array
     */
    public function getMasterDataDetailsOverview($data):array
    {
        $result_array = array();
        if (!empty($data)){
            foreach ($data as $result){
                if ($result->seo_picture_id != null){
                    $file = FoldersFiles::where('id', $result->seo_picture_id)->first();
                    $seo_picture_url = $file->file_path;
                } else {
                    $seo_picture_url = null;
                }
                $result->seo_pictue_url = $seo_picture_url;
                $result_array[] = $result;
            }
        }
        return $result_array;
    } // End Function

    /**
     * Method allow to generateRandom UserName
     * @return string
     */
    public function generateRandomUserName():string
    {
//        $name = $this->generateCode(8);
        $name = $this->generateCode(5);
        $result = 'anonym_' . $name;
        return $result;
    } // End Function

    /**
     * Method allow to compile all the resolutions of the files and use according the needs
     * @param $id
     * @return array
     */
    public function getFilesResolutionDetails($id):array
    {
        $result_array = array();
        $resolution_files = FoldersFiles::where('optimized_parent_id', $id)->get();
        if (!$resolution_files->isEmpty()) {
            foreach ($resolution_files as $resolution_file) {
                $result_array[] = [
                    'resolution' => $resolution_file->resolution,
                    'file_url' => $resolution_file->file_path,
                ];
            }
        }
        return $result_array;
    }

    /**
     * Method allow to store the temporary files in to the system only for the edited purpose
     * @param $media
     * @param $store_type
     * @return int
     */
    public function storeTempFile($media, $store_type)
    {
        $url = URL::to('/');
        File::delete(public_path('storage'));
        if (env('DISK_DRIVER') === 'mounted') {
            Config::set('filesystems.links.'.public_path('storage'), storage_path().'/volume/mnt/'.env('DISK_VOLUME'));
            symlink(storage_path().'/volume/mnt/'.env('DISK_VOLUME'), public_path('storage'));
            // Artisan::call('storage:link');
            $destination_path = '';
        } else {
            Artisan::call('storage:link');
            $destination_path = 'public/editedMedia';
        }
        $name = $media->getClientOriginalName();
        $filename = pathinfo($name, PATHINFO_FILENAME);
        $size = $media->getSize();
        $type = $media->extension();
        $hash_name = $media->hashName();
        $file_path = $url. '/storage/editedMedia/' .$hash_name;
        if (env('DISK_DRIVER') === 'mounted'){
            $path = $media->storeAs($destination_path, $hash_name, 'volumeEditedMedia');
        } else {
            $path = $media->storeAs($destination_path, $hash_name);
        }
        $edited_logo_id = DB::table('folders_files')->insertGetId([
            'folders_id' => null,
            'name' => $filename,
            'size' => $size,
            'type' => $type,
            'hash_name' => $hash_name,
            'file_path' => $file_path,
            'store_type' => $store_type,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        return $edited_logo_id;
    } // End Function

    /**
     * Method allow to crypt the random string by its own key for safety
     * @param $method
     * @param $string
     * @param $secret_key
     * @return string
     */
    public function cryptRandomString($method, $string, $secret_key):string
    {
        $config = 'aes-256-cbc';
        // see if the key starts with 'base64:'
        if (Str::startsWith($key = $secret_key, 'base64:')) {
            // decode the key
            $key = base64_decode(substr($key, 7));
        }
        $encrypter = new Encrypter($key, $config);
        if ($method == 'encrypt'){
            $cryptedString = $encrypter->encryptString($string);
        } else {
            $cryptedString = $encrypter->decryptString($string);
        }
        return $cryptedString;
    }

}

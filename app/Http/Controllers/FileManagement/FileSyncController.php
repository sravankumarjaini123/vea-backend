<?php

namespace App\Http\Controllers\FileManagement;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wordpress\WordpressController;
use App\Models\FoldersFiles;
use App\Models\Wordpress;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileSyncController extends WordpressController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Method allow to sync the new Categories to the Wordpress respective to the Posts
     * @param $wordpress_id
     * @param $file_id
     * @param $post_id
     */
    public function MediaSyncNew($wordpress_id, $file_id, $post_id)
    {
        try {
            if (Wordpress::where('id',$wordpress_id)->exists()) {
                $wordpress = Wordpress::where('id', $wordpress_id)->first();
                $authentication = $this->authenticateUserById($wordpress->id);
                $check_medias = json_decode( $this->client->request(
                    'GET',
                    $wordpress->site_url . '/wp-json/wp/v2/media/',
                    [ 'headers' => $this->headers, 'form_params'] )->getBody() );

                $wp_array_medias = array();
                foreach ($check_medias as $check_media){
                    if ($check_media->media_type != 'file'){
                        $wp_array_medias[] = $check_media->media_details->file;
                    }
                }
                $file = FoldersFiles::where('id',$file_id)->first();
                $date = Carbon::now()->format('Y/m');
                $file_name = $date.'/'.$file->name;
                if (!(in_array($file_name,$wp_array_medias))){
                    if($wordpress->files()->where('files_id',$file->id)->exists()){
                        $this->updateExistingMedia($file->id, $wordpress->id);
                    } else {
                        $this->syncNewMedia($file->id, $wordpress->id, $post_id);
                    }
                } else {
                    $check_medias_2 = json_decode( $this->client->request(
                        'GET',
                        $wordpress->site_url . '/wp-json/wp/v2/media/',
                        [ 'headers' => $this->headers, 'form_params'] )->getBody() );
                    $wp_array_medias_2 = array();
                    foreach ($check_medias_2 as $check_media_2){
                        $wp_array_medias_2[] = [
                            'id' => $check_media_2->id,
                            'name' => $check_media_2->media_details->file,
                        ];
                    }
                    foreach ($wp_array_medias_2 as $wp_array_media_2){
                        if ($file_name == $wp_array_media_2['name']){
                            if($wordpress->files()->where('files_id',$file->id)->exists()){
                                $wp_update_details = [
                                    'wp_file_id' => $wp_array_media_2['id'],
                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                ];
                                $wordpress->files()->updateExistingPivot($file->id, $wp_update_details);
                            } else {
                                $wp_media_details = [
                                    'wp_file_id' => $wp_array_media_2['id'],
                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                ];
                                // Save the data as relation for next usage
                                $wordpress->files()->attach($file->id, $wp_media_details);
                            }
                        }
                    }
                }
            }
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function updateExistingMedia($file_id, $wordpress_id)
    {
        try {
            $wordpress = Wordpress::where('id', $wordpress_id)->first();
            $media_data = $wordpress->files()->where('files_id',$file_id)->first();
            $convertedString = $this->convertToEnglish($media_data->name);
            $slug = Str::slug($convertedString,'-');
            $update_media_details = [
                'title' => $media_data->name,
                'slug' => $slug,
                'alt_text' => $media_data->name,
                'caption' => $media_data->copyright_text,
            ];
            $raw_media_id = json_decode( $this->client->request(
                'POST',
                $wordpress->site_url . '/wp-json/wp/v2/media/'.$media_data->pivot->wp_file_id,
                [ 'headers' => $this->headers, 'form_params' => $update_media_details] )->getBody() )->id;
            $wp_media_details = [
                'wp_file_id' =>  $raw_media_id,
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];
            $wordpress->files()->updateExistingPivot($file_id, $wp_media_details);
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function syncNewMedia($file_id, $wordpress_id, $post_id)
    {
        try {
            $wordpress = Wordpress::where('id', $wordpress_id)->first();
            $authentication = $this->authenticateUserById($wordpress->id);
            $media = FoldersFiles::where('id', $file_id)->first();
            if (env('DISK_DRIVER') === 'mounted'){
                $disk = 'volume';
            } else {
                $disk = 'media';
            }
            $source_url = Storage::disk($disk)->path($media->hash_name);
            $basename = basename($media->file_path);
            // $source_url = $source_url.$basename;
            $fdata = file_get_contents($source_url, false);

            $this->headers = array_merge([
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'application/binary',
                'Content-Disposition' => 'attachment; filename=' .$media->name.'.'.$media->type,
            ], $this->headers);

            $wp_file_id = json_decode($this->client->request(
                'POST',
                $wordpress->site_url . '/wp-json/wp/v2/media/',
                ['headers' => $this->headers, 'body' => $fdata])
                ->getBody())->id;

            $this->postSaveUpdateMedia($media->id, $wordpress_id, $wp_file_id, $post_id);
            $wp_med_details = [
                'wp_file_id' => $wp_file_id,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];
            // Save the data as relation for next usage
            $wordpress->files()->attach($file_id, $wp_med_details);
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function postSaveUpdateMedia($media_id, $wordpress_id, $file_id, $post_id)
    {
        try {
            $wordpress = Wordpress::where('id', $wordpress_id)->first();
            $authentication = $this->authenticateUserById($wordpress->id);
            $wordpress_post = $wordpress->posts()->where('posts_id', $post_id)->first();
            $media = FoldersFiles::where('id', $media_id)->first();
            $convertedString = $this->convertToEnglish($media->name);
            $slug = Str::slug($convertedString, '-');
            $new_media_details = [
                'title' => $media->name,
                'slug' => $slug,
                'status' => 'publish',
                'alt_text' => $media->name,
                'post' => $wordpress_post->pivot->wp_post_id,
                'caption' => $media->copyright_text,
            ];
            $update = json_decode($this->client->request(
                'POST',
                $wordpress->site_url . '/wp-json/wp/v2/media/'.$file_id,
                ['headers' => $this->headers, 'form_params' => $new_media_details])
                ->getBody())->id;
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function updateSyncFile($media_id)
    {
        try {
            $wordpress_sites = Wordpress::all();
            foreach ($wordpress_sites as $wordpress_site) {
                if ($wordpress_site->files()->where('files_id', $media_id)->exists()) {
                    $file = FoldersFiles::where('id', $media_id)->first();
                    $this->authenticateUserById($wordpress_site->id);
                    $check_files = json_decode($this->client->request(
                        'GET',
                        $wordpress_site->site_url . '/wp-json/wp/v2/media/',
                        ['headers' => $this->headers, 'form_params'])->getBody());
                    $wp_array_files = array();
                    foreach ($check_files as $check_file) {
                        $wp_array_files[] = $check_file->media_details->file;
                    }
                    if (!(in_array($file->name, $wp_array_files))) {
                        $wp_file_id = $wordpress_site->files()->where('files_id', $media_id)->first();
                        $convertedString = $this->convertToEnglish($file->name);
                        $slug = Str::slug($convertedString, '-');
                        $update_file_details = [
                            'title' => $file->name,
                            'slug' => $slug,
                            'alt_text' => $file->name,
                        ];
                        $category_delete = json_decode($this->client->request(
                            'POST',
                            $wordpress_site->site_url . '/wp-json/wp/v2/media/' . $wp_file_id->pivot->wp_file_id . '/',
                            ['headers' => $this->headers, 'form_params' => $update_file_details])->getBody());
                    }
                }
            }
            return true;
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function deleteSyncFile($media_id)
    {
        try {
            $wordpress_sites = Wordpress::all();
            foreach ($wordpress_sites as $wordpress_site){
                if ($wordpress_site->files()->where('files_id',$media_id)->exists()){
                    $wp_file_id = $wordpress_site->files()->where('files_id',$media_id)->first();
                    $authentication = $this->authenticateUserById($wordpress_site->id);
                    $category_delete = json_decode( $this->client->request(
                        'DELETE',
                        $wordpress_site->site_url . '/wp-json/wp/v2/media/'.$wp_file_id->pivot->wp_file_id.'?force=true',
                        [ 'headers' => $this->headers, 'form_params'] )
                        ->getBody() );
                }
            }
            return true;
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function
}

<?php

namespace App\Http\Controllers\Tags;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wordpress\WordpressController;
use App\Models\Tags;
use App\Models\Wordpress;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagSyncController extends WordpressController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Method allow to sync the new Tags to the Wordpress respective to the Posts
     * @param $wordpress_id
     * @param $tags_id
     */
    public function tagsSyncNew($wordpress_id, $tags_id)
    {
        try {
            if (Wordpress::where('id',$wordpress_id)->exists()) {
                $wordpress = Wordpress::where('id', $wordpress_id)->first();
                $authentication = $this->authenticateUserById($wordpress->id);
                $check_tags = json_decode( $this->client->request(
                    'GET',
                    $wordpress->site_url . '/wp-json/wp/v2/tags/',
                    [ 'headers' => $this->headers, 'form_params'] )->getBody() );
                $wp_array_tags = array();
                foreach ($check_tags as $check_tag){
                    $wp_array_tags[] = $check_tag->name;
                }
                $tags = Tags::where('id',$tags_id)->get();
                foreach ($tags as $tag){
                    if (!(in_array($tag->name,$wp_array_tags))){
                        if($wordpress->tags()->where('tags_id',$tag->id)->exists()){
                            $this->updateExistingTag($tag->id, $wordpress->id);
                        } else {
                            $this->syncNewTag($tag->id, $wordpress->id);
                        }
                    } else {
                        $check_tags = json_decode( $this->client->request(
                            'GET',
                            $wordpress->site_url . '/wp-json/wp/v2/tags/',
                            [ 'headers' => $this->headers, 'form_params'] )->getBody() );
                        $wp_array_tags = array();
                        foreach ($check_tags as $check_tag){
                            $wp_array_tags[] = [
                                'id' => $check_tag->id,
                                'name' => $check_tag->name,
                            ];
                        }
                        foreach ($wp_array_tags as $wp_array_tag){
                            if ($tag->name == $wp_array_tag['name']){
                                if($wordpress->tags()->where('tags_id',$tag->id)->exists()){
                                    $wp_update_details = [
                                        'wp_tag_id' => $wp_array_tag['id'],
                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    ];
                                    $wordpress->tags()->updateExistingPivot($tag->id, $wp_update_details);
                                } else {
                                    $wp_tag_details = [
                                        'wp_tag_id' => $wp_array_tag['id'],
                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    ];
                                    // Save the data as relation for next usage
                                    $wordpress->tags()->attach($tag->id, $wp_tag_details);
                                }
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

    /**
     * Method allow updating if the post is already existing in the WordPress
     * @param $tag_id
     * @param $wordpress_id
     */
    public function updateExistingTag($tag_id, $wordpress_id)
    {
        try {
            $wordpress = Wordpress::where('id', $wordpress_id)->first();
            $tag_data = $wordpress->tags()->where('tags_id',$tag_id)->first();
            $convertedString = $this->convertToEnglish($tag_data->name);
            $slug = Str::slug($convertedString,'-');
            $update_tag_details = [
                'name' => $tag_data->name,
                'slug' => $slug
            ];
            $raw_tag_id = json_decode( $this->client->request(
                'POST',
                $wordpress->site_url . '/wp-json/wp/v2/tags/'.$tag_data->pivot->wp_tag_id.'/',
                [ 'headers' => $this->headers, 'form_params'=>$update_tag_details] )->getBody() )->id;
            $wp_update_details = [
                'wp_tag_id' =>  $raw_tag_id,
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];
            $wordpress->tags()->updateExistingPivot($tag_id, $wp_update_details);
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to create the Tag if not existing in the Wordpress
     * @param $tag_id
     * @param $wordpress_id
     */
    public function syncNewTag($tag_id, $wordpress_id)
    {
        try {
            $wordpress = Wordpress::where('id', $wordpress_id)->first();
            $tag = Tags::where('id', $tag_id)->first();
            $convertedString = $this->convertToEnglish($tag->name);
            $authentication = $this->authenticateUserById($wordpress_id);
            $slug = Str::slug($convertedString, '-');
            $new_tag_details = [
                'name' => $tag->name,
                'slug' => $slug
            ];
            // save the data in the WordPress
            $tag_id = json_decode($this->client->request(
                'POST',
                $wordpress->site_url . '/wp-json/wp/v2/tags/',
                ['headers' => $this->headers, 'form_params' => $new_tag_details])
                ->getBody())->id;
            $wp_tag_details = [
                'wp_tag_id' => $tag_id,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];
            // Save the data as relation for next usage
            $wordpress->tags()->attach($tag->id, $wp_tag_details);
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow updating if the Tag is updated in the Tag module
     * @param $tag_id
     */
    public function updateSyncTag($tag_id)
    {
        try {
            $wordpress_sites = Wordpress::all();
            foreach ($wordpress_sites as $wordpress_site){
                if ($wordpress_site->tags()->where('tags_id',$tag_id)->exists()){
                    $tag = Tags::where('id',$tag_id)->first();
                    $this->authenticateUserById($wordpress_site->id);
                    $check_tags = json_decode( $this->client->request(
                        'GET',
                        $wordpress_site->site_url . '/wp-json/wp/v2/tags/',
                        [ 'headers' => $this->headers, 'form_params'] )->getBody() );
                    $wp_array_tags = array();
                    foreach ($check_tags as $check_tag){
                        $wp_array_tags[] = $check_tag->name;
                    }
                    if (!(in_array($tag->name,$wp_array_tags))) {
                        $wp_tag_id = $wordpress_site->tags()->where('tags_id', $tag_id)->first();
                        $convertedString = $this->convertToEnglish($tag->name);
                        $slug = Str::slug($convertedString,'-');
                        $update_tag_details = [
                            'name' => $tag->name,
                            'slug' => $slug
                        ];
                        $category_delete = json_decode($this->client->request(
                            'POST',
                            $wordpress_site->site_url . '/wp-json/wp/v2/tags/'.$wp_tag_id->pivot->wp_tag_id.'/',
                            ['headers' => $this->headers, 'form_params'=>$update_tag_details])->getBody());
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

    /**
     * Method allow to Delete the Tags permanently from the wordpress sites on delete in Tags module.
     * @param $tag_id
     */
    public function deleteSyncTag($tag_id)
    {
        try {
            $wordpress_sites = Wordpress::all();
            foreach ($wordpress_sites as $wordpress_site){
                if ($wordpress_site->tags()->where('tags_id',$tag_id)->exists()){
                    $wp_tag_id = $wordpress_site->tags()->where('tags_id',$tag_id)->first();
                    $authentication = $this->authenticateUserById($wordpress_site->id);
                    $category_delete = json_decode( $this->client->request(
                        'DELETE',
                        $wordpress_site->site_url . '/wp-json/wp/v2/tags/'.$wp_tag_id->pivot->wp_tag_id.'?force=true',
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

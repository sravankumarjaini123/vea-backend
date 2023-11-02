<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wordpress\WordpressController;
use App\Models\Categories;
use App\Models\Wordpress;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategorySyncController extends WordpressController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Method allow to sync the new Categories to the Wordpress respective to the Posts
     * @param $wordpress_id
     * @param array $categories_id
     */
    public function categoriesSyncNew($wordpress_id, array $categories_id)
    {
        try {
            if (Wordpress::where('id',$wordpress_id)->exists()) {
                $wordpress = Wordpress::where('id', $wordpress_id)->first();
                $authentication = $this->authenticateUserById($wordpress->id);
                $check_categories = json_decode( $this->client->request(
                    'GET',
                    $wordpress->site_url . '/wp-json/wp/v2/categories/',
                    [ 'headers' => $this->headers, 'form_params'] )->getBody() );
                $wp_array_categories = array();
                foreach ($check_categories as $check_category){
                    $wp_array_categories[] = $check_category->name;
                }
                $categories = Categories::whereIn('id',$categories_id)->get();
                foreach ($categories as $category){
                    if (!(in_array($category->name,$wp_array_categories))){
                        if($wordpress->categories()->where('categories_id',$category->id)->exists()){
                            $this->updateExistingCategory($category->id, $wordpress->id);
                        } else {
                            $this->syncNewCategory($category->id, $wordpress->id);
                        }
                    } else {
                        $check_categories = json_decode( $this->client->request(
                            'GET',
                            $wordpress->site_url . '/wp-json/wp/v2/categories/',
                            [ 'headers' => $this->headers, 'form_params'] )->getBody() );
                        $wp_array_categories = array();
                        foreach ($check_categories as $check_category){
                            $wp_array_categories[] = [
                                'id' => $check_category->id,
                                'name' => $check_category->name,
                            ];
                        }
                        foreach ($wp_array_categories as $wp_array_category){
                            if ($category->name == $wp_array_category['name']){
                                if($wordpress->categories()->where('categories_id',$category->id)->exists()){
                                    $wp_update_details = [
                                        'wp_category_id' => $wp_array_category['id'],
                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    ];
                                    $wordpress->categories()->updateExistingPivot($category->id, $wp_update_details);
                                } else {
                                    $wp_cat_details = [
                                        'wp_category_id' => $wp_array_category['id'],
                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    ];
                                    // Save the data as relation for next usage
                                    $wordpress->categories()->attach($category->id, $wp_cat_details);
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
     * @param $category_id
     * @param $wordpress_id
     */
    public function updateExistingCategory($category_id, $wordpress_id)
    {
        try {
            $wordpress = Wordpress::where('id', $wordpress_id)->first();
            $cat_data = $wordpress->categories()->where('categories_id',$category_id)->first();
            $convertedString = $this->convertToEnglish($cat_data->name);
            $slug = Str::slug($convertedString,'-');
            $update_cat_details = [
                'name' => $cat_data->name,
                'slug' => $slug
            ];
            $raw_category_id = json_decode( $this->client->request(
                'POST',
                $wordpress->site_url . '/wp-json/wp/v2/categories/'.$cat_data->pivot->wp_category_id.'/',
                [ 'headers' => $this->headers, 'form_params'=>$update_cat_details] )->getBody() )->id;
            $wp_update_details = [
                'wp_category_id' =>  $raw_category_id,
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];
            $wordpress->categories()->updateExistingPivot($category_id, $wp_update_details);
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to create the category if not existing in the Wordpress
     * @param $category_id
     * @param $wordpress_id
     */
    public function syncNewCategory($category_id, $wordpress_id)
    {
        try {
            $wordpress = Wordpress::where('id', $wordpress_id)->first();
            $category = Categories::where('id', $category_id)->first();
            $convertedString = $this->convertToEnglish($category->name);
            $slug = Str::slug($convertedString, '-');
            $new_cat_details = [
                'name' => $category->name,
                'slug' => $slug
            ];
            // save the data in the WordPress
            $category_id = json_decode($this->client->request(
                'POST',
                $wordpress->site_url . '/wp-json/wp/v2/categories/',
                ['headers' => $this->headers, 'form_params' => $new_cat_details])
                ->getBody())->id;

            $wp_cat_details = [
                'wp_category_id' => $category_id,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];
            // Save the data as relation for next usage
            $wordpress->categories()->attach($category->id, $wp_cat_details);
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow updating if the category is updated in the Category module
     * @param $cat_id
     */
    public function updateSyncCategory($cat_id)
    {
        try {
            $wordpress_sites = Wordpress::all();
            foreach ($wordpress_sites as $wordpress_site){
                if ($wordpress_site->categories()->where('categories_id',$cat_id)->exists()){
                    $category = Categories::where('id',$cat_id)->first();
                    $this->authenticateUserById($wordpress_site->id);
                    $check_categories = json_decode( $this->client->request(
                        'GET',
                        $wordpress_site->site_url . '/wp-json/wp/v2/categories/',
                        [ 'headers' => $this->headers, 'form_params'] )->getBody() );
                    $wp_array_categories = array();
                    foreach ($check_categories as $check_category){
                        $wp_array_categories[] = $check_category->name;
                    }
                    if (!(in_array($category->name,$wp_array_categories))) {
                        $wp_cat_id = $wordpress_site->categories()->where('categories_id', $cat_id)->first();
                        $convertedString = $this->convertToEnglish($category->name);
                        $slug = Str::slug($convertedString,'-');
                        $update_cat_details = [
                            'name' => $category->name,
                            'slug' => $slug
                        ];
                        $category_delete = json_decode($this->client->request(
                            'POST',
                            $wordpress_site->site_url . '/wp-json/wp/v2/categories/'.$wp_cat_id->pivot->wp_category_id.'/',
                            ['headers' => $this->headers, 'form_params'=>$update_cat_details])->getBody());
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
     * Method allow to Delete the categories permanently from the wordpress sites on delete in Category module.
     * @param $cat_id
     */
    public function deleteSyncCategory($cat_id)
    {
        try {
            $wordpress_sites = Wordpress::all();
            foreach ($wordpress_sites as $wordpress_site){
                if ($wordpress_site->categories()->where('categories_id',$cat_id)->exists()){
                    $wp_cat_id = $wordpress_site->categories()->where('categories_id',$cat_id)->first();
                    $authentication = $this->authenticateUserById($wordpress_site->id);
                    $category_delete = json_decode( $this->client->request(
                        'DELETE',
                        $wordpress_site->site_url . '/wp-json/wp/v2/categories/'.$wp_cat_id->pivot->wp_category_id.'?force=true',
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

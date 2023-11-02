<?php

namespace App\Http\Controllers\Wordpress;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Folders;
use App\Models\FoldersFiles;
use App\Models\Groups;
use App\Models\Posts;
use App\Models\Wordpress;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;

class WordpressController extends Controller
{
    public $client;
    public $site_url;
    public $headers;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Method allow to authenticate if the credentials are correct
     * @param Request $request
     * @return bool
     */
    public function authenticate_user(Request $request):bool
    {
        try {
            $username = $request->username;
            $password = $request->password;

            $response = json_decode( $this->client->request( 'POST',$request->site_url . '/wp-json/api-bearer-auth/v1/login', [
                RequestOptions::JSON => [
                    'username' => $username,
                    'password' => $password,
                ],
            ] )->getBody());
            $this->headers = [
                'Authorization' => 'Bearer ' . $response->access_token,
                'Accept'        => 'application/json',
            ];

            return true;
        } catch (GuzzleException $exception) {
            return false;
        }
    } // End Function

    /**
     * Method allow to authenticate if the credentials are correct
     * @param Request $request
     * @return bool
     */
    public function authenticateUserById($id):bool
    {
        try {
            $wordpress = Wordpress::where('id',$id)->first();

            $username = $wordpress->username;
            $password = $this->cryptRandomString('decrypt', $wordpress->password, $wordpress->cryption_key);

            $response = json_decode( $this->client->request( 'POST',$wordpress->site_url . '/wp-json/api-bearer-auth/v1/login', [
                RequestOptions::JSON => [
                    'username' => $username,
                    'password' => $password,
                ],
            ] )->getBody());
            $this->headers = [
                'Authorization' => 'Bearer ' . $response->access_token,
                'Accept'        => 'application/json',
            ];
            return true;
        } catch (GuzzleException $exception) {
            return false;
        }
    } // End Function

    /**
     * Method allow to display list of all wordPress sites.
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $query = Wordpress::all();
            return response()->json([
                'wordpressDetails' => $query,
                'message' => 'Success',
            ], 200);

        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to Import the Posts from Wordpress to our Site.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function wordpressPosts(Request $request):JsonResponse
    {
        try {
            // Master Data
            $klima_category_id = null;
            $bp_category_id = null;
            $group_id = null;
            if (!Groups::where('name', 'Klimafreundlicher Mittelstand')->exists()) {
                $order_number = Groups::max('display_order');
                $group_id = Groups::insertGetId([
                    'name' => 'Klimafreundlicher Mittelstand',
                    'display_order' => $order_number + 1,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }
            if (!Groups::where('name', 'VEA')->exists()) {
                $order_number = Groups::max('display_order');
                Groups::insert([
                    'name' => 'VEA',
                    'display_order' => $order_number + 1,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }
            if (!Categories::where('name', 'Klima News')->exists()) {
                $order_number = Categories::max('display_order');
                $klima_category_id = Categories::insertGetId([
                    'name' => 'Klima News',
                    'display_order' => $order_number + 1,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }
            if (!Categories::where('name', 'Best Practise')->exists()) {
                $order_number = Categories::max('display_order');
                $bp_category_id = Categories::InsertGetId([
                    'name' => 'Best Practise',
                    'display_order' => $order_number + 1,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }

            $wordpress = Wordpress::all()->first();
            $authentication = $this->authenticateUserById($wordpress->id);
            $posts = json_decode($this->client->request(
                'GET',
                $wordpress->site_url . '/wp-json/wp/v2/posts?per_page='.$request->per_page. '&offset='.$request->offset,
                ['headers' => $this->headers])
                ->getBody());

            $this->postsImport($posts, $wordpress, $klima_category_id, $bp_category_id, $group_id);

            // $get_posts = Posts::where('shareable_type', 'wordpress')->where('shareable_posts', '!=', null)->get();
            return response()->json([
                'message' => 'Success',
                // 'wordpressPosts' => $get_posts,
            ], 200);
        } catch (GuzzleException $ex) {
            return response()->json([
                'status' => 'Error',
                'message' => $ex->getMessage(),
            ], 500);
        }
    } //End function

    /**
     * Method allow to Import the Posts from Wordpress to Our System
     * @param $posts // from Wordpress
     * @param $wordpress // details of the Wordpress
     * @param $klima_category_id // for Klima News
     * @param $bp_category_id // for Best Practise
     * @param $group_id // for the Group and internal purposes
     * @return JsonResponse
     * @throws ValidationException
     */
    public function postsImport($posts, $wordpress, $klima_category_id, $bp_category_id, $group_id):JsonResponse
    {
        try {
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    if (!Posts::where('title', $post->title->rendered)->exists()) {
                        $store_file = $this->importMediaFilesFromWordpress($post->featured_media);
                        $file_id = $store_file->getData()->file_id;
                        if ($post->status == 'publish') {
                            $status_id = 2;
                        } elseif ($post->status == 'draft') {
                            $status_id = 1;
                        } else {
                            $status_id = 4;
                        }
                        $seo_title = substr($post->title->rendered, 0, 60);
                        $seo_description = mb_substr(strip_tags($post->excerpt->rendered), 0, 200);

                        $post_id = Posts::insertGetId([
                            'title' => $post->title->rendered,
                            'introduction' => $post->excerpt->rendered,
                            'description' => $post->content->rendered,
                            'post_type' => 'image',
                            'post_file_id' => $file_id,
                            'status_id' => $status_id,
                            'published_at' => $post->date,
                            'visible_as_post' => 1,
                            'shareable_posts' => 1,
                            'shareable_type' => 'wordpress',
                            'shareable_callback_url' => $post->link,
                            'seo_tag' => $seo_title,
                            'seo_permalink' => $post->slug,
                            'seo_description' => $seo_description,
                        ]);
                        $post_final = Posts::where('id', $post_id)->first();
                        $wordpress->posts()->attach($post_id, ['wp_post_id'=>$post->id]);
                        // Category connection
                        if (!empty($post->categories)) {
                            foreach ($post->categories as $category) {
                                if ($category == 1) {
                                    $wordpress->categories()->attach($klima_category_id, ['wp_category_id' => $category]);
                                    $post_final->categories()->attach($klima_category_id, ['created_at' => Carbon::now()->format('Y-m-d H:i:s')]);
                                } elseif ($category == 24) {
                                    $wordpress->categories()->attach($bp_category_id, ['wp_category_id' => $category]);
                                    $post_final->categories()->attach($bp_category_id, ['created_at' => Carbon::now()->format('Y-m-d H:i:s')]);
                                }
                            }
                        }
                        // Files Condition for Wordpress
                        if ($file_id != null) {
                            $wordpress->files()->attach($file_id, ['wp_file_id' => $post->featured_media]);
                        }
                        // Groups Condition for Wordpress
                        $post_final->groups()->attach($group_id, ['created_at'=>Carbon::now()->format('Y-m-d H:i:s')]);
                    }
                }
            }
            return response()->json([
                'status' => 'Success',
            ], 200);
        } catch (GuzzleException $ex) {
            return response()->json([
                'status' => 'Error',
                'message' => $ex->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to update the name of the particular group.
     * @param $file_id // from the Wordpress Site
     * @return JsonResponse
     * @throws ValidationException
     */
    public function importMediaFilesFromWordpress($file_id):JsonResponse
    {
        try {
            $wordpress = Wordpress::where('id', 1)->first();
            $authentication = $this->authenticateUserById($wordpress->id);
            $file = json_decode($this->client->request(
                'GET',
                $wordpress->site_url . '/wp-json/wp/v2/media/' . $file_id,
                ['headers' => $this->headers])
                ->getBody());

            $file_path = $file->guid->rendered;
            $name = substr($file_path, strrpos($file_path, '/') + 1);
            $name_no_ext = substr($name, 0, strrpos($name, "."));
            if (! FoldersFiles::where('name', $name_no_ext)->exists()) {
                if (env('DISK_DRIVER') === 'mounted') {
                    $disk = 'volume';
                } else {
                    $disk = 'media';
                }

                Storage::disk($disk)->put($name, file_get_contents($file_path));

                $size = Storage::disk($disk)->size($name);
                $path = Storage::disk($disk)->path($name);
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $hash_name = \hash('sha256', $name);
                Storage::disk($disk)->move($name, $hash_name . '.' . $type);

                // Change the Sym link based on the mounted disk.
                $url = URL::to('/');
                File::delete(public_path('storage'));
                if (env('DISK_DRIVER') === 'mounted') {
                    Config::set('filesystems.links.' . public_path('storage'), storage_path() . '/volume/mnt/' . env('DISK_VOLUME'));
                    symlink(storage_path() . '/volume/mnt/' . env('DISK_VOLUME'), public_path('storage'));
                    // Artisan::call('storage:link');
                    $destination_path = '';
                } else {
                    Artisan::call('storage:link');
                    $destination_path = 'public/media';
                }

                $file_path = $url . '/storage/media/' . $hash_name . '.' . $type;

                if (!Folders::where('name', 'Wordpress')->exists()) {
                    $folder_id = Folders::insertGetId([
                        'name' => 'Wordpress',
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s')
                    ]);
                } else {
                    $folder_id = Folders::where('name', 'Wordpress')->first()->id;
                }
                $file_id = FoldersFiles::insertGetId([
                    'folders_id' => $folder_id,
                    'name' => $name_no_ext,
                    'size' => $size,
                    'type' => $type,
                    'hash_name' => $hash_name . '.' . $type,
                    'file_path' => $file_path,
                    'copyright_text' => '',
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            } else {
                $file_id = FoldersFiles::where('name', $name_no_ext)->first()->id;
            }
            return response()->json([
                'file_id' => $file_id,
                'status' => 'Success',
            ], 200);
        } catch (GuzzleException $ex) {
            return response()->json([
                'status' => 'Error',
                'message' => $ex->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to store or create the new Wordpress site.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'site_url' => 'required|string|unique:wordpress',
                'username' => 'required|string',
                'password' => 'required|string',
                'name' => 'required|string'
            ]);
            $authenticate_site = $this->authenticate_user($request);
            if ($authenticate_site){
                $crypted_string = 'base64:'.Str::random(40).'+'.Str::random(2).'=';
                $password = $this->cryptRandomString('encrypt', $request->password, $crypted_string);
                $wordpress_id = DB::table('wordpress')->insertGetId([
                    'name' => $request->name,
                    'site_url' => $request->site_url,
                    'username' => $request->username,
                    'password' => $password,
                    'cryption_key' => $crypted_string,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                $wordpress = Wordpress::where('id',$wordpress_id)->first();
                return response()->json([
                    'wordpressDetails' => $wordpress,
                    'status' => 'Success',
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please check the credentials entered for adding the wordpress site',
                ],403);
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
     * Method allow to update the name of the particular group.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            if (Wordpress::where('id',$id)->exists()) {
                $wordpress = Wordpress::where('id',$id)->first();
                $request->validate([
                    'site_url' => ['required','string', Rule::unique('wordpress', 'site_url')->ignore($wordpress->id)],
                    'username' => 'required|string',
                    'name' => 'required|string'
                ]);
                if ($request->password != null){
                    $request->request->add(['password' => $request->password]);
                    $password = $this->cryptRandomString('encrypt', $request->password, $wordpress->cryption_key);
                } else {
                    $password = $wordpress->password;
                    $decrypt_password = $this->cryptRandomString('decrypt', $password, $wordpress->cryption_key);
                    $request->request->add(['password' => $decrypt_password]);
                }
                $authenticate_site = $this->authenticate_user($request);
                if ($authenticate_site){
                    $wordpress_id = Wordpress::where('id',$id)->update([
                        'name' => $request->name,
                        'site_url' => $request->site_url,
                        'username' => $request->username,
                        'password' => $password,
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                    $wordpress = Wordpress::where('id',$id)->first();
                    return response()->json([
                        'wordpressDetails' => $wordpress,
                        'status' => 'Success',
                    ],200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Please check the credentials entered for updating the wordpress site',
                    ],403);
                }
            } else{
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
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
     * Method allow to delete the particular group.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (Wordpress::where('id',$id)->exists()){
                Wordpress::where('id',$id)->delete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Wordpress site is deleted successfully',
                ],200);

            }else{
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
}

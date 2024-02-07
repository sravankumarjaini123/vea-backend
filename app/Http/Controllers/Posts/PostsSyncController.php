<?php

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Categories\CategorySyncController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FileManagement\FileSyncController;
use App\Http\Controllers\Tags\TagSyncController;
use App\Http\Controllers\Wordpress\WordpressController;
use App\Jobs\PostsWordpressSync;
use App\Models\Posts;
use App\Models\User;
use App\Models\Wordpress;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PostsSyncController extends WordpressController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Method allow to attach the channels to the respective post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function postsChannelsAttach(Request $request, $id):JsonResponse
    {
        try {
            $request->validate([
                'channel_detail_id' => 'required|integer'
            ]);
            if (Posts::where('id',$id)->exists()){
                $post = Posts::where('id',$id)->first();
                if (!$post->wordpress()->where('wordpress_id', $request->channel_detail_id)->exists()){
                    $post->wordpress()->attach($request->channel_detail_id, [
                        'sync_status' => 'unSynced',
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        'updated_at' => null]);
                }
                $post_syncs = $post->wordpress;
                $channels = array();
                foreach ($post_syncs as $post_sync){
                    $channels[] = [
                        'id' => $post_sync->id,
                        'name' => $post_sync->name,
                        'sync_status' => $post_sync->pivot->sync_status,
                        'synced_at' => null,
                    ];
                }
                return response()->json([
                    'postDetails' => $channels,
                    'status' => 'Success'
                ], 200);
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

    public function postsChannelsSync(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'notified_by' => 'required|integer',
                'posts_id' => 'required|array',
            ]);
            foreach ($request->posts_id as $post_id){
                $this->postsWordpressSync($request->notified_by, $post_id);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'Posts are added to jobs. Will be notified if they are done!!!',
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Method allow to sync the posts through JOBS running in queues.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function postsChannelsSyncById(Request $request, $id):JsonResponse
    {
        try {
            $request->validate([
                'notified_by' => 'required|integer',
            ]);
            $this->postsWordpressSync($request->notified_by, $id);
            return response()->json([
                'status' => 'Success',
                'message' => 'Posts are added to jobs. Will be notified if they are done!!!',
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function postsWordpressSync($users_id, $posts_id):void
    {
        $posts = Posts::where('id',$posts_id)->first();
        $posts_wordpress = $posts->wordpress;
        if (!empty($posts_wordpress)){
            foreach ($posts_wordpress as $post_wordpress) {
                $post_details = $posts->wordpress()->where('wordpress_id', $post_wordpress->id)->first();
                $posts->wordpress()->updateExistingPivot($post_wordpress->id, ['sync_status' => 'processing']);
                $this->postsJobDispatch($users_id, $post_wordpress, $posts_id);
            }
        }
    } // End Function

    /**
     * Method allow to Dispatch the job and trigger the event listener.
     * @param $users_id
     * @param $post_wordpress
     * @param $posts_id
     */
    public function postsJobDispatch($users_id, $post_wordpress, $posts_id):void
    {
        $posts = Posts::where('id',$posts_id)->first();
        // insert the Notification.
        $notifications_id = DB::table('notifications')->insertGetId([
            'data_id' => $posts_id,
            'data_name' => $posts->title,
            'notification_type' => 'posts_sync',
            'data_channel' => $post_wordpress->name,
            'status' => 'processing',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        // create the notification for the job
        $user = User::where('id', $users_id)->first();
        // attach the user for the notification
        $user->notifications()->attach($notifications_id, ['created_at' => Carbon::now()->format('Y-m-d H:i:s')]);
        // Run the Job
        PostsWordpressSync::dispatch($post_wordpress->id, $posts_id, $notifications_id, $user->id)->delay(now()->addSeconds(5));
    } // End Function

    /**
     * Method allow to sync the post or posts to the WordPress
     * @param $site_id
     * @param $post_id
     * @throws Exception
     */
    public function postsSync($site_id, $post_id)
    {
        try {
            if (Wordpress::where('id',$site_id)->exists()) {
                $wordpress = Wordpress::where('id',$site_id)->first();
                $authentication = $this->authenticateUserById($wordpress->id);
                if ($authentication) {
                    if (Posts::where('id', $post_id)->exists()) {
                        if ($wordpress->posts()->where('id', $post_id)->exists()) {
                            $existing_post = $this->updateExistingPost($post_id, $wordpress->id);
                            if ($existing_post->getStatusCode() == 200) {
                                return response()->json([
                                    'status' => 'Success',
                                    'message' => 'The Post had synced with latest updates'
                                ], 200);
                            } else {
                                return response()->json([
                                    'status' => 'Error',
                                    'message' => $existing_post->getData()->message,
                                ], $existing_post->getStatusCode());
                            }
                        } else {
                            $new_post = $this->syncNewPost($post_id, $wordpress->id);
                            if ($new_post->getStatusCode() == 200) {
                                return response()->json([
                                    'status' => 'Success',
                                    'message' => 'The Post had synced as a new updates'
                                ], 200);
                            } else {
                                return response()->json([
                                    'status' => 'Error',
                                    'message' => $new_post->getData()->message,
                                ], $new_post->getStatusCode());
                            }
                        }
                    } else {
                        return response()->json([
                            'status' => 'No Content',
                            'message' => 'There is no relevant information for selected query'
                        ], 210);
                    }
                } else {
                    return response()->json([
                        'status' => 'Unauthorized',
                        'message' => 'Please check the credentials of Wordpress site.',
                    ], 401);
                }
            } else{
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
     * Method allow to create the post if not existing in the Wordpress.
     * @param $post_id
     * @param $wordpress_id
     */
    public function syncNewPost($post_id, $wordpress_id)
    {
        try {
            $wordpress = Wordpress::where('id',$wordpress_id)->first();
            $post = Posts::withTrashed()->where('id', $post_id)->first();
            $post_details = $this->postDetails($post_id, $wordpress->id);
            $this->authenticateUserById($wordpress->id);
            $post_id = json_decode($this->client->request(
                'POST',
                $wordpress->site_url . '/wp-json/wp/v2/posts',
                ['headers' => $this->headers, 'form_params' => $post_details])
                ->getBody())->id;
            // Save the data as relation for next usage
            $wp_post_details = [
                'wp_post_id' => $post_id,
                'sync_status' => 'synced',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];
            $wordpress->posts()->updateExistingPivot($post->id, $wp_post_details);
            return response()->json([
                'status' => 'Success',
                'message' => 'The Post had synced with as a new and attached at our system'
            ], 200);
        } catch (GuzzleException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } //End Function

    /**
     * Method allow updating if the post is already existing in the WordPress
     * @param $post_id
     * @param $wordpress_id
     */
    public function updateExistingPost($post_id, $wordpress_id)
    {
        try {
            $wordpress = Wordpress::where('id',$wordpress_id)->first();
            $post = Posts::where('id', $post_id)->first();
            $post_data = $wordpress->posts()->where('id', $post_id)->first();
            $post_details = $this->postDetails($post_id, $wordpress->id);
            $this->authenticateUserById($wordpress->id);
            if ($post_data->pivot->wp_post_id == null){
                $post_id = json_decode($this->client->request(
                    'POST',
                    $wordpress->site_url . '/wp-json/wp/v2/posts',
                    ['headers' => $this->headers, 'form_params' => $post_details])
                    ->getBody())->id;
                // Save the data as relation for next usage
                $wp_post_details = [
                    'wp_post_id' => $post_id,
                    'sync_status' => 'synced',
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ];
            } else {
                $existing_post = json_decode($this->client->request(
                    'GET',
                    $wordpress->site_url . '/wp-json/wp/v2/posts/' . $post_data->pivot->wp_post_id,
                    ['headers' => $this->headers])
                    ->getStatusCode());
                if ($existing_post == 200) {
                    $post_id = json_decode($this->client->request(
                        'POST',
                        $wordpress->site_url . '/wp-json/wp/v2/posts/' . $post_data->pivot->wp_post_id . '/',
                        ['headers' => $this->headers, 'form_params' => $post_details])
                        ->getBody())->id;
                    // Save the data as relation for next usage
                    $wp_post_details = [
                        'wp_post_id' => $post_id,
                        'sync_status' => 'synced',
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ];
                } else {
                    $wordpress->posts()->detach($post_id);
                    $this->syncNewPost($post_id, $wordpress_id);
                }
            }
            $wordpress->posts()->updateExistingPivot($post->id, $wp_post_details);
            return response()->json([
                'status' => 'Success',
                'message' => 'The Post had synced with all the latest updates'
            ], 200);
        } catch (GuzzleException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function


    /**
     * Method allow to set the array of post details for he parameters
     * @param $id
     * @param $wordpress_id
     * @return array
     */
    public function postDetails($id, $wordpress_id):array
    {
        $wordpress = Wordpress::where('id',$wordpress_id)->first();
        $post = Posts::withTrashed()->where('id',$id)->first();

        // Assign the Media for the Posts
        if ($post->post_file_id != null) {
            $media_sync = new FileSyncController();
            $media_sync->MediaSyncNew($wordpress->id, $post->post_file_id, $id);
            if ($wordpress->files()->where('id',$post->post_file_id)->exists()) {
                $wordpress_media = $wordpress->files()->where('files_id',$post->post_file_id)->first();
                $featured_media = (int)$wordpress_media->pivot->wp_file_id;
            } else {
                $featured_media = null;
            }
        } else {
            $featured_media = null;
        }

        // Assign the Categories for the Posts
        $post_cats = array();
        $categories = array();
        $post_categories =  $post->categories()->get();
        foreach ($post_categories as $post_category){
            $post_cats[] = $post_category->id;
        }
        if (!empty($post_cats)) {
            $categories_sync = new CategorySyncController();
            $categories_sync->categoriesSyncNew($wordpress_id, $post_cats);
            foreach ($post_cats as $post_cat) {
                if ($wordpress->categories()->where('categories_id', $post_cat)->exists()) {
                    $wordpress_category_details = $wordpress->categories()->where('categories_id', $post_cat)->first();
                    $categories[] = $wordpress_category_details->pivot->wp_category_id;
                }
            }
        }

        // Assign the tags for the Posts
        $tags = array();
        $post_tag_ids = array();
        $post_tags = $post->tags()->get();
        foreach ($post_tags as $post_tag){
            $post_tag_ids[] = $post_tag->id;
        }
        if (!empty($post_tag_ids)) {
            $categories_sync = new TagSyncController();
            $categories_sync->tagsSyncNew($wordpress_id, $post_tag_ids);
            foreach ($post_tag_ids as $post_tag_id) {
                if ($wordpress->tags()->where('tags_id', $post_tag_id)->exists()) {
                    $wordpress_tag_details = $wordpress->tags()->where('tags_id', $post_tag_id)->first();
                    $tags[] = $wordpress_tag_details->pivot->wp_tag_id;
                }
            }
        }

        // Change the status according to the WordPress naming convention
        if ($post->status_id == 1){
            $post_status = 'draft';
        } elseif ($post->status_id == 2) {
            $post_status = 'publish';
        } elseif ($post->status_id == 3) {
            $post_status = 'future';
        } else {
            $post_status = 'pending';
        }

        // Append the final data to the Array to return to WordPress
        $wp_post_details = [
            'title' => $post->title,
            'content' => $post->introduction . $post->description,
            'status' => $post_status,
            'date' => $post->published_at,
            'slug' => $post->seo_permalink,
            'format' => $post->post_type,
            'featured_media' => $featured_media,
            'categories' => $categories,
            'tags' => $tags,
        ];
        return $wp_post_details;
    } // End Function

    /**
     * Method allow to detach the posts from the Wordpress
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function postsChannelsDetach(Request $request, $id):JsonResponse
    {
        try {
            $request->validate([
                'channel_detail_id' => 'required|integer',
            ]);
            $un_sync = $this->destroySyncPost($id, $request->channel_detail_id);
            if ($un_sync){
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Post is UnSynced successfully',
                ], 200);
            } else{
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
    }

    /**
     * Method allow to Delete the posts permanently from the wordpress sites.
     * @param $post_id
     * @param $wordpress_id
     */
    public function destroySyncPost($post_id, $wordpress_id)
    {
        try {
            $wordpress_site = Wordpress::where('id',$wordpress_id)->first();
            $post_data = $wordpress_site->posts()->where('posts_id', $post_id)->first();
            $wordpress_site->posts()->detach($post_id);
            if ($post_data->pivot->wp_post_id != null) {
                $authentication = $this->authenticateUserById($wordpress_site->id);
                $post_details = json_decode($this->client->request(
                    'DELETE',
                    $wordpress_site->site_url . '/wp-json/wp/v2/posts/' . $post_data->pivot->wp_post_id . '?force=true',
                    ['headers' => $this->headers, 'form_params'])
                    ->getBody());
            }
            return true;
        } catch (GuzzleException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to retrieve the callbackURL from the database
     * @param Request $request
     * @param $id
     * @return  JsonResponse
     * @throws GuzzleException
     */
    public function getShareableURL(Request $request, $id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()) {
                $posts = Posts::where('id', $id)->first();
                $wordpress = Wordpress::where('id',$request->wordpress_id)->first();
                $posts_wordpress = $posts->wordpress()->where('wordpress_id', $request->wordpress_id)->first();
                if ($posts_wordpress->pivot->wp_post_id != null){
                    $this->authenticateUserById($wordpress->id);
                    $post_link = json_decode($this->client->request(
                        'GET',
                        $wordpress->site_url . '/wp-json/wp/v2/posts/' . $posts_wordpress->pivot->wp_post_id . '/',
                        ['headers' => $this->headers])
                        ->getBody())->link;
                    return response()->json([
                        'callbackURL' => $post_link,
                        'status' => 'Success',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Please sync it, before fetching the callback URL'
                    ],422);
                }
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

}

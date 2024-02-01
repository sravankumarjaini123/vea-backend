<?php

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Categories\CategorySyncController;
use App\Http\Controllers\Controller;
use App\Models\FoldersFiles;
use App\Models\LegalTextsSettings;
use App\Models\Posts;
use App\Models\PostsStatistics;
use App\Models\Status;
use App\Models\User;
use App\Models\Wordpress;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Str;
use Nette\Schema\ValidationException;

class PostController extends Controller
{
    /**
     * Method allow to display list of all active posts.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'search_keyword' => 'nullable|string|min:3|max:200',
                'limit' => 'nullable|in:10,12,20,30,50'
            ]);
            if ($request->limit == null){
                $limit = 10;
            } else {
                $limit = (int)$request->limit;
            }
            $keyword = $request->search_keyword;
            // Get the Published Posts Details
            $posts_publish_details = DB::table('posts')->where('status_id','=',2);
            $posts_publish = $this->searchKeywordDetails($posts_publish_details, $keyword);
            // Get the Scheduled Posts Details
            $posts_scheduled_details = DB::table('posts')->where('status_id', '=', 3);
            $posts_scheduled = $this->searchKeywordDetails($posts_scheduled_details, $keyword);
            // Get the Drafted Details
            $posts_draft_details = DB::table('posts')->where('status_id', '=', 1);
            $posts_draft = $this->searchKeywordDetails($posts_draft_details, $keyword);
            // Get Inactive Details
            $posts_inactive_details = DB::table('posts')->where('status_id', '=', 4);
            $posts_inactive = $this->searchKeywordDetails($posts_inactive_details, $keyword);
            // Overview of all the Posts
            $overview = $this->getOverviewUponSelection($posts_scheduled, $posts_publish, $posts_draft, $posts_inactive, $limit);
            return response()->json([
                'posts' => $overview->getData()->posts,
                'posts_pagination' => $overview->getData()->posts_pagination,
                'message' => 'Success',
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getLine(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to retrieve the details of the posts with pagination details
     * @param $posts_scheduled // Raw data of the Scheduled Posts
     * @param $posts_publish // Raw data of the Published Posts
     * @param $posts_draft // Raw data of the Drafted Posts
     * @param $posts_inactive // Raw data of the Inactive Posts
     * @param $limit // Maximum number of items for the page to display
     * @return JsonResponse
     * @throws Exception
     */
    public function getOverviewUponSelection($posts_scheduled, $posts_publish, $posts_draft, $posts_inactive, $limit):JsonResponse
    {
        try {
            $array = array_merge($posts_scheduled, $posts_publish, $posts_draft, $posts_inactive);
            $total_count = count($array);
            $posts_details = array();
            $pagination_final = array();
            if ($total_count > 0) {
                $posts = Posts::whereIn('id', $array)->orderByRaw("field(id," . implode(',', $array) . ")");
                $posts_details = $this->getPostListOverview($posts->paginate($limit));
                $pagination_final = $this->getPaginationDetails($posts, $limit, $total_count);
            }
            return response()->json([
                'posts' => $posts_details,
                'posts_pagination' => $pagination_final,
                'message' => 'Success',
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function searchKeywordDetails($details, $keyword)
    {
        if ($keyword != null) {
            $details = $details->where(function ($query) use ($keyword) {
                $query->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('introduction', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%');
            });
        }
        return $details->orderBy('published_at', 'desc')->pluck('id')->toArray();
    } // End Function

    /**
     * Method allow get the list of posts with minimum data
     * @param $posts
     * @return array
     */
    public function getPostListOverview($posts):array
    {
        $post_details = array();
        if (! $posts->isEmpty()) {
            foreach ($posts as $post) {
                if ($post->post_file_id != null) {
                    $post_media = $post->postFile->file_path;
                    $post_media_name = $post->postFile->name;
                    $post_media_copyright_text = $post->postFile->copyright_text;
                    $media_resolutions = $this->getFilesResolutionDetails($post->post_file_id);
                } else {
                    $post_media = null;
                    $post_media_name = null;
                    $post_media_copyright_text = null;
                    $media_resolutions = array();
                }
                if ($post->post_thumbnail_id != null) {
                    $post_thumbnail = $post->postThumbnail->file_path;
                    $post_thumbnail_name = $post->postThumbnail->name;
                    $post_thumbnail_copyright_text = $post->postThumbnail->copyright_text;
                    $thumbnail_resolutions = $this->getFilesResolutionDetails($post->post_thumbnail_id);
                } else {
                    $post_thumbnail = null;
                    $post_thumbnail_name = null;
                    $post_thumbnail_copyright_text = null;
                    $thumbnail_resolutions = array();
                }
                // Master - Data
                $post_group_details = $post->groups;
                $post_category_details = $post->categories;
                $post_tag_details = $post->tags;
                $post_groups = $this->getDetailsForPost($post_group_details);
                $post_categories = $this->getDetailsForPost($post_category_details);
                $post_tags = $this->getDetailsForPost($post_tag_details);

                // Final Array of Post with all details
                $post_details[] = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'post_type' => $post->post_type,
                    'post_media_id' => $post->post_file_id,
                    'post_media_name' => $post_media_name,
                    'post_media' => $post_media,
                    'post_media_resolutions' => $media_resolutions,
                    'post_media_copyright_text' => $post_media_copyright_text,
                    'post_thumbnail_id' => $post->post_thumbnail_id,
                    'post_thumbnail_name' => $post_thumbnail_name,
                    'post_thumbnail' => $post_thumbnail,
                    'post_thumbnail_resolutions' => $thumbnail_resolutions,
                    'post_thumbnail_copyright_text' => $post_thumbnail_copyright_text,
                    'top_post' => $post->top_news,
                    'top_post_expiration' => $post->top_news_expiration,
                    'status_id' => $post->status_id,
                    'status' => $post->status->name,
                    'published_at' => $post->published_at,
                    'inactive_at' => $post->inactive_at,
                    'groups' => $post_groups,
                    'categories' => $post_categories,
                    'tags' => $post_tags,
                    'updated_at' => $post->updated_at,
                ];
            }
        }
        return $post_details;
    }

    /**
     * Method allow get the list of single post of all posts.
     * @param $posts
     * @param null $user_id
     * @return array
     * @throws ValidationException
     */
    public function getPostList($posts, $user_id = null, $channel = 'backend'): array
    {
        $post_details = array();
        if (!empty($posts)) {
            foreach ($posts as $post) {
                if ($post->post_file_id != null) {
                    $post_media = $post->postFile->file_path;
                    $post_media_name = $post->postFile->name;
                    $post_media_copyright_text = $post->postFile->copyright_text;
                    $media_resolutions = $this->getFilesResolutionDetails($post->post_file_id);
                    $post_media_duration = $post->postFile->duration;
                } else {
                    $post_media = null;
                    $post_media_name = null;
                    $post_media_copyright_text = null;
                    $media_resolutions = array();
                    $post_media_duration = null;
                }
                if ($post->post_thumbnail_id != null) {
                    $post_thumbnail = $post->postThumbnail->file_path;
                    $post_thumbnail_name = $post->postThumbnail->name;
                    $post_thumbnail_copyright_text = $post->postThumbnail->copyright_text;
                    $thumbnail_resolutions = $this->getFilesResolutionDetails($post->post_thumbnail_id);
                } else {
                    $post_thumbnail = null;
                    $post_thumbnail_name = null;
                    $post_thumbnail_copyright_text = null;
                    $thumbnail_resolutions = array();
                }

                // Master - Data
                $post_group_details = $post->groups;
                $post_category_details = $post->categories;
                $post_tag_details = $post->tags;
                $post_authors_details = $post->authors;

                $post_groups = $this->getDetailsForPost($post_group_details);
                $post_categories = $this->getDetailsForPost($post_category_details);
                $post_tags = $this->getDetailsForPost($post_tag_details);
                $post_authors = $this->getDetailsForPostAuthor($post_authors_details);

                // Media
                if (empty($post->media)) {
                    $media = [];
                } else {
                    $files = FoldersFiles::whereIn('id', $post->media)->get();
                    $media = $this->getPostsFileManagerDetails($files);
                }

                // Galleries
                $galleries = array();
                if (!empty($post->galleries)) {
                    foreach ($post->galleries as $gall){
                        $files = FoldersFiles::where('id', $gall)->get();
                        $result = $this->getPostsFileManagerDetails($files);
                        $galleries = array_merge($galleries, $result);
                    }
                }

                // Channels
                $wordpress_channels = $this->getWordpressDetails($post->wordpress);
                $twitter_details = $this->getTwitterDetails($post->twitter);
                $linkedIn_details = $this->getLinkedInDetails($post->linkedIn);

                // Related Posts
                if (empty($post->related_posts)) {
                    $related_posts = [];
                } else {
                    $related_posts_details = Posts::whereIn('id', $post->related_posts)
                        ->where('status_id', 2)
                        ->orderBy('published_at', 'DESC')
                        ->get();
                    $related_posts = $this->getRelatedPostsDetails($related_posts_details, $channel);
                }

                // Final Array of Post with all details
                $post_details[] = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'introduction' => $post->introduction,
                    'description' => $post->description,
                    'post_type' => $post->post_type,
                    'post_media_id' => $post->post_file_id,
                    'post_media_name' => $post_media_name,
                    'post_media' => $post_media,
                    'post_media_duration' => $post_media_duration,
                    'post_media_resolutions' => $media_resolutions,
                    'post_media_copyright_text' => $post_media_copyright_text,
                    'post_thumbnail_id' => $post->post_thumbnail_id,
                    'post_thumbnail_name' => $post_thumbnail_name,
                    'post_thumbnail' => $post_thumbnail,
                    'post_thumbnail_resolutions' => $thumbnail_resolutions,
                    'post_thumbnail_copyright_text' => $post_thumbnail_copyright_text,
                    'top_post' => $post->top_news,
                    'top_post_expiration' => $post->top_news_expiration,
                    'visible_as_post' => $post->visible_as_post,
                    'seo_tag' => $post->seo_tag,
                    'seo_permalink' => $post->seo_permalink,
                    'seo_description' => $post->seo_description,
                    'status_id' => $post->status_id,
                    'status' => $post->status->name,
                    'published_at' => $post->published_at,
                    'inactive_at' => $post->inactive_at,
                    'shareable_post' => $post->shareable_posts,
                    'shareable_type' => $post->shareable_type,
                    'shareable_description' => $post->shareable_description,
                    'shareable_callback_url' => $post->shareable_callback_url,
                    'groups' => $post_groups,
                    'categories' => $post_categories,
                    'tags' => $post_tags,
                    'authors' => $post_authors,
                    'media' => $media,
                    'related_posts' => $related_posts,
                    'galleries' => $galleries,
                    'wordpress_channels' => $wordpress_channels ?? array(),
                    'twitter_details' => $twitter_details ?? array(),
                    'linkedIn_details' => $linkedIn_details ?? array(),
                    'updated_at' => $post->updated_at,
                ];
            }
        }
        return $post_details;
    } // End Function

    public function getRelatedCoursesDetails($related_courses, $channel = '')
    {
        $courses = new CourseController();
        $result_array = $courses->getCourseDetails($related_courses, $channel, 'overview');
        return $result_array;
    }

    /**
     * Method allow to check whether the posts or courses are bought
     */
    public function checkForBought($user_id, $stripe_active, $data_type, $data, $data_product_details, $data_subscription_details):bool
    {
        $is_bought = 0;
        $user = User::where('id', $user_id)->first();
        if ($data_type === 'posts') {
            $condition = 'posts_id';
        } else {
            $condition = 'courses_id';
        }
        if ($user->sys_admin == 0 && $user->sys_customer == 1) {
            // Products
            if (!empty($data_product_details)) {
                $user_products = $user->stripesProducts()
                    ->where($condition, $data->id)->first();
                if (!empty($user_products)) {
                    if ($data->stripesProducts()->where('stripes_id', $stripe_active->id)->wherePivot('stripes_products_id', $user_products->pivot->stripes_products_id)->exists()) {
                        $is_bought = 1;
                    }
                }
            }
        } else {
            $is_bought = 1;
        }
        if ($is_bought == 0) {
            // Subscriptions
            if (!empty($data_subscription_details)) {
                $user_subscriptions = $user->stripesSubscriptions()->WherePivot('is_expired', 0)->get();
                foreach ($user_subscriptions as $user_subscription) {
                    $stripes_product = StripesProducts::where('id', $user_subscription->pivot->stripes_products_id)->where('stripes_id', $stripe_active->id)->first();
                    if (!empty($stripes_product)) {
                        if ($stripes_product->subscriptionContent()->where('name', $data_type)->where(function ($q) use ($data, $condition) {
                            $q->where('type', 'all')
                                ->orWhere($condition, $data->id);
                        })->exists()) {
                            $is_bought = 1;
                            break;
                        }
                    }
                }
            }
        }
        if ($data_product_details->isEmpty() && $data_subscription_details->isEmpty() && $user_id == null) {
            $is_bought = 1;
        }
        return $is_bought;
    } // End Function

    /**
     * Method allow to retrieve the subscription details for posts and events
     */
    public function getSubscriptionDetailsForData($name, $data_subscription_details, $stripe_active):array
    {
        $subscriptions = array();
        $all_subscriptions = StripesSubscriptionsContents::where('name', $name)->where('type', 'all')->get();
        if (!empty($all_subscriptions)){
            foreach ($all_subscriptions as $all_subscription){
                $sub_product_details = StripesProducts::where('id', $all_subscription->stripes_products_id)->where('stripes_id', $stripe_active->id)->get();
                $details = $this->getProductDetails($sub_product_details);
                if (!empty($details)) {
                    foreach ($details as $detail) {
                        $subscriptions[] = $detail;
                    }
                }
            }
        }
        if (!empty($data_subscription_details)) {
            foreach ($data_subscription_details as $data_subscription_detail) {
                $product_details = StripesProducts::where('id', $data_subscription_detail->stripes_products_id)->where('stripes_id', $stripe_active->id)->get();
                $subscription_details = $this->getProductDetails($product_details);
                if (!empty($subscription_details)) {
                    foreach ($subscription_details as $subscription_detail) {
                        $subscriptions[] = $subscription_detail;
                    }
                }
            }
        }
        return $subscriptions;
    } // End Function

    /**
     * Method allow to retrieve the Word Press details of the Posts
     */
    public function getWordpressDetails($wordpress_sites): array
    {
        $channels = array();
        if (!empty($wordpress_sites)) {
            foreach ($wordpress_sites as $wordpress_site) {
                $channels[] = [
                    'id' => $wordpress_site->id,
                    'name' => $wordpress_site->name,
                    'sync_status' => $wordpress_site->pivot->sync_status,
                    'synced_at' => $wordpress_site->pivot->updated_at,
                ];
            }
        }
        return $channels;
    } // End Function

    /**
     * Method allow to retrieve the twitter details of the Posts
     */
    public function getTwitterDetails($twitters):array
    {
        $twitter_details = array();
        if (!empty($twitters)){
            foreach ($twitters as $twitter){
                $twitter_details[] = [
                    'id' => $twitter->id,
                    'account_name' => $twitter->app_name,
                    'text' => $twitter->pivot->text,
                    'twitter_post_id' => $twitter->pivot->twitter_post_id,
                    'tweeted_by' => $twitter->pivot->tweeted_by,
                    'retweeted' => $twitter->pivot->retweeted,
                    'retweeted_by' => $twitter->pivot->retweeted_by,
                    'disconnected' => $twitter->pivot->disconnected,
                    'user_id' => $twitter->pivot->users_id,
                    'user_twitter_id' => $twitter->pivot->users_twitters_id,
                ];
            }
        }
        return $twitter_details;
    } // End Function

    /**
     * Method allow to retrieve the LinkedIn details of the Posts
     */
    public function getLinkedInDetails($linkedIns):array
    {
        $linkedIn_details = array();
        if (!empty($linkedIns)){
            foreach ($linkedIns as $linkedIn){
                $linkedIn_details[] = [
                    'id' => $linkedIn->id,
                    'account_name' => $linkedIn->app_name,
                    'content_type' => $linkedIn->pivot->content_type,
                    'share_type' => $linkedIn->pivot->share_type,
                    'shared_by' => $linkedIn->pivot->shared_by,
                    'subject' => $linkedIn->pivot->subject,
                    'body' => $linkedIn->pivot->body,
                    'visibility' => $linkedIn->pivot->visibility,
                    'media_id' => $linkedIn->pivot->media_id,
                    'external_url' => $linkedIn->pivot->external_url,
                    'reshared' => $linkedIn->pivot->reshared,
                    'reshared_by' => $linkedIn->pivot->reshared_by,
                    'disconnected' => $linkedIn->pivot->disconnected,
                ];
            }
        }
        return $linkedIn_details;
    } // End Function

    /**
     * Method allow to retrieve the Stripes Products Details
     */
    public function getProductDetails($details):array
    {
        $result_array = array();
        if (!empty($details)) {
            foreach ($details as $detail) {
                $legal_details_controllers = new StripeProductController();
                $legal_texts = $legal_details_controllers->getLegalTextsDetails($detail);
                if ($detail->images_id != null) {
                    $image_url = $detail->mainImage->file_path;
                } else {
                    $image_url = null;
                }
                $result_array[] = [
                    'id' => $detail->id,
                    'name' => $detail->name,
                    'license_type' => $detail->licenses_types,
                    'quantity' => $detail->licenses_types_quantity,
                    'description' => $detail->description,
                    'display_description' => $detail->display_description,
                    'image_url' => $image_url,
                    'assigned_to' => $detail->assigned_to,
                    'price' => $detail->price,
                    'price_currency' => $detail->price_currency,
                    'price_tax_behavior' => $detail->price_tax_behavior,
                    'price_interval' => $detail->price_interval,
                    'price_interval_count' => (int)$detail->price_interval_count,
                    'google_product_id' => $detail->google_products_id,
                    'google_product_price' => $detail->google_products_price,
                    'apple_product_id' => $detail->apple_products_id,
                    'apple_product_price' => $detail->apple_products_price,
                    'legal_texts' => $legal_texts,
                ];
            }
        }
        return $result_array;
    } // End Function

    /**
     * Method allow to store new posts.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required',
                'post_type' => 'required|in:image,video,audio',
            ]);
            $post_id = DB::table('posts')->insertGetId([
                'title' => $request->title,
                'post_type' => $request->post_type,
                'status_id' => 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            $post = Posts::where('id', $post_id)->first();
            $convertedString = $this->convertToEnglish($post->title);
            $permalink = Str::slug($convertedString, '-');
            $seo_title = substr($request->title,0,60);
            $post->seo_permalink = $permalink;
            $post->seo_tag = $seo_title;
            $post->save();

            $post_result = Posts::where('id', $post_id)->get();
            $post_details_arrays = $this->getPostList($post_result);
            $post_details = array();
            foreach ($post_details_arrays as $post_details_array) {
                $post_details = $post_details_array;
            }

            if (!empty($post_details)) {
                return response()->json([
                    'postDetails' => $post_details,
                    'status' => 'Success',
                    'message' => 'Post is created successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'There is some error in creating the database, please try again later'
                ], 400);
            }

        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to duplicate a particular post.
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function duplicatePosts($id):JsonResponse
    {
        try {
            $post_details = Posts::where('id', $id)->first();
            if(!empty($post_details)) {
                $data = [
                    'title' => 'COPY: /' . $post_details->title,
                    'status_id' => 1,
                    'published_at' => null,
                    'inactive_at' => null,
                    'introduction' => $post_details->introduction,
                    'description' => $post_details->description,
                    'post_type' => $post_details->post_type,
                    'post_file_id' => $post_details->post_file_id,
                    'post_thumbnail_id' => $post_details->post_thumbnail_id,
                    'top_news' => $post_details->top_news,
                    'top_news_expiration' => $post_details->top_news_expiration,
                    'media' => $post_details->media,
                    'galleries' => $post_details->galleries,
                    'related_posts' => $post_details->related_posts,
                    'shareable_posts' => $post_details->shareable_posts,
                    'shareable_type' => $post_details->shareable_type,
                    'shareable_description' => $post_details->shareable_description,
                    'shareable_callback_url' => $post_details->shareable_callback_url,
                    'seo_tag' => $post_details->seo_tag,
                    'seo_permalink' => $post_details->seo_permalink,
                    'seo_description' => $post_details->seo_permalink,
                ];
                $store_post = Posts::insert($data);
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Post is created successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }

        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } //End function

    /**
     * Method allow to show a particular post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function show($id, Request $request): JsonResponse
    {
        try {

            $request->validate([
                'channel' => 'required|in:web,ios,android,backend',
                'contacts' => 'nullable|integer'
            ]);

            if (Posts::where('id', $id)->exists()) {

                // SAVE STATISTIC AND IGNORE BACKEND CALLS
                if ($request->channel != 'backend') {
                    $data_posts_statistics = new PostsStatistics();
                    $data_posts_statistics->posts_id = $id;
                    $data_posts_statistics->channel = $request->channel;
                    $data_posts_statistics->save();
                }

                // LOAD POST
                $posts = Posts::where('id', $id)->get();
                $post_details_arrays = $this->getPostList($posts, $request->contacts, $request->channel);
                $post_details = array();
                foreach ($post_details_arrays as $post_details_array) {
                    $post_details = $post_details_array;
                }
                return response()->json([
                    'postDetails' => $post_details,
                    'message' => 'Success',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function getStatus(): JsonResponse
    {
        try {
            $status = Status::all();
            return response()->json([
                'status' => $status,
                'message' => 'Success',
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to update the general details of the post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateGeneral(Request $request, $id): JsonResponse
    {
        try {
            if (Posts::where('id', $id)->exists()) {
                $post = Posts::where('id', $id)->first();
                $request->validate([
                    'title' => 'required|string|max:255',
                ]);
                if ($post->seo_tag == null){
                    $seo_tag = mb_substr($request->title,0,60);
                } else {
                    $seo_tag = $post->seo_tag;
                }
                if ($post->seo_description == null || $post->shareable_description == null){
                    if ($request->introduction != null){
                        $seo_description = mb_substr(strip_tags($request->introduction),0, 200);
                        $shareable_description = mb_substr(strip_tags($request->introduction),0, 300);
                    } else {
                        if ($request->description != null){
                            $seo_description = mb_substr(strip_tags($request->description),0, 200);
                            $shareable_description = mb_substr(strip_tags($request->introduction),0, 300);
                        } else {
                            $seo_description = null;
                            $shareable_description = null;
                        }
                    }
                } else {
                    $seo_description = $post->seo_description;
                    $shareable_description = $post->shareable_description;
                }

                $post->title = $request->title;
                $post->introduction = $request->introduction;
                $post->description = $request->description;
                $convertedString = $this->convertToEnglish($post->title);
                $permalink = Str::slug($convertedString, '-');
                $post->seo_tag = $seo_tag;
                $post->seo_description = $seo_description;
                $post->seo_permalink = $permalink;
                $post->shareable_description = $shareable_description;
                $post->save();

                $post_details = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'introduction' => $post->introduction,
                    'description' => $post->description,
                ];
                return response()->json([
                    'postDetails' => $post_details,
                    'seo_tag' => $post->seo_tag,
                    'seo_permalink' => $post->seo_permalink,
                    'seo_description' => $post->seo_description,
                    'shareable_description' => $post->shareable_description,
                    'status' => 'Success',
                    'message' => 'Post is updated successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to update the Media details of the Post
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateMedia(Request $request, $id): JsonResponse
    {
        try {
            if (Posts::where('id', $id)->exists()) {
                Posts::where('id', $id)->update([
                    'media' => $request->media,
                ]);
                $post = Posts::where('id', $id)->first();
                $media = array();
                if (!empty($post->media)) {
                    $files = FoldersFiles::whereIn('id', $post->media)->get();
                    $media = $this->getPostsFileManagerDetails($files);
                }
                return response()->json([
                    'postDetails' => $media,
                    'status' => 'Success',
                    'message' => 'Media is updated successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End function

    /**
     * Method allow to get the details of the Folders Files attached to post
     * @param $details
     * @return array
     */
    public function getPostsFileManagerDetails($details):array
    {
        $result_array = array();
        if (!empty($details)){
            foreach ($details as $detail){
                $resolutions = $this->getFilesResolutionDetails($detail->id);
                $result_array[] = [
                    'id' => $detail->id,
                    'name' => $detail->name,
                    'size' => $detail->size,
                    'type' => $detail->type,
                    'file_path' => $detail->file_path,
                    'resolutions' => $resolutions
                ];
            }
        }
        return $result_array;
    } // End Function

    /**
     * Method allow to update the Galleries for the Post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateGalleries(Request $request, $id): JsonResponse
    {

        try {
            if (Posts::where('id', $id)->exists()) {
                Posts::where('id', $id)->update([
                    'galleries' => $request->galleries,
                ]);
                $post = Posts::where('id', $id)->first();
                $result = array();
                if (!empty($post->galleries)) {
                    foreach ($post->galleries as $gall){
                        $files = FoldersFiles::where('id', $gall)->get();
                        $galleries = $this->getPostsFileManagerDetails($files);
                        $result = array_merge($result, $galleries);
                    }
                }
                return response()->json([
                    'postDetails' => $result,
                    'status' => 'Success',
                    'message' => 'Gallery is updated successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End function

    /**
     * Method allow to attach the related posts for the particular post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateRelatedPosts(Request $request, $id): JsonResponse
    {
        try {
            if (Posts::where('id', $id)->exists()) {
                $request->validate([
                    'related_posts' => 'json',
                ]);
                if (!in_array($id, json_decode($request->related_posts))) {
                    Posts::where('id', $id)->update([
                        'related_posts' => $request->related_posts,
                    ]);
                    $post = Posts::where('id', $id)->first();
                    $related_posts = array();
                    if (!empty($post->related_posts)) {
                        $posts = Posts::whereIn('id', $post->related_posts)
                            ->where('status_id', 2)
                            ->orderBy('published_at', 'DESC')
                            ->get();
                        $related_posts = $this->getRelatedPostsDetails($posts);
                    }
                    return response()->json([
                        'postDetails' => $related_posts,
                        'status' => 'Success',
                        'message' => 'Related Posts had been attached successfully'
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Cannot attach the related post to the respective post'
                    ], 422);
                }
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End function

    /**
     * Method allow to attach the related events for the particular post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateRelatedEvents(Request $request, $id): JsonResponse
    {
        try {
            if (Posts::where('id', $id)->exists()) {
                $request->validate([
                    'related_events' => 'json',
                ]);
                Posts::where('id', $id)->update([
                    'related_events' => $request->related_events,
                ]);
                $posts = Posts::where('id', $id)->first();
                $related_events = array();
                if (!empty($posts->related_events)) {
                    $events = Events::whereIn('id', json_decode($posts->related_events))
                        ->get();
                    $get_events = new EventsController();
                    $related_events = $get_events->getEventList($events);
//                        $related_events = $this->getRelatedEventsDetails($events);
                }
                return response()->json([
                    'eventsDetails' => $related_events,
                    'status' => 'Success',
                    'message' => 'Related Events had been attached successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End function

    /**
     * Method allow to attach the related courses for the particular post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateRelatedCourses(Request $request, $id): JsonResponse
    {
        try {
            if (Posts::where('id', $id)->exists()) {
                $request->validate([
                    'related_courses' => 'json',
                ]);
                Posts::where('id', $id)->update([
                    'related_courses' => $request->related_courses,
                ]);
                $posts = Posts::where('id', $id)->first();
                $related_courses = array();
                if (!empty($posts->related_courses)) {
                    $courses = Courses::whereIn('id', json_decode($posts->related_courses))
                        ->get();
                    $related_courses = $this->getRelatedCoursesDetails($courses);
                }
                return response()->json([
                    'coursesDetails' => $related_courses,
                    'status' => 'Success',
                    'message' => 'Related Events had been attached successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End function

    /**
     * Method allow to get the details of the posts attached to the respective post
     * @param $posts
     * @return array
     */
    public function getRelatedEventsDetails($events):array
    {
        $result_details = array();
        if (!empty($events)){
            foreach ($events as $event){
                $result_details[] = [
                    'id' => $event->id,
                    'event_access_id' => $event->accessors_id,
                    'event_access_name' => $event->eventAccess->name,
                    'event_mode_id' => $event->events_modes_id,
                    'event_mode_name' => $event->eventMode->name,
                    'status_id' => $event->status_id,
                    'status_name' => $event->status->name,
                    'event_state' => $event->events_state,
                    'event_state_url' => $event->events_state_url,
                    'is_visible' => $event->is_visible,
                    'title' => $event->title,
                    'sub_title' => $event->sub_title,
                    'description' => $event->description,
                    'micropage_id' => $event->micropages_id,
                    'is_followed_up' => $event->is_followed_up,
                    'is_followed_by' => $event->is_followed_by,
                    'is_main' => $event->is_main,
                    'maximum_capacity' => $event->maximum_capacity,
                    'capacity_remaining' => $event->capacity_remaining,
                    'event_image_id' => $event->event_image_id,
                    'top_event' => $event->top_events,
                    'top_event_expiration' => $event->top_events_expiration,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'registration_start_date' => $event->registration_start_date,
                    'registration_end_date' => $event->registration_end_date,
                    'event_type_id' => $event->events_types_id,
                    'address_1' => $event->address_1,
                    'address_2' => $event->address_2,
                    'zip_code' => $event->zip_code,
                    'city' => $event->city,
                    'country_id' => $event->countries_id,
                    'contact_person_image_id' => $event->host_image_id,
                    'contact_person_name' => $event->contact_person_name,
                    'contact_person_email' => $event->contact_person_email,
                    'contact_person_phone' => $event->contact_person_phone,
                    'contact_person_remarks' => $event->contact_person_remarks,
                    'seo_tag' => $event->seo_tag,
                    'seo_permalink' => $event->seo_permalink,
                    'seo_description' => $event->seo_description,
                ];
            }
        }
        return $result_details;
    } // End Function
    /**
     * Method allow to get the details of the posts attached to the respective post
     * @param $posts
     * @param null $channel
     * @param boolean $is_bought
     * @return array
     */
    public function getRelatedPostsDetails($posts, $channel = null, $is_bought = 0):array
    {
        $result_details = array();
        if (!empty($posts)){
            foreach ($posts as $post){
                $post_media = null;
                $media_resolutions = array();
                if ($post->post_file_id != null) {
                    if ($post->post_type != 'image') {
                        if ($channel == null || $channel == 'backend') {
                            $post_media = $post->postFile->file_path;
                            $media_resolutions = $this->getFilesResolutionDetails($post->post_file_id);
                        } else {
                            if ($is_bought) {
                                $post_media = $post->postFile->file_path;
                                $media_resolutions = $this->getFilesResolutionDetails($post->post_file_id);
                            }
                        }
                    } else {
                        $post_media = $post->postFile->file_path;
                        $media_resolutions = $this->getFilesResolutionDetails($post->post_file_id);
                    }
                    $post_media_duration = $post->postFile->duration;
                } else {
                    $post_media_duration = null;
                }

                if ($post->post_thumbnail_id != null) {
                    $post_thumbnail = $post->postThumbnail->file_path;
                    $thumbnail_resolutions = $this->getFilesResolutionDetails($post->post_thumbnail_id);
                } else {
                    $post_thumbnail = null;
                    $thumbnail_resolutions = array();
                }
                $result_details[] = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'introduction' => $post->introduction,
                    'post_type' => $post->post_type,
                    'post_media_id' => $post->post_file_id,
                    'post_media' => $post_media,
                    'post_media_duration' => $post_media_duration,
                    'post_media_resolutions' => $media_resolutions,
                    'post_thumbnail_id' => $post->post_thumbnail_id,
                    'post_thumbnail' => $post_thumbnail,
                    'post_thumbnail_resolutions' => $thumbnail_resolutions,
                    'published_at' => $post->published_at,
                    'status_id' => $post->status_id,
                    'status' => $post->status->name,
                    'seo_tag' => $post->seo_tag,
                    'seo_permalink' => $post->seo_permalink,
                    'seo_description' => $post->seo_description,
                ];
            }
        }
        return $result_details;
    } // End Function

    /**
     * Method allow to update the status of the post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateFiles(Request $request, $id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()) {
                $post = Posts::where('id',$id)->first();
                $post_details= array();
                if ($post->post_type == 'video' || $post->post_type == 'audio'){
                    $post_details = $this->storeMediaForPost($post->id, $request->file_id, $request->thumbnail_file_id);
                } else {
                    $post_details = $this->storeMediaForPost($post->id, $request->file_id);
                }
                return response()->json([
                    'postDetails' => $post_details,
                    'status' => 'Success',
                    'message' => 'Files are uploaded successfully'
                ], 200);

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

    public function storeMediaForPost($id, $file_id=null, $thumbnail_id=null)
    {
        try {
            $post = Posts::where('id',$id)->first();
            if ($file_id != null) {
                $post->post_file_id = $file_id;
            } else {
                $post->post_file_id = null;
            }
            if ($thumbnail_id != null) {
                $post->post_thumbnail_id = $thumbnail_id;
            } else {
                $post->post_thumbnail_id = null;
            }
            $post->save();
            $post_media_details = Posts::where('id', $id)->first();
            if ($post_media_details->post_file_id != null) {
                $post_media = $post_media_details->postFile->file_path;
                $post_media_name = $post_media_details->postFile->name;
            } else {
                $post_media = null;
                $post_media_name = null;
            }
            if ($post_media_details->post_thumbnail_id != null) {
                $post_thumbnail = $post_media_details->postThumbnail->file_path;
                $post_thumbnail_name = $post_media_details->postThumbnail->name;
            } else {
                $post_thumbnail = null;
                $post_thumbnail_name = null;
            }
            $post_details = [
                'id' => $post_media_details->id,
                'post_media_id' => $post_media_details->post_file_id,
                'post_media_name' => $post_media_name,
                'post_media' => $post_media,
                'post_thumbnail_id' => $post_media_details->post_thumbnail_id,
                'post_thumbnail_name' => $post_thumbnail_name,
                'post_thumbnail' => $post_thumbnail,
            ];
            return $post_details;
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Method allow to update the status of the post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateStatus(Request $request, $id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()) {
                $request->validate([
                    'status_id' => 'required|in:1,2,3,4'
                ]);
                $post = Posts::where('id',$id)->first();
                $post->status_id = $request->status_id;

                if ($post->status_id == 1){
                    if ($post->inactive_at != null){
                        $post->inactive_at = null;
                    }
                    if ($post->published_at != null){
                        $post->published_at = null;
                    }
                } elseif ($post->status_id == 2){
                    $request->validate([
                        'publish_at' => ['required', 'date_format:Y-m-d H:i'],
                    ]);
                    if ($request->inactive_at != null){
                        $request->validate([
                            'publish_at' => ['required', 'date_format:Y-m-d H:i'],
                            'inactive_at' => ['date_format:Y-m-d H:i','after_or_equal:'.$request->publish_at],
                        ]);
                        $post->inactive_at = $request->inactive_at;
                    } else {
                        $post->inactive_at = null;
                    }
                    $post->published_at = $request->publish_at;
                } elseif ($post->status_id == 3) {
                    $request->validate([
                        'publish_at' => ['required', 'date_format:Y-m-d H:i', 'after_or_equal:' . Carbon::now()->format('Y-m-d H:i')],
                    ]);
                    $post->published_at = $request->publish_at;
                    if ($request->inactive_at != null) {
                        $request->validate([
                            'inactive_at' => ['date_format:Y-m-d H:i', 'after_or_equal:' . $request->publish_at],
                        ]);
                        $post->inactive_at = $request->inactive_at;
                    } else {
                        $post->inactive_at = null;
                    }
                }  elseif ($post->status_id == 4){
                    if ($request->inactive_at != null){
                        $request->validate([
                            'inactive_at' => ['date_format:Y-m-d H:i','after_or_equal:'.Carbon::now()->format('Y-m-d H:i')],
                        ]);
                        $post->inactive_at = $request->inactive_at;
                    } else {
                        $post->inactive_at = Carbon::now()->format('Y-m-d H:i');
                        $post->published_at = null;
                    }
                }
                $post->save();
                $post_details = [
                    'id' => $post->id,
                    'status_id' => $post->status_id,
                    'status' => $post->status->name,
                    'published_at' => $post->published_at,
                    'inactive_at' => $post->inactive_at,
                ];
                return response()->json([
                    'postDetails' => $post_details,
                    'status' => 'Success',
                    'message' => 'Post is updated successfully'
                ], 200);

            } else {
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
     * Method allow to choose the post is top or not.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateTopPost(Request $request, $id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()) {
                $post = Posts::where('id',$id)->first();
                if ($request->expiration != null){
                    $request->validate([
                        'expiration' => ['date_format:Y-m-d H:i','after_or_equal:'.Carbon::now()->format('Y-m-d H:i')],
                    ]);
                    $post->top_news_expiration = $request->expiration;
                } else {
                    $post->top_news_expiration = null;
                }
                $post->top_news = $request->top_post;
                $post->save();

                $post_details = [
                    'id' => $post->id,
                    'top_post' => $post->top_news,
                    'top_post_expiration' => $post->top_news_expiration,
                ];
                return response()->json([
                    'postDetails' => $post_details,
                    'status' => 'Success',
                    'message' => 'Post is updated successfully'
                ], 200);
            } else {
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
     * Method allow to update the meta Details of the post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateMetaDetails(Request $request, $id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()){
                $post = Posts::where('id',$id)->first();
                $post->seo_tag = $request->tag;
                $post->seo_description = $request->description;
                $post->save();

                $post_details = [
                    'id' => $post->id,
                    'seo_tag' => $post->seo_tag,
                    'seo_description' => $post->seo_description,
                ];
                return response()->json([
                    'postDetails' => $post_details,
                    'status' => 'Success',
                    'message' => 'Post is updated successfully'
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
    } // End Function

    /**
     * Method allow to update the Type of Post from any to any
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateType(Request $request, $id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()){
                $request->validate([
                    'type' => 'required|in:audio,video,image'
                ]);
                $post = Posts::where('id',$id)->first();
                $post->post_type = $request->type;
                $post->post_file_id = null;
                $post->post_thumbnail_id = null;
                $post->save();

                $post_details = [
                    'id' => $post->id,
                    'post_type' => $post->post_type,
                ];
                return response()->json([
                    'postDetails' => $post_details,
                    'status' => 'Success',
                    'message' => 'Post is updated successfully'
                ], 200);
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
     * Method allow to update the visibility of the Posts
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateVisibility(Request $request, $id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()){

                $post = Posts::where('id',$id)->first();
                $post->visible_as_post = $request->visibility ?? 1;
                $post->save();

                $post_details = [
                    'id' => $post->id,
                    'visible_as_post' => (int)$post->visible_as_post,
                ];
                return response()->json([
                    'postVisibility' => $post_details,
                    'status' => 'Success',
                    'message' => 'Post is updated successfully'
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
    } // End Function

    /**
     * Method allow to assign the groups for the Post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateGroups(Request $request, $id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()){
                $post = Posts::where('id',$id)->first();
                $post_groups = $post->groups;
                if (!empty($post_groups)){
                    foreach ($post_groups as $post_group){
                        $post->groups()->detach($post_group->id);
                    }
                }
                if (!empty($request->groups_id)){
                    foreach ($request->groups_id as $group_id){
                        $post->groups()->attach($group_id, ['created_at'=>Carbon::now()->format('Y-m-d H:i:s')]);
                    }
                }
                $post->updated_at = Carbon::now()->format('Y-m-d H:i:s');
                $post->save();
                $post_groups_new = Posts::where('id',$id)->first();
                $post_group_details = $post_groups_new->groups;
                $post_details = $this->getDetailsForPost($post_group_details);
                return response()->json([
                    'postGroupDetails' => $post_details,
                    'status' => 'Success',
                    'message' => 'Groups are updated successfully'
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
    } // End Function

    /**
     * Method allow to assign the categories for the Post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateCategories(Request $request, $id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()){
                $post = Posts::where('id',$id)->first();
                $post_categories = $post->categories;
                if (!empty($post_categories)){
                    foreach ($post_categories as $post_category){
                        $post->categories()->detach($post_category->id);
                    }
                }
                if (!empty($request->categories_id)){
                    foreach ($request->categories_id as $category_id){
                        $post->categories()->attach($category_id, ['created_at'=>Carbon::now()->format('Y-m-d H:i:s')]);
                    }
                }
                $post->updated_at = Carbon::now()->format('Y-m-d H:i:s');
                $post->save();
                $post_categories_new = Posts::where('id',$id)->first();
                $post_category_details = $post_categories_new->categories;
                $post_details = $this->getDetailsForPost($post_category_details);
                return response()->json([
                    'postCategoryDetails' => $post_details,
                    'status' => 'Success',
                    'message' => 'Categories are updated successfully'
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
    } // End Function

    /**
     * Method allow to assign the tags for the Post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateTags(Request $request, $id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()){
                $post = Posts::where('id',$id)->first();
                $post_tags = $post->tags;
                if (!empty($post_tags)){
                    foreach ($post_tags as $post_tag){
                        $post->tags()->detach($post_tag->id);
                    }
                }
                if (!empty($request->tags_id)){
                    foreach ($request->tags_id as $tag_id){
                        $post->tags()->attach($tag_id, ['created_at'=>Carbon::now()->format('Y-m-d H:i:s')]);
                    }
                }
                $post->updated_at = Carbon::now()->format('Y-m-d H:i:s');
                $post->save();
                $post_tags_new = Posts::where('id',$id)->first();
                $post_tag_details = $post_tags_new->tags;
                $post_details = $this->getDetailsForPost($post_tag_details);
                return response()->json([
                    'postTagDetails' => $post_details,
                    'status' => 'Success',
                    'message' => 'Tags are updated successfully'
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
    } // End Function

    /**
     * Method allow to assign the tags for the Post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateAuthors(Request $request, $id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()){
                $post = Posts::where('id',$id)->first();
                $post_authors = $post->authors;
                if (!empty($post_authors)){
                    foreach ($post_authors as $post_author){
                        $post->authors()->detach($post_author->id);
                    }
                }
                if (!empty($request->authors_id)){
                    foreach ($request->authors_id as $author_id){
                        $post->authors()->attach($author_id, ['created_at'=>Carbon::now()->format('Y-m-d H:i:s')]);
                    }
                }
                $post->updated_at = Carbon::now()->format('Y-m-d H:i:s');
                $post->save();
                $post_authors_new = Posts::where('id',$id)->first();
                $post_authors_new = $post_authors_new->authors;
                $post_details = $this->getDetailsForPostAuthor($post_authors_new);
                return response()->json([
                    'postAuthorsDetails' => $post_details,
                    'status' => 'Success',
                    'message' => 'Authors are updated successfully'
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
    } // End Function

    /**
     * Method allow to update the details of the Shareable content
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateShareableContent(Request $request, $id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()){
                $request->validate([
                    'shareable_post' => 'required|boolean',
                    'shareable_callback_url' => 'required',
                    'shareable_type' => 'nullable|in:wordpress,custom',
                ]);
                if ($request->shareable_post){
                    $shareable_type = $request->shareable_type;
                    $shareable_callback_url = $request->shareable_callback_url;
                } else {
                    $shareable_type = null;
                    $shareable_callback_url = null;
                }
                $shareable_array = [
                    'shareable_posts' => $request->shareable_post,
                    'shareable_type' => $shareable_type,
                    'shareable_callback_url' => $shareable_callback_url,
                ];
                DB::table('posts')->where('id', $id)->update($shareable_array);
                $post = Posts::where('id',$id)->first();
                $post_details = [
                    'id' => $post->id,
                    'shareable_post' => $post->shareable_posts,
                    'shareable_type' => $post->shareable_type,
                    'shareable_callback_url' => $post->shareable_callback_url,
                ];
                return response()->json([
                    'postShareableDetails' => $post_details,
                    'status' => 'Success',
                ], 200);
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
     * Method allow to update the details of the Shareable Description
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateShareableDescription(Request $request, $id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()){
                $post = Posts::where('id',$id)->first();
                $post->shareable_description = $request->shareable_description ?? null;
                $post->save();
                $post_details = [
                    'id' => $post->id,
                    'shareable_description' => $post->shareable_description
                ];
                return response()->json([
                    'postShareableDetails' => $post_details,
                    'status' => 'Success',
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
    } // End Function

    /**
     * Method allow to get the details of respective master data
     * @param $details
     * @return array
     */
    public function getDetailsForPost($details):array
    {
        $post_details = array();
        if (!empty($details)){
            foreach ($details as $detail){
                $post_details[] = [
                    'id' => $detail->id,
                    'name' => $detail->name,
                ];
            }
        }
        return $post_details;
    } // End Function

    /**
     * Method allow to get the details of respective master data
     * @param $details
     * @return array
     */
    public function getDetailsForPostAuthor($details):array
    {
        $post_details = array();
        if (!empty($details)){
            foreach ($details as $detail){
                if ($detail->salutations_id != null){
                    $salutation = $detail->salutation->salutation;
                } else {
                    $salutation = null;
                }
                if ($detail->titles_id != null){
                    $title = $detail->title->title;
                } else {
                    $title = null;
                }
                if ($detail->profile_photo_id != null){
                    $file = FoldersFiles::where('id', $detail->profile_photo_id)->first();
                    $profile_photo_url = $file->file_path;
                    $profile_photo_resolutions = $this->getFilesResolutionDetails($file->id);
                } else {
                    $profile_photo_url = null;
                    $profile_photo_resolutions = array();
                }
                $post_details[] = [
                    'id' => $detail->id,
                    'salutation_id' => $detail->salutations_id,
                    'salutation' => $salutation,
                    'title_id' => $detail->titles_id,
                    'title' => $title,
                    'firstname' => $detail->firstname,
                    'lastname' => $detail->lastname,
                    'profile_photo_id' => $detail->profile_photo_id,
                    'profile_photo_url' => $profile_photo_url,
                    'profile_photo_resolutions' => $profile_photo_resolutions,
                    'description' => $detail->description,
                    'display_name' => $detail->display_name,
                ];
            }
        }
        return $post_details;
    } // End Function

    /**
     * Method allow to delete the particular post.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()){
//                dd('coming');
                // Check the wordpress sites are existing or not and continue
                $wordpress = Wordpress::all();
                if (!empty($wordpress)){
                    $sync = new PostsSyncController();
                    foreach ($wordpress as $wordpressId){
                        $wordpress_site = Wordpress::where('id',$wordpressId->id)->first();
                        if( $wordpress_site->posts()->where('posts_id', $id)->exists()){
                            $unsync = $sync->destroySyncPost($id, $wordpressId->id);
                        }
                    }
                }
                Posts::where('id',$id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Post is deleted successfully',
                ],200);

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
     * Method allow to delete the group of posts.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->posts_id)){
                foreach ($request->posts_id as $post_id)
                {
                    $category = Posts::findOrFail($post_id);
                    $category->delete();
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Posts are deleted successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one legalText to delete'
                ], 422);
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
     * Method allow to Retrieve list of deleted posts.
     * @return JsonResponse
     * @throws Exception
     */
    public function retrieve():JsonResponse
    {
        try {
            $posts = Posts::onlyTrashed()->get();
            $post_details = $this->getPostList($posts);
            return response()->json([
                'posts' => $post_details,
                'message' => 'Success',
            ], 200);

        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to Restore the particular posts.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function restore($id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->onlyTrashed()->exists()){

                // Check the wordpress sites are existing or not and continue
                $wordpress = Wordpress::all();
                if (!empty($wordpress)){
                    foreach ($wordpress as $word) {
                        $wordpress_site = Wordpress::where('id',$word->id)->first();
                        $wordpress_site->posts()->detach($id);
                        $sync = new PostsSyncController();
                        $unsync = $sync->syncNewPost($id, $word->id);
                    }
                }

                $posts = Posts::where('id',$id)->onlyTrashed()->restore();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Post is restored successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
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
     * Method allow to Restore group of posts.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massRestore(Request $request):JsonResponse
    {
        try {
            if (!empty($request->posts_id)){
                foreach ($request->posts_id as $post_id)
                {
                    $post = Posts::where('id',$post_id)->onlyTrashed()->first();
                    if (!empty($post)){
                        $post->restore();
                    }
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Posts are restored successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one legalText to delete'
                ], 422);
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
     * Method allow to Delete the posts permanently
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function forceDelete($id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->onlyTrashed()->exists()){
                Posts::where('id',$id)->forceDelete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Post is successfully deleted permanently!',
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

    /**
     * Method allow to Delete multiple posts permanently
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massForceDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->posts_id)){
                foreach ($request->posts_id as $post_id)
                {
                    $post = Posts::where('id',$post_id)->onlyTrashed()->first();
                    if (!empty($post)){
                        $post->forceDelete();
                    }
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Posts are permanently deleted successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one legalText to delete'
                ], 422);
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
     * Method allow to retrieve all the posts with filter conditions
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getFilterPosts(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'status' => 'in:active,draft,inactive',
                'type' => 'nullable|in:image,video,audio',
                'purchase_condition' => 'nullable|in:assigned,unassigned'
            ]);
            $posts = DB::table('posts')->where('deleted_at', '=', null);

            if ($request->top_post != null){
                $posts = $posts->where('top_news', $request->top_post);
            }
            if ($request->month != null){
                $posts = $posts->whereMonth('published_at', $request->month);
            }
            if ($request->year != null){
                $posts = $posts->whereYear('published_at', $request->year);
            }
            if ($request->type != null) {
                if ($request->type === 'image') {
                    $posts = $posts->where('post_type' === 'image');
                } elseif ($request->type === 'audio') {
                    $posts = $posts->where('post_type' === 'audio');
                } else {
                    $posts = $posts->where('post_type' === 'video');
                }
            }
            $array = array();
            $post_condition_group = array();
            if ($request->groups != null) {
                $post_condition_group = $this->postsMasterData('groups', $request->groups, 'or');
                $array[] = $post_condition_group;
            }
            $post_condition_category = array();
            if ($request->categories != null) {
                $post_condition_category = $this->postsMasterData('categories', $request->categories, 'or');
                $array[] = $post_condition_category;
            }
            $post_condition_tag = array();
            if ($request->tags != null) {
                $post_condition_tag = $this->postsMasterData('tags', $request->tags, 'or');
                $array[] = $post_condition_tag;
            }

            if ($request->groups != null && $request->categories != null && $request->tags != null) {
                $post_condition_final = array_intersect($post_condition_group, $post_condition_category, $post_condition_tag);
            } else {
                $count = 0;
                if (count($array) >= 2) {
                    foreach ($array as $test_array) {
                        if (!empty($test_array)) {
                            $count++;
                        }
                    }
                }
                if ($count != count($array)) {
                    $post_condition_final = call_user_func_array('array_intersect', $array);
                } else {
                    $result = array_filter($array);
                    $post_condition_final = array_shift($result);
                    foreach ($result as $filter) {
                        $post_condition_final = array_intersect($post_condition_final, $filter);
                    }
                }
            }
            if ($post_condition_final != null){
                $posts = $posts->whereIn('id', $post_condition_final);
            }
            if ($post_condition_final == null && ($request->groups != null || $request->categories != null || $request->tags != null)) {
                $posts = $posts->whereIn('id', []);
            }
            if ($request->limit == null){
                $limit = 10;
            } else {
                $limit = (int)$request->limit;
            }

            if ($request->purchase_condition != null) {
                $purchase_ids = array();
                $un_purchase_ids = array();
                $post_purchases = Posts::all()->where('deleted_at', '=', null);
                foreach ($post_purchases as $post_purchase) {
                    $post_product_details = $post_purchase->stripesProducts;
                    $post_subscription_details = $post_purchase->stripesSubscriptions;
                    if (!$post_product_details->isEmpty() || !$post_subscription_details->isEmpty()) {
                        $purchase_ids[] = $post_purchase->id;
                    } else {
                        $un_purchase_ids[] = $post_purchase->id;
                    }
                }
                if ($request->purchase_condition === 'assigned') {
                    $posts = $posts->whereIn('id', $purchase_ids);
                }
                if ($request->purchase_condition === 'unassigned') {
                    $posts = $posts->whereIn('id', $un_purchase_ids);
                }
            }

            $ids = $posts->pluck('id')->toArray();
            $keyword = $request->search_keyword;
            $posts_publish_details = DB::table('posts')->whereIn('id', $ids)->where('status_id','=',2);
            $posts_publish = $this->searchKeywordDetails($posts_publish_details, $keyword);
            $posts_schedule_details = DB::table('posts')->whereIn('id', $ids)->where('status_id','=',3);
            $posts_scheduled = $this->searchKeywordDetails($posts_schedule_details, $keyword);
            $posts_draft_details = DB::table('posts')->whereIn('id', $ids)->where('status_id','=',1);
            $posts_drafted = $this->searchKeywordDetails($posts_draft_details, $keyword);
            $posts_inactive_details = DB::table('posts')->whereIn('id', $ids)->where('status_id','=',4);
            $posts_inactive = $this->searchKeywordDetails($posts_inactive_details, $keyword);
            $filter_overview = $this->getOverviewUponSelection($posts_scheduled, $posts_publish, $posts_drafted, $posts_inactive, $limit);

            if ($request->status != null) {
                if($request->status == 'active') {
                    $filter_overview = $this->getOverviewUponSelection($posts_scheduled, $posts_publish, $posts_drafted=[], $posts_inactive=[], $limit);
                } elseif($request->status == 'draft') {
                    $filter_overview = $this->getOverviewUponSelection($posts_scheduled=[], $posts_publish=[], $posts_drafted, $posts_inactive=[], $limit);
                } elseif($request->status == 'inactive') {
                    $filter_overview = $this->getOverviewUponSelection($posts_scheduled=[], $posts_publish=[], $posts_drafted=[], $posts_inactive, $limit);
                }
            }

            if ($filter_overview->getStatusCode() === 200) {
                return response()->json([
                    'postsFilterDetails' => $filter_overview->getData()->posts,
                    'pagination_details' => $filter_overview->getData()->posts_pagination,
                    'status' => 'Success',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'There is some issue with Fetching the data, Please look after the filter criteria',
                ], 422);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to retrieve all the Wordpress sites for the Post
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function getPostWordpress($id):JsonResponse
    {
        try {
            if (Posts::where('id',$id)->exists()){
                $posts = Posts::where('id', $id)->first();
                $posts_details = $posts->wordpress;
                $result = array();
                if (count($posts_details) > 0){
                    foreach ($posts_details as $posts_detail){
                        $result[] = [
                            'id' => $posts_detail->pivot->wordpress_id,
                            'name' => $posts_detail->name
                        ];
                    }
                    return response()->json([
                        'postWordpressDetails' => $result,
                        'status' => 'Success',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'No Content',
                        'message' => 'There are no Wordpress sites.'
                    ],422);
                }
            } else{
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

    public function initiateFactory():JsonResponse
    {
        try {
            // DB::table('events')->delete();
            $post = Events::factory()->count(100)->create();
            return response()->json([
                'status' => 'Success',
                'message' => 'Factory is implemented successfully'
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

} // End class

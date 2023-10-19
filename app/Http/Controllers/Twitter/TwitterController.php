<?php

namespace App\Http\Controllers\Twitter;

use App\Http\Controllers\Controller;
use App\Models\Posts;
use App\Models\Twitters;
use App\Models\User;
use App\Models\UsersTwitters;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;

class TwitterController extends Controller
{
    public $site_uri;
    public $client;
    private $users;
    private $twitters;
    private $posts;
    private $users_details;
    private $user_headers;

    public function __construct()
    {
        $this->client = new Client();
        $this->site_uri = 'https://api.twitter.com/';
    }

    /**
     * Method allow to store the twitter details of the user
     * @param $user_id
     * @param $twitter_id
     * @param $post_id
     * @return void
     */
    public function setHeadersForTwitter($user_id, $twitter_id, $post_id):void
    {
        $this->users = User::where('id', $user_id)->first();
        $this->twitters = Twitters::where('id', $twitter_id)->first();
        $this->posts = Posts::where('id', $post_id)->first();
        $this->users_details = $this->users->twitter()->where('twitters_id', $this->twitters->id)->first();
        $this->user_headers = [
            'Authorization' => 'Bearer ' . $this->users_details->pivot->access_token,
        ];
    }

    /**
     * Method allow to display list of all Twitter Accounts
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $query = Twitters::all();
            $twitter_details = $this->getTwitterDetails($query);
            return response()->json([
                'twitterDetails' => $twitter_details,
                'status' => 'Success',
            ],200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function getTwitterDetails($twitters):array
    {
        $result_array = array();
        if (!empty($twitters)){
            foreach ($twitters as $twitter){
                $result_array[] = [
                    'id' => $twitter->id,
                    'app_name' => $twitter->app_name,
                    'client_id' => $twitter->client_id,
                    'callback_url' => $twitter->callback_url,
                    'created_at' => $twitter->created_at,
                    'updated_at' => $twitter->updated_at,
                ];
            }
        }
        return $result_array;
    } // End Function

    /**
     * Method allow to store the twitter details of the user
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'app_name' => 'required|string|unique:twitters',
                'client_id' => 'required|string',
                'client_secret' => 'required|string',
                'callback_url' => 'required|string',
            ]);

            $twitter_id = DB::table('twitters')->insertGetId([
                'app_name' => $request->app_name,
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'callback_url' => $request->callback_url,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            $twitter_details = Twitters::where('id', $twitter_id)->get();
            $twitters = $this->getTwitterDetails($twitter_details);
            $result = array();
            foreach ($twitters as $twitter){
                $result = $twitter;
            }
            return response()->json([
                'twitterDetails' => $result,
                'status' => 'Success',
            ],200);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to display a single twitter account for accessing the authorization URL
     * @return JsonResponse
     * @throws Exception
     */
    public function twitterPosts()
    {

    } //End Function
    /**
     * Method allow to display a single twitter account for accessing the authorization URL
     * @param $id // Twitter ID
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            if (Twitters::where('id', $id)->exists()){
                $query = Twitters::where('id', $id)->get();
                $twitters = $this->getTwitterDetails($query);
                $result = array();
                foreach ($twitters as $twitter){
                    $result = $twitter;
                }
                return response()->json([
                    'twitterDetails' => $result,
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

    public function showShareableAccounts():JsonResponse
    {
        try {
            $users_twitters = UsersTwitters::where('auth_id', null)
                ->where('shareable_password','!=',null)->get();
            $twitter_Details = array();
            foreach ($users_twitters as $users_twitter){
                $user = User::where('id', $users_twitter->users_id)->first();
                $twitter = Twitters::where('id', $users_twitter->twitters_id)->first();
                $twitter_Details[] = [
                    'user_id' => $users_twitter->users_id,
                    'system_name' => $user->firstname . ' ' . $user->lastname,
                    'twitter_id' => $users_twitter->twitters_id,
                    'twitter_user_name' => $users_twitter->username,
                    'twitter_account_name' => $twitter->app_name,
                    'user_twitter_detail_id' => $users_twitter->id,
                ];
            }
            return response()->json([
                'twitterDetails' => $twitter_Details,
                'status' => 'Success',
            ],200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function getAuthorizationURL($id):JsonResponse
    {
        try {
            if (Twitters::where('id', $id)->exists()){
                $twitter = Twitters::where('id', $id)->first();
                $callback_url = urlencode($twitter->callback_url);
                $state = Str::random(30);
                $scope = 'tweet.read%20tweet.write%20users.read%20offline.access';
                $url = 'https://twitter.com/i/oauth2/authorize?response_type=code&client_id='.$twitter->client_id.'&redirect_uri='.$callback_url.'&state='.$state.'&scope='.$scope.'&code_challenge=challenge&code_challenge_method=plain';
                return response()->json([
                    'twitterDetails' => $url,
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

    public function getMetrics($id, $user_id)
    {
        try {
            if (Twitters::where('id', $id)->exists()){
                $user = User::where('id', $user_id)->first();
                $user_details = $user->twitter()->where('twitters_id', $id)->first();
                $tweet_fields = 'non_public_metrics,organic_metrics';
                $expansions = 'attachments.media_keys&media.fields=public_metrics';
                $metrics_headers = [
                    'Authorization' => 'Bearer '.$user_details->pivot->access_token,
                ];
                $tweet = json_decode( $this->client->request('GET',
                    $this->site_uri . '2/tweets?ids=1557282719381557248,1557282861224525824,1557285990208782338&tweet.fields='.$tweet_fields,
                    ['headers' => $metrics_headers])->getBody());
                dd($tweet);
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
    }

    /**
     * Method allow to Update the details of the Twitter for API
     * @param Request $request
     * @param $id // Twitter ID
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            if (Twitters::where('id', $id)->exists()){
                $twitter = Twitters::where('id',$id)->first();
                $request->validate([
                    'app_name' => ['required','string', Rule::unique('twitters', 'app_name')->ignore($twitter->id)],
                    'client_id' => 'required|string',
                    'callback_url' => 'required|string',
                ]);
                if (!empty($request->client_secret)){
                    $client_secret = $request->client_secret;
                } else {
                    $client_secret = $twitter->client_secret;
                }
                $twitter->app_name = $request->app_name;
                $twitter->client_id = $request->client_id;
                $twitter->client_secret = $client_secret;
                $twitter->callback_url = $request->callback_url;
                $twitter->save();

                $twitter_details = Twitters::where('id', $twitter->id)->get();
                $twitters = $this->getTwitterDetails($twitter_details);
                $result = array();
                foreach ($twitters as $twitter_final){
                    $result = $twitter_final;
                }
                return response()->json([
                    'twitterDetails' => $result,
                    'status' => 'Success',
                ],200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to authorize the User based on the Code after successful login
     * @param Request $request
     * @param $id // Twitter ID
     * @param $user_id // User ID
     * @return JsonResponse
     * @throws Exception
     */
    public function authorizeUserAccessToken(Request $request, $id, $user_id):JsonResponse
    {
        try {
            if (Twitters::where('id', $id)->exists()) {
                $request->validate([
                    'code' => 'required|string',
                    'auth_type' => 'required|in:new,existing'
                ]);
                $user = User::where('id', $user_id)->first();
                $twitter = Twitters::where('id', $id)->first();
                $basic_token = base64_encode($twitter->client_id . ':' . $twitter->client_secret);
                $access_token_headers = [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . $basic_token
                ];
                $auth = $this->client->request('POST',
                    $this->site_uri . '2/oauth2/token?grant_type=authorization_code&code=' . $request->code . '&code_verifier=challenge&redirect_uri=' . $twitter->callback_url,
                    ['headers' => $access_token_headers]);
                $auth_body = json_decode($auth->getBody());
                $auth_status = json_decode($auth->getStatusCode());
                if ($auth_status == 200) {
                    $twitter_user = $this->authenticateUser($id, $user_id, $auth_body->access_token, $auth_body->refresh_token, $auth_body->token_type, $request->auth_type);
                    if ($twitter_user->status() == 200) {
                        $user_twitter = $user->twitter()->where('twitters_id', $id)->where('access_token', $auth_body->access_token)->first();
                        $result_array = [
                            'id' => $twitter->id,
                            'app_name' => $twitter->app_name,
                            'username' => $user_twitter->pivot->username,
                            'profile_picture_url' => $user_twitter->pivot->profile_picture_url,
                            'auth_type' => $user_twitter->pivot->auth_type,
                            'user_twitter_detail_id' => $user_twitter->pivot->id,
                        ];
                        return response()->json([
                            'twitterDetails' => $result_array,
                            'message' => 'The user is connected to the desired twitter account',
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 'Error',
                            'message' => $twitter_user->getData()->message,
                        ], 500);
                    }
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'There is some error in authorization please contact your administrator'
                    ], $auth_status);
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

    public function authenticateUser($twitter_id, $user_id, $access_token, $refresh_token, $token_type, $auth_type):JsonResponse
    {
        try {
            $user = User::where('id',$user_id)->first();
            $twitter = Twitters::where('id', $twitter_id)->first();
            $user_headers = [
                'Authorization' => 'Bearer '. $access_token,
            ];
            $user_auth_details = json_decode($this->client->request('GET',
                $this->site_uri . '2/users/me?user.fields=profile_image_url',
                ['headers' => $user_headers])->getBody());
            $user_auth_details = $user_auth_details->data;
            $additional_values = [
                'twitter_user_id' => $user_auth_details->id,
                'username' => $user_auth_details->username,
                'profile_picture_url' => $user_auth_details->profile_image_url,
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'token_type' => $token_type,
                'auth_type' => $auth_type,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];
            $user->twitter()->attach($twitter->id, $additional_values);
            return response()->json([
                'status' => 'Success',
            ],200);
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to create the shareable password for sharing the credentials to others
     * @param Request $request
     * @param $id // Twitter ID
     * @param $user_id // User ID
     * @return JsonResponse
     * @throws Exception
     */
    public function shareablePassword(Request $request, $id):JsonResponse
    {
        try {
            $user_twitter = UsersTwitters::where('id', $id)->first();
            if (!empty($user_twitter)) {
                if ($user_twitter->auth_type === 'new'){
                    if (!empty($request->shareable_password)){
                        $shareable_password = $request->shareable_password;
                    } else {
                        $shareable_password = null;
                    }
                    $user_twitter->shareable_password = $shareable_password;
                    $user_twitter->save();
                    return response()->json([
                        'status' => 'Success',
                        'message' => 'Shareable password is updated successfully'
                    ],200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Sorry! cannot create shareable password.'
                    ],422);
                }
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

    /**
     * Method allow to authorize the User with the existing owner accounts
     * @param Request $request
     * @param $id // Twitter ID
     * @param $user_id // User ID
     * @return JsonResponse
     * @throws Exception
     */
    public function authenticateExistingUser(Request $request, $id, $user_id):JsonResponse
    {
        try {
            $request->validate([
                'auth_id' => 'required|integer',
                'auth_type' => 'required|in:existing',
                'shareable_password' => 'required'
            ]);
            $user_twitter = UsersTwitters::where('id', $request->auth_id)->first();
            $new_user = User::where('id', $user_id)->first();
            if ($user_id != $user_twitter->users_id) {
                if (!empty($user_twitter) && $user_twitter->shareable_password != null) {
                    if ($request->shareable_password === $user_twitter->shareable_password) {
                        $new_user->twitter()->attach($id, [
                            'twitter_user_id' => $user_twitter->twitter_user_id,
                            'username' => $user_twitter->username,
                            'profile_picture_url' => $user_twitter->profile_picture_url,
                            'access_token' => $user_twitter->access_token,
                            'token_type' => $user_twitter->token_type,
                            'refresh_token' => $user_twitter->refresh_token,
                            'auth_type' => $request->auth_type,
                            'auth_id' => $request->auth_id,
                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        ]);
                        $new_twitter = Twitters::where('id', $id)->first();
                        $new_user_twitter = $new_user->twitter()->where('twitters_id', $id)->first();
                        $result_array = [
                            'id' => $id,
                            'app_name' => $new_twitter->app_name,
                            'username' => $new_user_twitter->pivot->username,
                            'profile_picture_url' => $new_user_twitter->pivot->profile_picture_url,
                            'auth_type' => $new_user_twitter->pivot->auth_type,
                            'user_twitter_detail_id' => $new_user_twitter->pivot->id,
                        ];
                        return response()->json([
                            'twitterDetails' => $result_array,
                            'message' => 'The user is connected to the desired twitter account',
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 'Error',
                            'message' => 'Please enter the correct password and try again.'
                        ], 422);
                    }
                } else {
                    return response()->json([
                        'status' => 'No Content',
                        'message' => 'There is no relevant information for selected query'
                    ], 210);
                }
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'You are already an owner, Cannot connect to the account.'
                ], 422);
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to tweet the post details on the wall of login user
     * @param Request $request
     * @param $id // Twitter Detail ID
     * @return JsonResponse
     * @throws Exception
     */
    public function tweetPosts(Request $request, $id):JsonResponse
    {
        try {
            if (UsersTwitters::where('id',$id)->exists()){
                $request->validate([
                    'text' => 'required|max:200',
                ]);
                $user_twitter = UsersTwitters::where('id', $id)->first();
                $posts = Posts::where('id', $request->post_id)->first();
                if (!empty($user_twitter)) {
                    $this->user_headers = [
                        'Authorization' => 'Bearer '.$user_twitter->access_token,
                    ];
                    $request_payload = [
                        'text' => $request->text,
                    ];
                    $tweet = json_decode($this->client->request('POST',
                        $this->site_uri . '2/tweets',
                        ['headers' => $this->user_headers, 'json' => $request_payload])->getBody());
                    $tweet_details = $tweet->data;
                    $owners_id = $user_twitter->auth_id;
                    if ($owners_id != null) {
                        $auth_id = $owners_id;
                    } else {
                        $auth_id = $user_twitter->id;
                    }
                    $tweet_data = [
                        'text' => $request->text,
                        'users_twitters_id' => $auth_id,
                        'users_id' => $user_twitter->users_id,
                        'twitter_post_id' => $tweet_details->id,
                        'tweeted_by' => $user_twitter->username,
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ];
                    $posts->twitter()->attach($user_twitter->twitters_id, $tweet_data);
                    $twitter_post_details = $this->getTwitterPostDetails($user_twitter->twitters_id, $request->post_id, $user_twitter->users_id, $tweet_details->id);
                    return response()->json([
                        'twitterDetails' => $twitter_post_details,
                        'message' => 'The Post is tweeted successfully'
                    ], 200);
                } return response()->json([
                    'status' => 'Error',
                    'message' => 'Please check the query, As some parameters are missing'
                ],422);
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

    public function getTwitterPostDetails($twitter_id, $post_id, $user_id, $twitter_post_id):array
    {
        $post = Posts::where('id', $post_id)->first();
        $post_details = $post->twitter()->where('twitter_post_id', $twitter_post_id)->first();
        $result = [
            'id' => $twitter_id,
            'text' => $post_details->pivot->text,
            'twitter_post_id' => $post_details->pivot->twitter_post_id,
            'tweeted_by' => $post_details->pivot->tweeted_by,
            'tweeted_by_id' => $user_id,
            'retweeted' => $post_details->pivot->retweeted,
            'retweeted_by' => $post_details->pivot->retweeted_by,
            'disconnected' => $post_details->pivot->disconnected,
        ];
        return $result;
    } // End Function

    /**
     * Method allow to Re-Tweet the post details on the wall of login user
     * @param Request $request
     * @param $id // Twitter ID
     * @return JsonResponse
     * @throws Exception
     */
    public function reTweetPosts(Request $request, $id):JsonResponse
    {
        try {
            if (UsersTwitters::where('id',$id)->exists()) {
                $user_twitter = UsersTwitters::where('id', $id)->first();
                $posts = Posts::where('id', $request->post_id)->first();
                $post_twitter = $posts->twitter()
                    ->where('twitter_post_id', $request->twitter_post_id)
                    ->where('twitters_id', $user_twitter->twitters_id)->first();
                if (!empty($post_twitter)) {
                    $request_payload = [
                        'tweet_id' => $post_twitter->pivot->twitter_post_id,
                    ];
                    $this->user_headers = [
                        'Authorization' => 'Bearer '.$user_twitter->access_token,
                    ];
                    if (empty($post_twitter->pivot->retweeted_by)) {
                        $retweeted_by = [$user_twitter->username];
                    } else {
                        $json_array = json_decode($post_twitter->pivot->retweeted_by);
                        if (! in_array($user_twitter->username, $json_array)){
                            $json_array[] = $user_twitter->username;
                        }
                        $retweeted_by = $json_array;
                    }
                    $tweet = json_decode( $this->client->request('POST',
                        $this->site_uri . '2/users/'.$user_twitter->twitter_user_id.'/retweets',
                        ['headers' => $this->user_headers, 'json' => $request_payload])->getBody());
                    $tweet_details = $tweet->data;
                    $retweet_data = [
                        'retweeted' => true,
                        'retweeted_by' => $retweeted_by,
                    ];
                    $posts->twitter()->wherePivot('twitter_post_id', $request->twitter_post_id)->updateExistingPivot($user_twitter->twitters_id, $retweet_data);
                    $twitter_post_details = $this->getTwitterPostDetails($user_twitter->twitters_id, $request->post_id, $user_twitter->users_id, $post_twitter->pivot->twitter_post_id);
                    return response()->json([
                        'twitterDetails' => $twitter_post_details,
                        'message' => 'The Post is re-tweeted successfully'
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Please check the query, As some parameters are missing'
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

    /**
     * Method allow to delete the tweet of post on wall of login user
     * @param Request $request
     * @param $id // Twitter Detail ID
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteTweetsPosts(Request $request, $id):JsonResponse
    {
        try {
            if (UsersTwitters::where('id',$id)->exists()){
                $user_twitter = UsersTwitters::where('id', $id)->first();
                $posts = Posts::where('id',$request->post_id)->first();
                $post_twitter = $posts->twitter()
                    ->where('users_twitters_id', $id)
                    ->where('twitters_id', $user_twitter->twitters_id)->first();
                $this->user_headers = [
                    'Authorization' => 'Bearer '.$user_twitter->access_token,
                ];
                $destroy_tweet = $this->client->request('DELETE',
                    $this->site_uri . '2/tweets/'.$post_twitter->pivot->twitter_post_id,
                    ['headers' => $this->user_headers]);
                $posts->twitter()->wherePivot('users_twitters_id', $id)->detach($user_twitter->twitters_id);
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The tweet is deleted successfully.'
                ],200);
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

    /**
     * Method allow to grab the refresh token for the user before logging out.
     * @param $id // Twitter Detail Id
     * @return JsonResponse
     * @throws Exception
     */
    public function generateRefreshToken($id):JsonResponse
    {
        try {
            if (UsersTwitters::where('id',$id)->exists()) {
                $user_twitter = UsersTwitters::where('id', $id)->first();
                $user = User::where('id', $user_twitter->users_id)->first();
                $twitter = Twitters::where('id', $user_twitter->twitters_id)->first();
                $basic_token = base64_encode($twitter->client_id . ':' . $twitter->client_secret);
                $headers = [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . $basic_token,
                ];
                $auth = $this->client->request('POST',
                    'https://api.twitter.com/2/oauth2/token?grant_type=refresh_token&refresh_token=' . $user_twitter->refresh_token,
                    ['headers' => $headers]);
                $auth_body = json_decode($auth->getBody());
                $additional_values = [
                    'access_token' => $auth_body->access_token,
                    'refresh_token' => $auth_body->refresh_token,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ];
                UsersTwitters::where('id', $id)->update($additional_values);
                $child_twitters = UsersTwitters::where('auth_id', $id)->get();
                if (!empty($child_twitters)) {
                    foreach ($child_twitters as $child_twitter) {
                        UsersTwitters::where('id', $child_twitter->id)->update($additional_values);
                    }
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Refresh token is generated successfully.'
                ], 200);

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

    /**
     * Method allow to revoke the token details of the user attached and disconnect
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function disconnectUser($id):JsonResponse
    {
        try {
            if (UsersTwitters::where('id',$id)->exists()) {
                $user_twitter = UsersTwitters::where('id', $id)->first();
                $user = User::where('id', $user_twitter->users_id)->first();
                $twitter = Twitters::where('id', $user_twitter->twitters_id)->first();
                $basic_token = base64_encode($twitter->client_id . ':' . $twitter->client_secret);
                if ($user_twitter->auth_id === null) {
                    $access_token_headers = [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Authorization' => 'Basic ' . $basic_token
                    ];
                    $revoke_token = $this->client->request('POST',
                        'https://api.twitter.com/2/oauth2/revoke?token=' . $user_twitter->access_token . '&token_type_hint=access_token',
                        ['headers' => $access_token_headers]);

                    $revoke_status = json_decode($revoke_token->getStatusCode());
                    if ($revoke_status == 200) {
                        $users_twitters_details = UsersTwitters::where('auth_id', $user_twitter->id)->get();
                        if (!empty($users_twitters_details)) {
                            foreach ($users_twitters_details as $users_twitters_detail) {
                                $temp_user = User::where('id', $users_twitters_detail->users_id)->first();
                                $temp_user->twitter()->detach($users_twitters_detail->twitters_id);
                            }
                        }
                        $twitter_posts_details = $twitter->posts()->where('users_twitters_id', $user_twitter->id)->get();
                        if (!empty($twitter_posts_details)) {
                            foreach ($twitter_posts_details as $twitter_posts_detail){
                                $twitter->posts()->wherePivot('users_twitters_id', $id)->updateExistingPivot($twitter_posts_detail->pivot->posts_id, ['disconnected' => 1]);
                            }
                        }
                    } else {
                        return response()->json([
                            'status' => 'Error',
                            'message' => 'There is some issue in revoking the token. Please contact Administrator'
                        ], 210);
                    }
                } else {
                    $twitter_posts_details = $twitter->posts()->where('users_id', $user_twitter->users_id)->get();
                    if (!empty($twitter_posts_details)) {
                        foreach ($twitter_posts_details as $twitter_posts_detail){
                            $twitter->posts()->wherePivot('users_id', $twitter_posts_detail->pivot->users_id)->updateExistingPivot($twitter_posts_detail->pivot->posts_id, ['users_id' => null]);
                        }
                    }
                }
                UsersTwitters::where('id', $id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The User is disconnected from twitter Successfully'
                ], 200);

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

    /**
     * Method allow to delete the Twitter account
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (Twitters::where('id',$id)->exists()) {
                $twitter = Twitters::where('id', $id)->first();
                $twitter_posts = $twitter->posts;
                if (!empty($twitter_posts)) {
                    foreach ($twitter_posts as $twitter_post) {
                        $twitter->posts()->updateExistingPivot($twitter_post->id, ['disconnected' => 1]);
                    };
                }
                Twitters::where('id',$id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Twitter account is deleted successfully',
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
     * Method allow to Look after details of the Registered User
     * @param $id // Twitter ID
     * @param $user_id
     * @return JsonResponse
     * @throws Exception
     */
    public function userLookUp($id, $user_id):JsonResponse
    {
        try {
            if (Twitters::where('id',$id)->exists()){
                $user = User::where('id',$user_id)->first();
                $twitter = Twitters::where('id', $id)->first();
                $user_details = $user->twitter()->where('twitters_id',$twitter->id)->first();
                $user_headers = [
                    'Authorization' => 'Bearer '. $user_details->pivot->access_token,
                ];
                $auth = $this->client->request('GET',
                    $this->site_uri . '2/users/me',
                    ['headers' => $user_headers]);
                $auth = json_decode($auth->getBody());
                $auth = $auth->data;
                return response()->json([
                    'tokenDetails' => $auth,
                    'status' => 'Success',
                ],200);
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

} // End Class

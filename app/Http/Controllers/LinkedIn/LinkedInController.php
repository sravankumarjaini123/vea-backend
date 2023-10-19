<?php

namespace App\Http\Controllers\LinkedIn;

use App\Http\Controllers\Controller;
use App\Models\FoldersFiles;
use App\Models\LinkedIns;
use App\Models\Posts;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;

class LinkedInController extends Controller
{
    public $client;
    public $apiClient;
    public $headers;
    public $authheaders;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://www.linkedin.com']);
        $this->apiClient = new Client(['base_uri' => 'https://api.linkedin.com']);
        $this->authheaders = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }

    /**
     * Method allow to display list of all LinkedIn apps.
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $query = LinkedIns::all();
            $linked_ins = $this->getLinkedInDetails($query);
            return response()->json([
                'linkedInDetails' => $linked_ins,
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
     * Method allow to retrieve the details of LinkedIn apps.
     * @param $linkedIns
     * @return JsonResponse
     * @throws Exception
     */
    public function getLinkedInDetails($linkedIns):array
    {
        $result_array = array();
        if (!empty($linkedIns)){
            foreach ($linkedIns as $linkedIn){
                $result_array[] = [
                    'id' => $linkedIn->id,
                    'app_name' => $linkedIn->app_name,
                    'client_id' => $linkedIn->client_id,
                    'callback_url' => $linkedIn->callback_url,
                    'created_at' => $linkedIn->created_at,
                    'updated_at' => $linkedIn->updated_at,
                ];
            }
        }
        return $result_array;
    } // End Function

    /**
     * Method allow to create the mew LinkedIn app.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'app_name' => 'required|string',
                'client_id' => 'required|string',
                'client_secret' => 'required|string',
                'callback_url' => 'required|string',
            ]);

            $linked_in = DB::table('linkedins')->insertGetId([
                'app_name' => $request->app_name,
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'callback_url' => $request->callback_url,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
            $linked_ins = LinkedIns::where('id', $linked_in)->get();
            $linked_ins_details = $this->getLinkedInDetails($linked_ins);
            $result = array();
            foreach ($linked_ins_details as $linked_ins_detail){
                $result = $linked_ins_detail;
            }
            return response()->json([
                'linkedInDetails' => $result,
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
     * Method allow to display details of the single LinkedIn app.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            if (LinkedIns::where('id', $id)->exists()){
                $linked_in_details = LinkedIns::where('id', $id)->get();
                $linked_ins = $this->getLinkedInDetails($linked_in_details);
                $result = array();
                foreach ($linked_ins as $linked_in){
                    $result = $linked_in;
                }
                return response()->json([
                    'linkedInDetails' => $result,
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
     * Method allow to Update the details of the Twitter for API
     * @param Request $request
     * @param $id // Twitter ID
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            if (LinkedIns::where('id', $id)->exists()){
                $linkedIn = LinkedIns::where('id',$id)->first();
                $request->validate([
                    'app_name' => ['required','string', Rule::unique('twitters', 'app_name')->ignore($linkedIn->id)],
                    'client_id' => 'required|string',
                    'callback_url' => 'required|string',
                ]);
                if (!empty($request->client_secret)){
                    $client_secret = $request->client_secret;
                } else {
                    $client_secret = $linkedIn->client_secret;
                }
                $linkedIn->app_name = $request->app_name;
                $linkedIn->client_id = $request->client_id;
                $linkedIn->client_secret = $client_secret;
                $linkedIn->callback_url = $request->callback_url;
                $linkedIn->save();

                $linkedIn_details = LinkedIns::where('id', $linkedIn->id)->get();
                $linkedIns = $this->getLinkedInDetails($linkedIn_details);
                $result = array();
                foreach ($linkedIns as $linkedIn_detail){
                    $result = $linkedIn_detail;
                }
                return response()->json([
                    'linkedInDetails' => $result,
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
     * Method allow to retrieve the authorization URL of LinkedIn app for user.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function getAuthorizationURL($id):JsonResponse
    {
        try {
            if (LinkedIns::where('id', $id)->exists()){
                $linked_ins = LinkedIns::where('id', $id)->first();
                $state = Str::random(30);
                $scope = 'r_basicprofile%20r_emailaddress%20w_member_social%20w_organization_social%20r_organization_social%20rw_organization_admin';
                $url = 'https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id='.$linked_ins->client_id.'&redirect_uri='.$linked_ins->callback_url.'&state='.$state.'&scope='.$scope;
                return response()->json([
                    'linkedInDetails' => $url,
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
     * Method allow to Authorize the user based on the code provided after accepting
     * @param Request $request
     * @param $user_id
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function authorizeUserAccessToken(Request $request, $id, $user_id):JsonResponse
    {
        try {
            if (LinkedIns::where('id', $id)->exists()){
                $request->validate(['code' => 'required']);
                $authorization = $this->authenticateUser($id, $user_id, $request->code);
                if ($authorization->status() == 200){
                    $headers = $this->setAuthHeaders($id, $user_id);
                    if ($headers){
                        $linkedIn_user_details = $this->attachUserDetails($id, $user_id);
                        if ($linkedIn_user_details->status() == 200){
                            $linked_in = LinkedIns::where('id',$id)->first();
                            $user = User::where('id', $user_id)->first();
                            $user_linkedin = $user->linkedIn()->where('linkedins_id', $linked_in->id)->first();
                            $result = [
                                'id' => $linked_in->id,
                                'app_name' => $linked_in->app_name,
                                'username' => $user_linkedin->pivot->username,
                                'profile_picture_url' => $user_linkedin->pivot->profile_picture_url,
                            ];
                            return response()->json([
                                'linkedInDetails' => $result,
                                'status' => 'Success',
                            ],200);
                        } else {
                            return response()->json([
                                'status' => 'Error',
                                'message' => $linkedIn_user_details->getData()->message,
                            ],500);
                        }
                    } else {
                        return response()->json([
                            'status' => 'Error',
                            'message' => 'There is some issue in setting the headers',
                        ],422);
                    }
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => $authorization->getData()->message,
                    ],500);
                }
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
     * Method allow to complete the authentication of the user
     * @param $linkedIn_id
     * @param $user_id
     * @param $code
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function authenticateUser($linkedIn_id, $user_id, $code):JsonResponse
    {
        try {
            $linked_in = LinkedIns::where('id',$linkedIn_id)->first();
            $user = User::where('id', $user_id)->first();

            $linkedIn_access_details = [
                'grant_type' => 'authorization_code',
                'client_id' => $linked_in->client_id,
                'client_secret' => $linked_in->client_secret,
                'redirect_uri' => $linked_in->callback_url,
                'code' => $code,
            ];
            $this->authheaders = [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ];
            $authorize_user = $this->client->request('POST',
                '/oauth/v2/accessToken',
                ['headers' => $this->authheaders, 'form_params' => $linkedIn_access_details,]);
            $data = json_decode($authorize_user->getBody());
            $linked_in_details = [
                'access_token' => $data->access_token,
                'refresh_token' => $data->refresh_token,
                'token_type' => 'Bearer',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];
            $user->linkedIn()->attach($linked_in->id, $linked_in_details);
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
     * Method allow to set the access token headers for accessing the api of LinkedIN
     * @param $linkedIn_id
     * @param $user_id
     * @return boolean
     */
    public function setAuthHeaders($linkedIn_id, $user_id):bool
    {
        $linked_in = LinkedIns::where('id',$linkedIn_id)->first();
        $user = User::where('id',$user_id)->first();
        $user_linkedin = $user->linkedIn()->where('linkedins_id', $linked_in->id)->first();
        if (!empty($user_linkedin)){
            $this->headers = [
                'Accept' => 'application/json',
                'Content_Type' => 'application/json',
                'Authorization' => "Bearer " . $user_linkedin->pivot->access_token,
            ];
            return true;
        } else {
            return false;
        }
    } // End Function

    /**
     * Method allow to get the details of the login user for LinkedIn
     * @param $linkedIn_id
     * @param $user_id
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function attachUserDetails($linkedIn_id, $user_id):JsonResponse
    {
        try {
            $linked_in = LinkedIns::where('id',$linkedIn_id)->first();
            $user = User::where('id', $user_id)->first();
            $user_linkedin = $user->linkedIn()->where('linkedins_id', $linked_in->id)->first();
            $organisation_headers = [
                'X-Restli-Protocol-Version' => '2.0.0',
                'Authorization' => "Bearer " . $user_linkedin->pivot->access_token,
            ];
            $response_profile_picture = $this->apiClient->request('GET', '/v2/me?projection=(id,owner,firstName,lastName,profilePicture(displayImage~digitalmediaAsset:playableStreams))', [
                'headers' => $this->headers]);
            $response_organisation = $this->apiClient->request('GET', '/v2/organizationAcls?q=roleAssignee', [
                'headers' => $organisation_headers]);

            $data = json_decode($response_profile_picture->getBody()->getContents(), true);
            $org = json_decode($response_organisation->getBody()->getContents(), true);

            $organisations = $org['elements'][0]['organization'];
            $organisations = explode(':', $organisations);
            $profile_picture = array();
            $first_name = null;
            $last_name = null;
            $organisation_id = null;
            foreach ($data as $key => $d){
                if ($key === 'lastName'){
                    $lastName_key = array_key_first($d['localized']);
                    $last_name = $d['localized'][$lastName_key];
                }
                if ($key === 'firstName'){
                    $firstName_key = array_key_first($d['localized']);
                    $first_name = $d['localized'][$firstName_key];
                }
                if ($key === 'profilePicture') {
                    foreach ($d['displayImage~'] as $a) {
                        $profile_picture[] = array_push($profile_picture, $a);
                    }
                }
            }
            $linkedin_profile_picture = $profile_picture[2][2]['identifiers'][0]['identifier'];
            $linkedin_user_id = $data['id'];
            foreach ($organisations as $key => $organisation){
                if ($key === 3){
                    $organisation_id = $organisation;
                }
            }
            $linkedIn_details = [
                'linkedin_user_id' => $linkedin_user_id,
                'organisation_id' => $organisation_id,
                'username' => $first_name . ' ' . $last_name,
                'profile_picture_url' => $linkedin_profile_picture,
            ];
            $user->linkedIn()->updateExistingPivot($linked_in->id, $linkedIn_details);

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
     * Method allow to issue the new refresh and access tokens after expiry
     * @param $id
     * @param $user_id
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function refreshTokenWithAccessToken($id, $user_id):JsonResponse
    {
        try {
            $linked_in = LinkedIns::where('id',$id)->first();
            $user = User::where('id', $user_id)->first();
            $user_linkedin = $user->linkedIn()->where('linkedins_id', $linked_in->id)->first();
            $linkedIn_refresh_details = [
                'grant_type' => 'refresh_token',
                'client_id' => $linked_in->client_id,
                'client_secret' => $linked_in->client_secret,
                'refresh_token' => $user_linkedin->pivot->refresh_token,
            ];

            $refresh_user = $this->client->request('POST',
                '/oauth/v2/accessToken',
                ['headers' => $this->authheaders, 'form_params' => $linkedIn_refresh_details]);
            $data = json_decode($refresh_user->getBody());
            $linked_in_details = [
                'access_token' => $data->access_token,
                'refresh_token' => $data->refresh_token,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];
            $user->linkedIn()->updateExistingPivot($linked_in->id, $linked_in_details);
            return response()->json([
                'status' => 'Success',
                'message' => 'The new Access token and Refresh Token are issued successfully'
            ],200);
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to share the posts to LinkedIn with media...
     * @param Request $request
     * @param $id
     * @param $user_id
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function sharePosts(Request $request, $id, $user_id):JsonResponse
    {
        try {
            $request->validate([
                'body' => 'required|max:3000',
                'visibility' => 'required|in:everyone,connections',
                'content_type' => 'nullable|in:media,external_url',
                'type' => 'required|in:organization,personal'
            ]);
            $linked_in = LinkedIns::where('id',$id)->first();
            $user = User::where('id', $user_id)->first();
            $post = Posts::where('id', $request->post_id)->first();
            $this->setAuthHeaders($id, $user_id);
            $user_linkedin = $user->linkedIn()->where('linkedins_id', $linked_in->id)->first();
            if ($request->type === 'organization'){
                $urn = 'urn:li:organization:'.$user_linkedin->pivot->organisation_id;
                $share_type = 'organization';
            } else {
                $urn = 'urn:li:person:'.$user_linkedin->pivot->linkedin_user_id;
                $share_type = 'personal';
            }
            if ($request->content_type === 'media') {
                if ($post->post_file_id != null && $post->post_type == 'image') {
                    $this->shareMediaAssets($post->post_file_id, $urn, $user_linkedin->pivot->access_token, $post, $linked_in);
                } else {
                    $post->linkedIn()->attach($linked_in->id, ['content_type' => 'media']);
                }
            } elseif($request->content_type === 'external_url') {
                $details = [
                    'content_type' => $request->content_type,
                    'external_url' => $request->external_share_url,
                ];
                $post->linkedIn()->attach($linked_in->id, $details);
            } else {
                $post->linkedIn()->attach($linked_in->id);
            }
            $post_linkedIn = $post->linkedIn()->where('linkedins_id', $linked_in->id)->first();
            if ($request->visibility === 'connections'){
                $visibility = false;
            } else {
                $visibility = true;
            }
            $body = [
                'distribution' => [
                    'linkedInDistributionTarget' => [
                        'visibleToGuest' => $visibility
                    ]
                ],
                'owner' => $urn,
                'subject' => $request->subject,
                'text' => [
                    'text' => $request->body . "\n\n" . $request->hashtags,
                ],
            ];
            if ($post_linkedIn->pivot->content_type != null) {
                if ($post_linkedIn->pivot->content_type === 'media' && $post_linkedIn->pivot->media_id != null) {
                    $content_entity = [
                        'contentEntities' => [
                            [
                                'entity' => $post_linkedIn->pivot->media_id,
                            ],
                        ],
                        'shareMediaCategory' => 'IMAGE',
                    ];
                    $body = array_merge($body, ['content' => $content_entity]);
                } else {
                    if ($request->external_share_url != null) {
                        $content_entity = [
                            'contentEntities' => [
                                [
                                    'entityLocation' => $request->external_share_url,
                                ],
                            ],
                        ];
                        $body = array_merge($body, ['content' => $content_entity]);
                    }
                }
            }

            $share_request = $this->apiClient->request('POST',
                '/v2/shares', ['headers' => $this->headers, 'json' => $body]);

            $share_request_body = json_decode($share_request->getBody());
            $share_request_status = json_decode($share_request->getStatusCode());
            if ($share_request_status === 200 || $share_request_status === 201){
                $additional_details = [
                    'subject' => $request->subject,
                    'body' => $request->body,
                    'visibility' => $request->visibility,
                    'linkedin_post_id' => $share_request_body->id,
                    'share_type' => $share_type,
                    'shared_by' => $user_linkedin->pivot->username,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ];
                $post->linkedIn()->updateExistingPivot($linked_in->id, $additional_details);
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Post is shared successfully on your LinkedIn wall'
                ],200);
            } else {
                $post->linkedIn()->detach($linked_in->id);
                return response()->json([
                    'status' => 'Error',
                    'message' => 'There is some issue in Sharing your post on LinkedIn'
                ],422);
            }
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function shareMediaAssets($file_id, $owner_urn, $access_token, $post, $linkedIn):JsonResponse
    {
        try {
            $media = FoldersFiles::where('id', $file_id)->first();
            $media_path = Storage::disk('media')->path($media->hash_name);
            $upload_request = [
                'registerUploadRequest' => [
                    'owner' => $owner_urn,
                    'recipes' => [
                        'urn:li:digitalmediaRecipe:feedshare-image'
                    ],
                    'serviceRelationships' => [
                        [
                            'identifier' => 'urn:li:userGeneratedContent',
                            'relationshipType' => 'OWNER',
                        ],
                    ],
                    'supportedUploadMechanism' => [
                        'SYNCHRONOUS_UPLOAD'
                    ]
                ],
            ];
            $upload_request = $this->apiClient->request('POST',
                '/v2/assets?action=registerUpload',
                ['headers' => $this->headers, 'json' => $upload_request]);
            $upload_request = json_decode($upload_request->getBody());
            $upload_url = $upload_request->value->uploadMechanism->{'com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest'}->uploadUrl;
            $asset_id = $upload_request->value->asset;
            $upload_client = new Client();
            $asset_Response = $upload_client->request('PUT', $upload_url,
                ['headers' => ['Authorization' => 'Bearer '.$access_token], 'body' => fopen($media_path, 'r')]);

            $post->linkedIn()->attach($linkedIn->id, ['media_id' => $asset_id, 'content_type' => 'media']);
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
     * Method allow to share the posts to LinkedIn with media...
     * @param Request $request
     * @param $id
     * @param $user_id
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function reSharePosts(Request $request, $id, $user_id):JsonResponse
    {
        try {
            $linked_in = LinkedIns::where('id',$id)->first();
            $user = User::where('id', $user_id)->first();
            $post = Posts::where('id', $request->post_id)->first();
            $this->setAuthHeaders($id, $user_id);
            $user_linkedin = $user->linkedIn()->where('linkedins_id', $linked_in->id)->first();
            if ($request->type === 'organisation'){
                $urn = 'urn:li:organization:'.$user_linkedin->pivot->organisation_id;
            } else {
                $urn = 'urn:li:person:'.$user_linkedin->pivot->linkedin_user_id;
            }
            $post_linkedIn = $post->linkedIn()->where('linkedins_id', $linked_in->id)->first();
            if (!empty($post_linkedIn)) {
                if ($post_linkedIn->pivot->content_type != null) {
                    if ($post_linkedIn->pivot->visibility === 'everyone') {
                        if ($request->type === 'organization'){
                            $reShare_type_name = 'Organization';
                        } else {
                            $reShare_type_name = $user_linkedin->pivot->username;
                        }
                        if (empty($post_linkedIn->pivot->reshared_by)) {
                            $reshared_by = [$reShare_type_name];
                        } else {
                            $json_array = json_decode($post_linkedIn->pivot->reshared_by);
                            if (!in_array($reShare_type_name, $json_array)) {
                                $json_array[] = $reShare_type_name;
                            }
                            $reshared_by = $json_array;
                        }
                        $share_urn = 'urn:li:share:' . $post_linkedIn->pivot->linkedin_post_id;
                        $body = [
                            'originalShare' => $share_urn,
                            'resharedShare' => $share_urn,
                            'text' => [
                                'text' => $request->body . "\n\n" . $request->hashtags,
                            ],
                            'owner' => $urn
                        ];
                        $reShare_request = $this->apiClient->request('POST',
                            '/v2/shares', ['headers' => $this->headers, 'json' => $body]);

                        $details = [
                            'reshared' => true,
                            'reshared_by' => $reshared_by
                        ];
                        $post->linkedIn()->updateExistingPivot($id, $details);
                        return response()->json([
                            'status' => 'Success',
                            'message' => 'The Post is reShared successfully on your LinkedIn wall'
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 'Error',
                            'message' => 'The post is restricted to only connections, cannot be reShared !!!'
                        ],422);
                    }
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'The post without content cannot be reShared.'
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
     * Method allow to update the post shared on LinkedIn (Only text is edited)
     * @param Request $request
     * @param $id
     * @param $user_id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateSharesPosts(Request $request, $id, $user_id):JsonResponse
    {
        try {
            $request->validate([
                'body' => 'required|max:3000',
            ]);
            $this->setAuthHeaders($id, $user_id);
            $linked_in = LinkedIns::where('id',$id)->first();
            $post = Posts::where('id', $request->post_id)->first();
            $post_linkedIn = $post->linkedIn()->where('linkedins_id', $linked_in->id)->first();
            if (!empty($post_linkedIn)){
                $share_urn = 'urn:li:share:'.$post_linkedIn->pivot->linkedin_post_id;
                $body = [
                    'patch' => [
                        '$set' => [
                            'text' => [
                                'text' => $request->body,
                            ]
                        ]
                    ]
                ];
                $share_request = $this->apiClient->request('POST',
                    '/v2/shares/'.$share_urn, ['headers' => $this->headers, 'json' => $body]);
                $post->linkedIn()->updateExistingPivot($linked_in->id, ['body' => $request->body, 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')]);
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Post is updated successfully on your LinkedIn wall'
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

    public function deleteSharesPosts(Request $request, $id, $user_id):JsonResponse
    {
        try {
            $this->setAuthHeaders($id, $user_id);
            $post = Posts::where('id', $request->post_id)->first();
            $post_linkedIn = $post->linkedIn()->where('linkedins_id', $id)->first();
            if (!empty($post_linkedIn)){
                $share_request = $this->apiClient->request('DELETE',
                    '/v2/shares/'.$post_linkedIn->pivot->linkedin_post_id, ['headers' => $this->headers]);
                $post->linkedIn()->detach($id);
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Post is deleted successfully from your LinkedIn wall'
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
     * Method allow to disconnect the token details of the user attached
     * @param $id
     * @param $user_id
     * @return JsonResponse
     * @throws Exception
     */
    public function disconnectUser($id, $user_id):JsonResponse
    {
        try {
            $linked_in = LinkedIns::where('id',$id)->first();
            $user = User::where('id', $user_id)->first();
            $linkedIn_posts = $linked_in->posts;
            foreach ($linkedIn_posts as $linkedIn_post){
                $linkedIn = LinkedIns::where('id', $linkedIn_post->pivot->linkedins_id)->first();
                $linkedIn->posts()->updateExistingPivot($linkedIn_post->id, ['disconnected' => 1]);
            }

            $user->linkedIn()->detach($linked_in->id);

            return response()->json([
                'status' => 'Success',
                'message' => 'The User is disconnected from LinkedIn Successfully'
            ],200);

        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

} // End Class

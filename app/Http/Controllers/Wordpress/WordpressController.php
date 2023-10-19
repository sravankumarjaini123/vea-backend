<?php

namespace App\Http\Controllers\Wordpress;

use App\Http\Controllers\Controller;
use App\Models\Posts;
use App\Models\Wordpress;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Method allow to display list of all posts related to wordpress sites.
     * @return JsonResponse
     * @throws Exception
     */
    public function wordpressPosts():JsonResponse
    {
        try {
            $get_posts = Posts::where('shareable_type', 'wordpress')->where('shareable_posts', '!=', null)->get();
            return response()->json([
                'message' => 'Success',
                'wordpressPosts' => $get_posts,
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => 'Error',
                'message' => $ex->getMessage(),
            ], 500);
        }
    } //End function

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

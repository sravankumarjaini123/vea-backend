<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserAuth\UserController;
use App\Models\User;
use App\Models\UsersDevices;
use App\Models\UsersLogin;
use App\Models\WebsitesAppsSettingsGeneral;
use Carbon\Carbon;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Lcobucci\JWT\Parser as JwtParser;
use League\OAuth2\Server\AuthorizationServer;
use Nette\Schema\ValidationException;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use Exception;

class LoginController extends Controller
{

    use HandlesOAuthErrors;
    /**
     * The authorization server.
     *
     * @var AuthorizationServer
     */
    protected $server;

    /**
     * The token repository instance.
     *
     * @var TokenRepository
     */
    protected $tokens;

    /**
     * The JWT parser instance.
     *
     * @var \Lcobucci\JWT\Parser
     *
     * @deprecated This property will be removed in a future Passport version.
     */
    protected $jwt;

    /**
     * Create a new controller instance.
     *
     * @param  AuthorizationServer  $server
     * @param  TokenRepository  $tokens
     * @param  \Lcobucci\JWT\Parser  $jwt
     * @return void
     */
    public function __construct(AuthorizationServer $server,
                                TokenRepository $tokens,
                                JwtParser $jwt)
    {
        $this->jwt = $jwt;
        $this->server = $server;
        $this->tokens = $tokens;
    }

    /**
     * Method to allow the user to login with accessToken and refreshToken
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws \Nette\Schema\ValidationException
     */
    public function login(ServerRequestInterface $request):JsonResponse
    {
        try {
            $login_parameters = $request->getParsedBody();
            $email = $login_parameters['username'];
            $password = $login_parameters['password'];
            $client_id = $login_parameters['client_id'];
            $this->authenticate($email, $password, $login_parameters);
            $user = Auth::user();
            if(!empty($user)) {
                $login_details = [
                    'users_id' => $user->id,
                    'ip' => (!empty($login_parameters['ip'])) ? $login_parameters['ip'] : null,
                    'date' => Carbon::now(),
                    'browser_agent' => (!empty($login_parameters['browser_agent'])) ? $login_parameters['browser_agent'] : null,
                    'status' => 1,
                ];
                $store_login_details = UsersLogin::create($login_details);
            }

            if ($user && $user->is_blocked != 1){
                if ($client_id == 4){
                    $device_request = new Request();
                    $device_request->setMethod('POST');
                    $device_request->user_id = $user->id;
                    $device_request->device_id = $login_parameters['device_id'];
                    $device_request->device_name = $login_parameters['device_name'];
                    $device_request->device_model = $login_parameters['device_model'];
                    $device_request->device_location = $login_parameters['device_location'] ?? null;
                    $status = $this->userDeviceStatus($device_request);
                    if ($status->getStatusCode() === 210 || $status->getStatusCode() === 422) {
                        return response()->json([
                            'status' => $status->getData()->status,
                            'message' => $status->getData()->message,
                        ],210);
                    }
                } else {
                    $this->OldTokenExpiration($user, $client_id);
                }

                $tokenDetails = $this->issueToken($request);
                if ($client_id == 4) {
                    $tokens = Auth::user()->tokens()->where('revoked', 0)->get();
                    foreach ($tokens as $token) {
                        RefreshToken::where('access_token_id', $token->id)->update(['expires_at' => Carbon::now()->addDays(7)]);
                    }
                }
                $token_contents = json_decode((string)$tokenDetails->content(), true);

                if ($user->two_factor_secret == null) {
                    $user_details = new ContactController();
                    $user = User::where('id', $user->id)->first();
                    $userDetails = $user_details->getContactDetails($user);

                    return response()->json([
                        'tokenDetails' => $token_contents,
                        'userDetails' => $userDetails,
                        'status' => 'Success',
                        'message' => 'Welcome to Omnics Manager, Explore our new features.',
                    ], 200);
                } else {

                    // Get the user details as response
                    $user_details = [
                        'id' => $user->id,
                        'has2FA' => true,
                    ];
                    return response()->json([
                        'status' => 'Success',
                        'userDetails' => $user_details,
                    ], 200);
                }
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Error in logging into the system, Please try after some time',
                ],400);
            }
        } catch (\Nette\Schema\ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ],500);
        }
    } // End Function

    public function OldTokenExpiration($user, $client_id):void
    {
        $tokens = $user->tokens()->where('client_id', $client_id)->pluck('id');
        if (!empty($tokens)) {
            foreach ($tokens as $token) {
                $token_update = Token::where('id', $token)->update(['revoked' => true]);
                if ($token_update) {
                    RefreshToken::where('access_token_id', $token)->update(['revoked' => true]);
                }
            }
        }
    } // End Function

    /**
     * Authorize a client to access the user's account.
     *
     * @param  ServerRequestInterface  $request
     * @return \Illuminate\Http\Response
     */
    public function issueToken(ServerRequestInterface $request)
    {
        return $this->withErrorHandling(function () use ($request) {
            return $this->convertResponse(
                $this->server->respondToAccessTokenRequest($request, new Psr7Response)
            );
        });
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate($email, $password, $login_parameters = null)
    {
        $this->ensureIsNotRateLimited($email);


        if (! Auth::attempt(['email'=>$email, 'password'=>$password])) {
            $get_user = User::where('email', $email)->first();
            if($get_user) {
                $login_details = [
                    'users_id' => $get_user->id,
                    'ip' => (!empty($login_parameters['ip'])) ? $login_parameters['ip'] : null,
                    'date' => Carbon::now(),
                    'browser_agent' => (!empty($login_parameters['browser_agent'])) ? $login_parameters['browser_agent'] : null,
                    'status' => 0,
                ];
                $store_login_details = UsersLogin::create($login_details);
            }
            RateLimiter::hit($this->throttleKey($email));

            throw \Illuminate\Validation\ValidationException::withMessages([
                'content' => __('auth.failed'),
            ]);
        }
        RateLimiter::clear($this->throttleKey($email));
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited($email)
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($email), 5)) {
            return;
        }
        $request = new \Illuminate\Http\Request();

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($email));

        throw \Illuminate\Validation\ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     * @param $email
     * @return string
     */
    public function throttleKey($email):string
    {
        return Str::lower($email);
    }

    public function userDeviceStatus($request):JsonResponse
    {
        try {
            $user = User::where('id', $request->user_id)->first();
            $general_setting = WebsitesAppsSettingsGeneral::where('id', 7)->first();
            $user_devices = $user->userDevice()->count();
            $user_device = $user->userDevice()->where('devices_id', $request->device_id)->first();
            if (empty($user_device)){
                if ((int)$general_setting->answer === $user_devices) {
                    return response()->json([
                        'status' => 'No-Content',
                        'message' => 'The respective device or try again disconnecting your previous devices, Please contact administrator',
                    ], 210);
                } elseif ((int)$general_setting->answer > $user_devices) {
                    DB::table('users_devices')->insert([
                        'users_id' => $user->id,
                        'devices_id' => $request->device_id,
                        'devices_name' => $request->device_name ?? null,
                        'devices_model' => $request->device_model ?? null,
                        'devices_location' => $request->device_location ?? null,
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                    return response()->json([
                        'status' => 'Success',
                        'message' => 'User device is registered and can continue to login',
                    ], 201);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'More devices are registered than desired, Please contact Administrator',
                        'custom_error_code' => 435,
                    ], 422);
                }
            } else {
                return response()->json([
                    'status' => 'Success',
                    'message' => 'User can be moved to Login',
                ], 200);
            }
        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ],500);
        }
    } // End Function

    /**
     * Method allow to get the details of the User with tokens and details
     * @param  ServerRequestInterface  $request
     * @param  $id
     * @return JsonResponse
     * @throws Exception
     */
    public function loginAfter2FA(ServerRequestInterface $request, $id):JsonResponse
    {
        try {
            $tokenDetails = $this->issueToken($request);
            $token_contents = json_decode((string)$tokenDetails->content(), true);
            $contact_details = new ContactController();
            $contact = User::where('id', $id)->first();
            $result = $contact_details->getContactDetails($contact);
            return response()->json([
                'tokenDetails' => $token_contents,
                'userDetails' => $result,
                'status' => 'Success',
                'message' => 'Welcome to Omnics Manager, Explore our new features.',
            ], 200);
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function
}

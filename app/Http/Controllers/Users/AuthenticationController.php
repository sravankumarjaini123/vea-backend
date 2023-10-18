<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\UserController;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Lcobucci\JWT\Parser as JwtParser;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use Nette\Schema\ValidationException;
use Exception;

class AuthenticationController extends Controller
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
     * @throws ValidationException
     */
    public function login(ServerRequestInterface $request):JsonResponse
    {
        try {
            $login_parameters = $request->getParsedBody();
            $email = $login_parameters['username'];
            $password = $login_parameters['password'];
            $client_id = $login_parameters['client_id'];
            $this->authenticate($email, $password);
            $user = Auth::user();

            if ($user){
                if ($user->sys_admin == true) {
                    $tokens = $user->tokens()->where('client_id', $client_id)->pluck('id');
                    if (!empty($tokens)) {
                        foreach ($tokens as $token) {
                            $token_update = Token::where('id', $token)->update(['revoked' => true]);
                            if ($token_update) {
                                RefreshToken::where('access_token_id', $token)->update(['revoked' => true]);
                            }
                        }
                    }
                    $tokenDetails = $this->issueToken($request);
                    $token_contents = json_decode((string)$tokenDetails->content(), true);

                    if ($user->two_factor_secret == null) {
                        $user_details = new UserController();
                        $userDetails = $user_details->userDetails($user->id, 'login');

                        return response()->json([
                            'tokenDetails' => $token_contents,
                            'userDetails' => $userDetails,
                            'status' => 'Success',
                            'message' => 'Welcome to our System, Explore our new features.',
                        ], 200);
                    } else {
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
                        'status' => 'Unauthorized',
                        'message' => 'You are not allowed to log into the system, Please contact administrator',
                    ], 401);
                }
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Error in logging into the system, Please try after some time',
                ],400);
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ],500);
        }
    } // End Function

    /**
     * Authorize a client to access the user's account.
     *
     * @param  ServerRequestInterface  $request
     * @return Response
     */
    public function issueToken(ServerRequestInterface $request):Response
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
    public function authenticate($email, $password):void
    {
        $this->ensureIsNotRateLimited($email);

        if (! Auth::attempt(['email'=>$email, 'password'=>$password])) {
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

    /**
     * Method to Destroy authentication session
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            $tokens =  $user->tokens->pluck('id');
            if (!empty($tokens)){
                // REVOKE - TOKENS
                Token::whereIn('id', $tokens)
                    ->update(['revoked' => true]);
                RefreshToken::whereIn('access_token_id', $tokens)->update(['revoked' => true]);

                // DELETE - TOKENS
                Auth::guard('api')->user()->tokens->each(function ($token, $key){
                    $token->delete();
                });

                return response()->json([
                    'status' => 'Success',
                    'message' => 'Thank you and we are looking forward to see you again.',
                ], 200);

            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'First login to the system',
                ], 200);
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
            $user_details = new UserController();
            $userDetails = $user_details->userDetails($id, 'login');
            return response()->json([
                'tokenDetails' => $token_contents,
                'userDetails' => $userDetails,
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

<?php

namespace App\Http\Controllers\TwoFactorAuthentication;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Nette\Schema\ValidationException;
use Psr\Http\Message\ServerRequestInterface;

class TwoFactorController extends Controller
{
    protected $recovery_code = '';
    protected $verify_code = '';
    public function passwordConfirmation(Request $request, $id):JsonResponse
    {
        try {
            $request->validate([
                'password' => 'required',
            ]);
            $user = DB::table('users')->where('id',$id)->first();
            if ($user){
                $currentPassword = $request->password;
                if (Hash::check($currentPassword,$user->password)) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Password confirmation is successful',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Please enter the correct password',
                    ], 400);
                }
            }else{
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
     * Get the QR-Codes of the user in SVG format
     *
     * @param  $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id): JsonResponse
    {
        try {
            $user = DB::table('users')->where('id',$id)->first();
            if ($user->two_factor_secret != null){
                $svg = User::where('id',$id)->first()->twoFactorQrCodeSvg();
                return response()->json([
                    'status' => 'Success',
                    'svg' => $svg,
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please enable the two factor authentication',
                ],404);
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
     * Get the Recovery Codes of the QR Code in case of mobile loss
     *
     * @param  $id
     * @return JsonResponse
     * @throws Exception
     */
    public function index($id):JsonResponse
    {
        try {
            $user = DB::table('users')->where('id',$id)->first();
            if ($user->two_factor_secret != null){
                $recovery_codes = json_decode(decrypt($user->two_factor_recovery_codes));
                return response()->json([
                    'status' => 'Success',
                    'recoveryCodes' => $recovery_codes,
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please enable the two factor authentication',
                ],404);
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
     * Enable two factor authentication for the user.
     *
     * @param  EnableTwoFactorAuthentication  $enable
     * @param  $id
     * @return JsonResponse
     * @throws Exception
     */
    public function store(EnableTwoFactorAuthentication $enable, $id):JsonResponse
    {
        try {
            $user = DB::table('users')->where('id',$id)->first();
            if ($user->two_factor_secret == null){
                $enable(User::where('id',$id)->first());
                return response()->json([
                    'status' => 'success',
                    'message' => 'The two factor authentication is enabled successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'two factor authentication is enabled already',
                ],400);
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
     * Disable two factor authentication for the user.
     *
     * @param  DisableTwoFactorAuthentication  $disable
     * @param  $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(DisableTwoFactorAuthentication $disable, $id):JsonResponse
    {
        try {
            $user = DB::table('users')->where('id',$id)->first();
            if ($user->two_factor_secret != null) {
                $disable(User::where('id',$id)->first());
                return response()->json([
                    'status' => 'success',
                    'message' => 'The two factor authentication is disabled successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please enable the two factor authentication',
                ],404);
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
     * Verify two factor authentication for the user.
     * @param  Request  $request
     * @param  $id
     * @return JsonResponse
     * @throws Exception
     */
    public function verify(Request $request, $id):JsonResponse
    {
        try {
            $request->validate([
                'recovery_code' => 'required_without_all:verify_code',
                'verify_code' => 'required_without_all:recovery_code'
            ]);
            if ($request->recovery_code != null){
                $this->recovery_code = $request->recovery_code;
                $code_first = collect(User::find($id)->recoveryCodes())->first(function ($code) {
                    return hash_equals($this->recovery_code, $code) ? $code : null;
                });
                if ($code_first != null){
                    User::find($id)->replaceRecoveryCode($code_first);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'The two factor authentication is success',
                    ],200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Please enter the correct recovery code and try again!',
                    ], 422);
                }
            } else {
                $this->verify_code = $request->verify_code;
                $user = DB::table('users')->where('id',$id)->first();
                $code_verify = $this->verify_code && app(TwoFactorAuthenticationProvider::class)->verify(
                        decrypt($user->two_factor_secret), $this->verify_code);
                if ($code_verify){
                    return response()->json([
                        'status' => 'success',
                        'message' => 'The two factor authentication is success',
                    ],200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Please enter the correct code and try again!',
                    ], 422);
                }
            }
        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function
}

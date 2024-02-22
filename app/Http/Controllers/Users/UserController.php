<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Partners\PartnersController;
use App\Models\EmailsSettings;
use App\Models\User;
use App\Models\UsersForgotPasswords;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Nette\Schema\ValidationException;
use App\Jobs\SendPasswordsAndRegistrationsMails;

class UserController extends Controller
{
    /**
     * Method allow to display list of all USERS
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $all_users = User::where('sys_admin',1)->get();
            $users_details = array();
            if(!empty($all_users)){
                foreach ($all_users as $all_user)
                {
                    $users_details[] = $this->userDetails($all_user->id);
                }
            }
            return response()->json([
                'users' => $users_details,
                'message' => 'Success'
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
     * Method allow to show the particular group.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            if (User::where('id',$id)->exists()){
                $user_details = $this->userDetails($id);
                return response()->json([
                    'user' => $user_details,
                    'message' => 'Success'
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
     * Method allow to get the details of the login user
     * @return JsonResponse
     * @throws Exception
     */
    public function getUserProfile():JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            $user_details = $this->userDetails($user->id);
            return response()->json([
                'user' => $user_details,
                'message' => 'Success'
            ],200);
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to assign the groups for Partners.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateLabels(Request $request, $id):JsonResponse
    {
        try {
            if (User::where('id',$id)->where('sys_admin', 0)->where('sys_customer', 1)->exists()) {
                $user = User::where('id',$id)->first();
                $user_labels = $user->labels;
                // Delete related records
                if (!empty($user_labels)){
                    foreach ($user_labels as $user_label){
                        $user->labels()->detach($user_label->id);
                    }
                }
                // Save new related data
                if (!empty($request->labels_id)){
                    foreach ($request->labels_id as $label_id){
                        $user->labels()->attach($label_id, ['created_at'=>Carbon::now()->format('Y-m-d H:i:s')]);
                    }
                }
                // Build Response array
                $updated_user = User::where('id',$id)->first();
                $user_label_details = $updated_user->labels;
                $details = new PartnersController();
                $user_details = $details->getPartnersDetails($user_label_details);

                return response()->json([
                    'contactDetails' => $user_details,
                    'status' => 'Success',
                    'message' => 'Labels are updated successfully'
                ], 200);
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

    /**
     * Method allow to update the new email/username.
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function contactsLoginsList($id):JsonResponse
    {
        try {
            $user_logins = DB::table('users_login')->where('users_id',$id)->get();

            if(!empty($user_logins)) {
                return response()->json([
                    'status' => 'success',
                    'user_logins' => $user_logins,
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'There is no relevant information for selected query',
                ], 210);
            }
        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Method allow to retrieve the user Details
     * @param $id
     * @param null $condition
     * @return array
     */
    public function userDetails($id, $condition = null):array
    {
        $user = User::where('id',$id)->first();
        $userDetails = array();
        if (!empty($user)) {
            if ($condition != null) {
                $user->last_login_at = Carbon::now()->format('Y-m-d H:i:s');
                $user->save();
            }
            if ($user->titles_id != null) {
                $title = $user->title->title;
            } else {
                $title = null;
            }
            if ($user->two_factor_secret != null) {
                $has2FA = true;
            } else {
                $has2FA = false;
            }
            $users_roles = $user->roles;
            $role_details = array();
            foreach ($users_roles as $users_role) {
                $role_details = [
                    'id' => $users_role->id,
                    'name' => $users_role->name,
                ];
            }
            if ($user->profile_photo_id != null) {
                $profile_photo_url = $user->profilePhoto->file_path;
            } else {
                $profile_photo_url = null;
            }

            // Connected Accounts
            $user_twitters = $user->twitter;
            $connected_accounts = array();
            $twitter = array();
            if (!empty($user_twitters)) {
                foreach ($user_twitters as $user_twitter) {
                    if (!empty($user_twitter->id)) {
                        $twitter[] = [
                            'id' => $user_twitter->id,
                            'app_name' => $user_twitter->app_name,
                            'username' => $user_twitter->pivot->username,
                            'profile_picture_url' => $user_twitter->pivot->profile_picture_url,
                            'auth_type' => $user_twitter->pivot->auth_type,
                            'shareable_password' => $user_twitter->pivot->shareable_password,
                            'user_twitter_detail_id' => $user_twitter->pivot->id,
                            'auth_id' => $user_twitter->pivot->auth_id,
                        ];
                    }
                }
            }
            $user_linkedIns = $user->linkedIn;
            $linkedIn = array();
            if (!empty($user_linkedIns)) {
                foreach ($user_linkedIns as $user_linkedIn) {
                    $linkedIn[] = [
                        'id' => $user_linkedIn->id,
                        'app_name' => $user_linkedIn->app_name,
                        'username' => $user_linkedIn->pivot->username,
                        'profile_picture_url' => $user_linkedIn->pivot->profile_picture_url,
                    ];
                }
            }
            $connected_accounts = array_merge($connected_accounts, ['twitter' => $twitter], ['linkedIn' => $linkedIn]);

            $userDetails = [
                'id' => $user->id,
                'salutation_id' => $user->salutations_id,
                'salutation' => $user->salutation->salutation,
                'title_id' => $user->titles_id,
                'title' => $title,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'username' => $user->username,
                'profile_photo_id' => $user->profile_photo_id,
                'profile_photo_url' => $profile_photo_url,
                'has2FA' => $has2FA,
                'sys_admin' => $user->sys_admin,
                'sys_customer' => $user->sys_customer,
                'connected_accounts' => $connected_accounts,
                'role' => $role_details,
                'last_login' => $user->last_login_at,
            ];
        }
        return $userDetails;
    } // End Function

    /**
     * Method allow to update the new email/username.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateEmail(Request $request, $id):JsonResponse
    {
        try {
            $auth_user = Auth::guard('api')->user();
            $user = User::where('id',$id)->first();
            $request->validate([
                'email' => ['required','email','string', Rule::unique('users', 'email')->ignore($user->id)],
                'confirm_password' => 'required',
            ]);
            $confirm_password = $request->confirm_password;
            if ($user) {
                if ($user->email !== $request->email) {
                    if (Hash::check($confirm_password, $user->password)) {
                        // Save the changes to the database
                        $email = [
                            'email' => $request->email
                        ];
                        User::where('id',$id)->update($email);
                        $updatedEmail = User::where('id',$id)->first();
                        // After changing if the User is same then User need to logout.
                        $this->revokeAndDeleteTokens($user);
                        return response()->json([
                            'email' => $updatedEmail->email,
                            'status' => 'success',
                            'message' => 'The email is changed successfully.',
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Please enter the correct Password and try again.',
                        ], 404);
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'The new email address is same as the old email address',
                    ], 406);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'There is no relevant information for selected query',
                ], 210);
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
     * Method allow to revoke the existing token and delete them
     * @param $user
     * @return bool
     */
    public function revokeAndDeleteTokens($user):bool
    {
        $tokens =  $user->tokens->pluck('id');
        if (!$tokens->isEmpty()){
            // REVOKE - TOKENS
            Token::whereIn('id', $tokens)
                ->update(['revoked' => true]);
            RefreshToken::whereIn('access_token_id', $tokens)->update(['revoked' => true]);

            // DELETE - TOKENS
            Auth::guard('api')->user()->tokens->each(function ($token, $key){
                $token->delete();
            });
        }
        return true;
    } // End Function

    /**
     * Method allow to update the new password.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updatePassword(Request $request, $id):JsonResponse
    {
        try {
            $user = User::where('id',$id)->first();

            if ($user) {
                if( $user->sys_admin == 0 && $user->sys_customer == 1) {
                    $request->validate([
                        'password' => ['required'],
                    ]);
                    $password = [
                        'password' => Hash::make($request->password)
                    ];
                    User::where('id', $id)->update($password);

                } else {
                    $request->validate([
                        'current_password' => 'required',
                        'password' => ['required'],
                    ]);
                    $current_password = $request->current_password;
                    if (!empty($user->password) && Hash::check($current_password, $user->password)) {

                        // Save the change of password to database
                        $password = [
                            'password' => Hash::make($request->password)
                        ];
                        User::where('id', $id)->update($password);
                        $this->revokeAndDeleteTokens($user);

                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'The current password does not match, Please try again',
                        ], 203);
                    }
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'The password has changed Successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'There is no relevant information for selected query',
                ], 210);
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
     * Method allow to update the personal details of the user
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updatePersonal(Request $request, $id):JsonResponse
    {
        try {
            $user = DB::table('users')->where('id',$id)->first();
            $request->validate([
                'salutation_id' => 'required|integer',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
            ]);
            if (!$user->sys_admin){
                $request->validate([
                    'username' => ['required','string', Rule::unique('users', 'username')->ignore($user->id)]
                ]);
            }
            if ($user) {
                if ($request->profile_photo_id == null) {
                    $profile_photo_id = $user->profile_photo_id;
                } else {
                    $profile_photo_id = $request->profile_photo_id;
                }

                $personalDetails = [
                    'salutations_id' => $request->salutation_id,
                    'titles_id' => $request->title_id,
                    'firstname' => $request->first_name,
                    'lastname' => $request->last_name,
                    'username' => $request->username,
                    'profile_photo_id' => $profile_photo_id
                ];
                User::where('id', $id)->update($personalDetails);
                $updatedUser = User::where('id',$id)->first();
                if ($updatedUser->titles_id != null) {
                    $title = $updatedUser->title->title;
                } else {
                    $title = null;
                }
                if ($updatedUser->profile_photo_id != null) {
                    $profile_photo_url = $updatedUser->profilePhoto->file_path;
                } else {
                    $profile_photo_url = null;
                }

                $user_details = [
                    'salutation_id' => $updatedUser->salutations_id,
                    'salutation' => $updatedUser->salutation->salutation,
                    'title_id' => $updatedUser->titles_id,
                    'title' => $title,
                    'first_name' => $updatedUser->firstname,
                    'last_name'=> $updatedUser->lastname,
                    'username' => $updatedUser->username,
                    'profile_photo_id' => $user->profile_photo_id,
                    'profile_photo_url' => $profile_photo_url,
                ];
                return response()->json([
                    'userDetails' => $user_details,
                    'status' => 'success',
                    'message' => 'The personal details of user updated successfully',
                ], 200);

            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'There is no relevant information for selected query',
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
     * Method allow to update the roles for Users
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateUserRoles(Request $request, $id):JsonResponse
    {
        try {
            if (User::where('id', $id)->exists()) {
                $user = User::where('id',$id)->first();
                $count = $this->checkForSuperAdministrator($request->user_role_id, $id);
                if ($count > 0) {
                    $user->roles()->detach();
                    $user->roles()->attach($request->user_role_id);
                    return response()->json([
                        'status' => 'Success',
                        'message' => 'The roles had been assigned to the user respectively',
                    ], 200);
                } return response()->json([
                    'status' => 'Error',
                    'message' => 'There should be at least one Super Administrator for the system'
                ],422);
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
     * Method allow to check whether atleast one super administrator is present in the system
     * @param $request
     * @param $id
     * @return boolean
     */
    public function checkForSuperAdministrator($role_id, $id):bool
    {
        $all_users = User::where('sys_admin', 1)->where('sys_customer', 1)->where('id','!=',$id)->get();
        $count = 0;
        if ($role_id != 1){
            foreach ($all_users as $all_user){
                $users_roles = $all_user->roles;
                foreach ($users_roles as $users_role){
                    if ($users_role->slug == 'super-administrator'){
                        $count++;
                    }
                }
            }
        } else {
            $count = 1;
        }
        return $count;
    } // End Function

    /**
     * Method allow to reteive the all the notifications of the particular user
     * @param $id
     * @return JsonResponse
     */
    public function notifications($id):JsonResponse
    {
        try {
            if(User::where('id',$id)->exists()){
                $user = User::where('id',$id)->first();
                $user_notifications = $user->notifications()->latest()->get();
                $result_notifications = array();
                foreach ($user_notifications as $user_notification){
                    $result_notifications[] = [
                        'data_id' => $user_notification->data_id,
                        'data_name' => $user_notification->data_name,
                        'notification_type' => $user_notification->notification_type,
                        'data_channel' => $user_notification->data_channel,
                        'status' => $user_notification->status,
                        'error_message' => $user_notification->error_message,
                        'updated_at' => $user_notification->updated_at,
                    ];
                }
                return response()->json([
                    'notificationDetails' => $result_notifications,
                    'status' => 'success',
                ], 200);

            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'There is no relevant information for selected query',
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
     * Method allow to generate codes for Emails
     * @param $email
     * @param $table
     * @return int
     */
    public function checkAndGenerateCode($email, $table):int
    {
        $codes_checks = DB::table($table)->where('email', $email)
            ->where('status', 'created')->get();
        if (!empty($codes_checks)) {
            foreach ($codes_checks as $codes_check) {
                DB::table($table)->where('id', $codes_check->id)->update([
                    'status' => 'expired',
                    'expired_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }
        }
        $x = 0;
        do {
            $code = $this->generateCode(12);
            if (DB::table($table)->where('code', '=', $code)->first()) {
                $x = 1;
            }
        } while ($x > 0);
        $code_id = DB::table($table)->insertGetId([
            'email' =>  $email,
            'base64_email' => base64_encode($email),
            'code' => $code,
            'status' => 'created',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        return $code_id;
    } // End Function

    /**
     * Method allow to confirm the email for sending the email for forgot password
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function emailConfirmationWithCode(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'technology' => 'required|in:system,app',
            ]);
            if ($request->technology === 'system'){
                $user = User::where('email', $request->email)->where('sys_admin',1)->first();
                $condition = 'system_forgot_password';
            } else {
                $user = User::where('email', $request->email)->first();
                $condition = 'app_forgot_password';
            }
            if (!empty($user)){
                $email_settings_details = EmailsSettings::where('technologies', $request->technology)
                    ->where('name','forgot_password')->first();
                if ($email_settings_details->emails_id != null && $email_settings_details->emails_templates_id != null){
                    $email_id = $email_settings_details->emails_id;
                    $email_template_id = $email_settings_details->emails_templates_id;
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Email settings are not been set, please contact administrator!!!',
                    ],422);
                }
                $code_id = $this->checkAndGenerateCode($user->email, 'users_forgot_passwords');
                if (!empty($code_id)){
                    SendPasswordsAndRegistrationsMails::dispatch($email_id, $email_template_id, $user->id, $code_id, $condition, $email_settings_details->id);
                    return response()->json([
                        'status' => 'Success',
                        'message' => 'The email is sent to desired email with Code to reset your password.',
                    ],200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'There is some issue in code generation, please try again after some time!!!',
                    ],422);
                }
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'The email is not present in the system. Please contact administrator!!!',
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
     * Method allow to confirm the code and process further for reset
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function codeConfirmation(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required',
                'code' => 'required',
                'technology' => 'nullable|in:app',
            ]);
            if ($request->technology === 'app'){
                $email_code = UsersForgotPasswords::where('email', $request->email)->where('code', $request->code)->first();
            } else {
                $email_code = UsersForgotPasswords::where('email', base64_decode($request->email))->where('code', $request->code)->first();
            }
            if (!empty($email_code)){
                if ($email_code->status === 'created'){
                    $email_code->status = 'confirmed';
                    $email_code->save();
                    return response()->json([
                        'status' => 'Success',
                        'message' => 'The code is confirmed',
                    ],200);
                } elseif ($email_code->status === 'confirmed'){
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'The code is already been used, Please process again!!!',
                    ],422);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'The code is expired, Please process again!!!',
                    ],422);
                }
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'There is no relevant information for selected query',
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
     * Method allow to reset the password and allow to Login with new password
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function resetPassword(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'technology' => 'nullable|in:app',
            ]);
            if ($request->technology === 'app'){
                $email = $request->email;
            } else {
                $email = base64_decode($request->email);
            }
            $email_code = UsersForgotPasswords::where('email', $email)->latest('created_at')->first();
            if ($email_code->status === 'confirmed' && $email_code->expired_at == null) {
                if (User::where('email', $email)->exists()) {
                    $password = [
                        'password' => Hash::make($request->password)
                    ];
                    User::where('email', $email)->update($password);
                    $email_code->expired_at = Carbon::now()->format('Y-m-d H:i:s');
                    $email_code->save();
                    return response()->json([
                        'status' => 'Success',
                        'message' => 'The password is reset successfully',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'There is no relevant information for selected query',
                    ], 210);
                }
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'There is some issue for reset of your password, Please process again',
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
     * Method allow to delete the system User
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (User::where('id',$id)->exists()) {
                $user = User::where('id', $id)->first();
                $tokens =  $user->tokens->pluck('id');
                Token::whereIn('id', $tokens)
                    ->update(['revoked' => true]);

                RefreshToken::whereIn('access_token_id', $tokens)->update(['revoked' => true]);
                $accessToken = $user->tokens->each(function ($token, $key){
                    $token->delete();
                });
                User::where('id',$id)->delete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The User is removed successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'There is no relevant information for selected query',
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
}

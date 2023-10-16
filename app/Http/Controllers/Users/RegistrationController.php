<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\UserController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Nette\Schema\ValidationException;
use Illuminate\Validation\Rules;
use Exception;

class RegistrationController extends Controller
{
    /**
     * Method to allow the New sys_user to Register
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function register(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'salutation_id' => 'required|integer',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'role_id' => 'required|integer',
            ]);

            $user_id = User::insertGetId([
                'salutations_id' => $request->salutation_id,
                'titles_id' => $request->title_id,
                'firstname' => $request->first_name,
                'lastname' => $request->last_name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'sys_admin' => true,
                'sys_customer' => true,
            ]);

            if ($user_id != null){
                $user = User::where('id',$user_id)->first();
                $user->roles()->attach($request->role_id);
                $user_controller = new UserController();
                $user_details = $user_controller->userDetails($user->id);
                return response()->json([
                    'userDetails' => $user_details,
                    'status' => 'Success',
                    'message' => 'Registration is successful, Login to experience our features.',
                ], 200);
            } else{
                return response()->json([
                    'status' => 'Database - Error',
                    'message' => 'Problem in registering user, try again after some time',
                ], 205);
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
     * Method allow to delete the system User
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (User::where('id',$id)->exists()) {
                $all_users = User::where('sys_admin', 1)->where('sys_customer', 1)->where('id','!=',$id)->get();
                $count = 0;
                if (!empty($all_users)) {
                    foreach ($all_users as $all_user) {
                        $users_roles = $all_user->roles;
                        foreach ($users_roles as $users_role) {
                            if ($users_role->slug == 'super-administrator') {
                                $count++;
                            }
                        }
                    }
                }
                if ($count > 0) {
                    User::where('id', $id)->delete();
                    return response()->json([
                        'status' => 'Success',
                        'message' => 'The User is removed successfully',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'There should be at least one Super Administrator for the system'
                    ],422);
                }
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

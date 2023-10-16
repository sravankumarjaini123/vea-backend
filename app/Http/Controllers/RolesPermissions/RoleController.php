<?php

namespace App\Http\Controllers\RolesPermissions;

use App\Http\Controllers\Controller;
use App\Models\Permissions;
use App\Models\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;

class RoleController extends Controller
{
    /**
     * Method allow to display list of all roles
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $roles = Roles::all();
            $result = $this->getRoleDetails($roles);
            return response()->json([
                'rolesDetails' => $result,
                'message' => 'Success',
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function getRoleDetails($roles):array
    {
        $result = array();
        if (!empty($roles)){
            foreach ($roles as $role){
                $role_users = $role->users;
                $users_array = array();
                if (!empty($role_users)) {
                    foreach ($role_users as $role_user) {

                        $profile_photo_url = null;
                        $url = URL::to('/');
                        if(!empty($role_user->profile_photo_url)) {
                            $profile_photo_url = $url. '/storage/media/' .$role_user->profile_photo_url;
                        }
                        $users_array[] = [
                            'id' => $role_user->id,
                            'name' => $role_user->firstname . ' ' . $role_user->lastname,
                            'profile_photo_url' =>  $profile_photo_url,
                            'email' => $role_user->email,
                        ];
                    }
                }
                $role_resources = $role->resources;
                $permissions_array = array();
                if (!empty($role_resources)){
                    foreach ($role_resources as $role_resource){
                        $permissions_array[] = [
                            'resource_id' => $role_resource->id,
                            'resource_name' => $role_resource->name,
                            'resource_slug' => $role_resource->slug,
                            'permission_id' => $role_resource->pivot->permissions_id,
                        ];
                    }
                }
                $roles_array = [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'count_of_users' => count($role_users),
                ];
                $result[] = array_merge($roles_array, ['users' => $users_array], ['resources' => $permissions_array]);
            }
        }
        return $result;
    } // End Function

    /**
     * Method allow to store or create the new Role.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:roles'
            ]);
            $convertedString = $this->convertToEnglish($request->name);
            $slug = Str::slug($convertedString, '-');
            $role_id = DB::table('roles')->insertGetId([
                'name' => $request->name,
                'slug' => $slug,
            ]);
            return response()->json([
                'status' => 'Success',
                'message' => 'Role is added successfully',
            ],200);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to show the single role details
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function show($id):JsonResponse
    {
        try {
            if (Roles::where('id', $id)->exists()){
                $role = Roles::where('id', $id)->get();
                $result = array();
                $roles_array = $this->getRoleDetails($role);
                foreach ($roles_array as $roles) {
                    $result = $roles;
                }
                return response()->json([
                    'rolesDetails' => $result,
                    'message' => 'Role is added successfully',
                ],200);
            } else {
                return response()->json([
                    'success' => 'No Content',
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
     * Method allow to update the name of the particular group.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            $role = Roles::where('id',$id)->first();
            $request->validate([
                'name' => ['required','string', Rule::unique('roles', 'name')->ignore($role->id)]
            ]);

            if (Roles::where('id',$id)->exists()){
                $convertedString = $this->convertToEnglish($request->name);
                $slug = Str::slug($convertedString, '-');

                DB::table('roles')->where('id', $id)
                    ->update(['name' => $request->name, 'slug' => $slug]);

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Role is updated successfully',
                ],200);
            } else{
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
     * Method allow to update resource permissions of the role.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateRolesResources(Request $request, $id):JsonResponse
    {
        try {
            if (Roles::where('id', $id)->exists()){
                $role = Roles::where('id', $id)->first();
                if ($role->id != 1){
                    foreach ($request->roles_resources as $roles_resource){
                        $resources_id = $roles_resource['resource_id'];
                        $role->resources()->detach($resources_id);
                        foreach ($roles_resource['permissions_id'] as $permission_id){
                            $role->resources()->attach($resources_id, ['permissions_id' => $permission_id]);
                        }
                    }
                }
                if ($request->name != null){
                    $convertedString = $this->convertToEnglish($request->name);
                    $slug = Str::slug($convertedString, '-');
                    DB::table('roles')->where('id', $id)->update([
                        'name' => $request->name,
                        'slug' => $slug,
                    ]);
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Resource permissions had been updated successfully',
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
     * Method allow to delete the particular group.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (Roles::where('id',$id)->exists()){
                if ($id != 1) {
                    Roles::where('id', $id)->delete();

                    return response()->json([
                        'status' => 'Success',
                        'message' => 'The Role is deleted successfully',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Cannot delete the super administrator'
                    ],422);
                }
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

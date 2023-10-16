<?php

namespace App\Http\Controllers\RolesPermissions;

use App\Http\Controllers\Controller;
use App\Models\Resources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;

class ResourceController extends Controller
{
    /**
     * Method allow to display list of all Permissions
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $resources = Resources::all();
            $result = array();
            if (!empty($resources)) {
                foreach ($resources as $resource) {
                    $resource_details = Resources::where('id', $resource->id)->first();
                    $resource_array = [
                        'id' => $resource->id,
                        'name' => $resource->name,
                        'slug' => $resource->slug,
                    ];
                    $resources_roles = $resource_details->roles;
                    $roles = array();
                    if (!empty($resources_roles)) {
                        foreach ($resources_roles as $resources_role) {
                            $roles[] = [
                                'id' => $resources_role->id,
                                'name' => $resources_role->name,
                            ];
                        }
                    }
                    $result[] = array_merge($resource_array, ['roles' => $roles]);
                }
            }
            return response()->json([
                'data' => $result,
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
     * Method allow to store or create the new Resource.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:resources'
            ]);
            $convertedString = $this->convertToEnglish($request->name);
            $slug = Str::slug($convertedString, '-');
            $role_id = DB::table('resources')->insertGetId([
                'name' => $request->name,
                'slug' => $slug,
            ]);
            return response()->json([
                'status' => 'Success',
                'message' => 'Resource is added successfully',
            ],200);
        } catch (ValidationException $exception) {
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
            $resource = Resources::where('id',$id)->first();
            $request->validate([
                'name' => ['required','string', Rule::unique('resources', 'name')->ignore($resource->id)]
            ]);

            if (Resources::where('id',$id)->exists()){
                $convertedString = $this->convertToEnglish($request->name);
                $slug = Str::slug($convertedString, '-');

                DB::table('resources')->where('id',$id)
                    ->update(['name' => $request->name, 'slug' => $slug]);

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Resource is updated successfully',
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
     * Method allow to assign the role for the respective resource
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function resourcesRoles(Request $request, $id):JsonResponse
    {
        try {
            if (Resources::where('id',$id)->exists()){
                $request->validate([
                    'roles_id' => 'required|array'
                ]);
                $resource = Resources::where('id',$id)->first();
                foreach ($request->roles_id as $role_id){
                    $resource->roles()->attach($role_id);
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Role(s) is assigned to resource successfully',
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
     * Method allow to delete the particular group.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (Resources::where('id',$id)->exists()){
                Resources::where('id',$id)->delete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Resource is deleted successfully',
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
}

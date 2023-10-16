<?php

namespace App\Http\Controllers\Sectors;

use App\Http\Controllers\Controller;
use App\Models\IndustriesSectors;
use App\Models\IndustriesSectorsGroups;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;

class SectorsController extends Controller
{
    /**
     * Method allow to display list of all groups or single group.
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $sectors_groups = IndustriesSectorsGroups::orderBy('name')->get();
            $query = array();
            foreach ($sectors_groups as $sectors_group){
                $result = [
                    'id' => $sectors_group->id,
                    'name' => $sectors_group->name,
                ];
                // Get details of all sectors
                if (!empty($sectors_group->sectorsGroups)){
                    $sectors_details = $this->getSectorsDetails($sectors_group->sectorsGroups);
                } else {
                    $sectors_details = array();
                }
                $query[] = array_merge($result, ['sectors' => $sectors_details]);
            }
            return response()->json([
                'data' => $query,
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
     * Method allow to give Industry Sectors data Array
     * @param $sectors_groups
     * @return array
     */
    public function getSectorsDetails($sectors_groups):array
    {
        $result_array = array();
        if (!empty($sectors_groups)){
            foreach ($sectors_groups as $sectors_group){
                $result_array[] = [
                    'id' => $sectors_group->id,
                    'name' => $sectors_group->name
                ];
            }
        }
        return $result_array;
    } // End Function

    /**
     * Method allow to store or create the new group for the Sectors.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function storeGroups(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:industries_sectors_groups'
            ]);
            $group = IndustriesSectorsGroups::create($request->all());

            return response()->json([
                'status' => 'Success',
                'message' => 'Group is added successfully',
            ],200);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to store or create the new Sector for the groups.
     * @param Request $request
     * @param $group_id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function storeSectors(Request $request, $group_id):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:industries_sectors'
            ]);
            $sector = DB::table('industries_sectors')->insert([
                'name' => $request->name,
                'industries_sectors_groups_id' => $group_id,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Sector is added successfully',
            ],200);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to update the name of the particular group.
     * @param Request $request
     * @param $group_id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateGroups(Request $request, $group_id):JsonResponse
    {
        try {
            $sector_group = IndustriesSectorsGroups::where('id',$group_id)->first();
            if (!empty($sector_group)) {
                $request->validate([
                    'name' => ['required','string', Rule::unique('industries_sectors_groups', 'name')->ignore($sector_group->id)]
                ]);
                $sector_group->name = $request->name;
                $sector_group->save();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The group name is updated successfully',
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
     * Method allow to update the name of the particular group.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateSectors(Request $request, $id):JsonResponse
    {
        try {
            if (IndustriesSectors::where('id',$id)->exists()){
                $sectors = IndustriesSectors::where('id',$id)->first();
                $request->validate([
                    'name' => ['required','string', Rule::unique('industries_sectors', 'name')->ignore($sectors->id)]
                ]);
                $sectors->name = $request->name;
                $sectors->save();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The sector name is updated successfully',
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
     * Method allow to delete the particular groups with respective sectors.
     * @param $group_id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroyGroups($group_id):JsonResponse
    {
        try {
            if (IndustriesSectorsGroups::where('id', $group_id)->exists()) {
                IndustriesSectorsGroups::where('id',$group_id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Group is deleted successfully',
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
     * Method allow to delete the particular Sectors.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroySectors($id):JsonResponse
    {
        try {
            if (IndustriesSectors::where('id', $id)->exists()) {
                IndustriesSectors::where('id',$id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Sector is deleted successfully',
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

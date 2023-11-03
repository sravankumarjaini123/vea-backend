<?php

namespace App\Http\Controllers\Fundings;

use App\Http\Controllers\Controller;
use App\Models\FundingsRequirements;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;

class FundingRequirementController extends Controller
{
    /**
     * Method allow to display list of all Funding Requirements
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $states = DB::table('fundings_requirements')
                ->orderBy('display_order', 'ASC')
                ->get();
            return response()->json([
                'data' => $states,
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
     * Method allow to store or create the new Funding Requirement.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:fundings_requirements'
            ]);

            $order_number = FundingsRequirements::max('display_order');

            $group = DB::table('fundings_requirements')->insert([
                'name' => $request->name,
                'display_order' =>  $order_number + 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Funding Requirement is added successfully',
            ],200);

        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to show the particular Funding Requirement.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            if (FundingsRequirements::where('id',$id)->exists()){
                $state = FundingsRequirements::where('id',$id)->first();
                return response()->json([
                    'data' => $state,
                    'message' => 'Success',
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
     * Method allow to update the name of the particular Funding Requirement.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            $requirement = FundingsRequirements::where('id',$id)->first();
            $request->validate([
                'name' => ['required','string', Rule::unique('fundings_requirements', 'name')->ignore($requirement->id)]
            ]);

            if (FundingsRequirements::where('id',$id)->exists()){
                FundingsRequirements::where('id',$id)
                    ->update([
                        'name' => $request->name,
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Funding Requirement is updated successfully',
                ],200);

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
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to delete the particular Funding Requirement.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (FundingsRequirements::where('id',$id)->exists()){
                FundingsRequirements::where('id',$id)->delete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Funding Requirement is deleted successfully',
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
     * Method allow to soft delete the set of Funding Requirements.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->requirements_id)){
                foreach ($request->requirements_id as $group_id)
                {
                    $group = FundingsRequirements::findOrFail($group_id);
                    $group->delete();
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Requirements are deleted successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one Group to delete'
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
     * Method allow to change the order of display of Funding Requirements
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function sorting(Request $request):JsonResponse
    {
        try {
            foreach ($request->requirements_sorting as $sorting)
            {
                $index = $sorting['id'];
                $new_position = $sorting['newposition'];
                $display_order_number = [
                    'display_order' => $new_position
                ];
                FundingsRequirements::where('id',$index)->update($display_order_number);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'The Funding Requirements are sorted successfully',
            ],200);

        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function
}

<?php

namespace App\Http\Controllers\Fundings;

use App\Http\Controllers\Controller;
use App\Models\FundingsSubjects;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;

class FundingSubjectController extends Controller
{
    /**
     * Method allow to display list of all Funding Subjects
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $subjects = DB::table('fundings_subjects')
                ->orderBy('display_order', 'ASC')
                ->get();
            return response()->json([
                'data' => $subjects,
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
     * Method allow to store or create the new Funding Subject.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:fundings_subjects'
            ]);

            $order_number = FundingsSubjects::max('display_order');

            $subject = DB::table('fundings_subjects')->insert([
                'name' => $request->name,
                'display_order' =>  $order_number + 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Funding Subject is added successfully',
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
     * Method allow to show the particular Funding Subject.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            if (FundingsSubjects::where('id',$id)->exists()){
                $subject = FundingsSubjects::where('id',$id)->first();
                return response()->json([
                    'data' => $subject,
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
     * Method allow to update the name of the particular Funding Subject.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            $subject = FundingsSubjects::where('id',$id)->first();
            $request->validate([
                'name' => ['required','string', Rule::unique('fundings_subjects', 'name')->ignore($subject->id)]
            ]);

            if (FundingsSubjects::where('id',$id)->exists()){
                FundingsSubjects::where('id',$id)
                    ->update([
                        'name' => $request->name,
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Funding Subject is updated successfully',
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
     * Method allow to delete the particular Funding Subject.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (FundingsSubjects::where('id',$id)->exists()){
                FundingsSubjects::where('id',$id)->delete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Funding Subject is deleted successfully',
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
     * Method allow to soft delete the set of Funding States.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->subjects_id)){
                foreach ($request->subjects_id as $subject_id)
                {
                    $group = FundingsSubjects::findOrFail($subject_id);
                    $group->delete();
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Funding Subjects are deleted successfully',
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
     * Method allow to change the order of display of Funding States
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function sorting(Request $request):JsonResponse
    {
        try {
            foreach ($request->subjects_sorting as $sorting)
            {
                $index = $sorting['id'];
                $new_position = $sorting['newposition'];
                $display_order_number = [
                    'display_order' => $new_position
                ];
                FundingsSubjects::where('id',$index)->update($display_order_number);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'The Funding Subjects are sorted successfully',
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

<?php

namespace App\Http\Controllers\Labels;

use App\Http\Controllers\Controller;
use App\Models\Labels;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;

class LabelController extends Controller
{
    /**
    * Method allow to display list of all Labels.
    * @return JsonResponse
    * @throws Exception
    */
    public function index():JsonResponse
    {
        try {
            $labels = Labels::orderBy('display_order')->get();
            return response()->json([
                'data' => $labels,
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
     * Method allow to store or create the new Label.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:labels'
            ]);

            $ordernumber = Labels::max('display_order');

            $label = DB::table('labels')->insert([
                'name' => $request->name,
                'display_order' =>  $ordernumber + 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Label is added successfully',
            ],200);

        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to show the particular Label.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            if (Labels::where('id',$id)->exists()){
                $labels = Labels::where('id',$id)->first();
                return response()->json([
                    'data' => $labels,
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
     * Method allow to update the name of the particular Label.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            $label = Labels::where('id',$id)->first();
            $request->validate([
                'name' => ['required','string', Rule::unique('labels', 'name')->ignore($label->id)]
            ]);

            if (Labels::where('id',$id)->exists()){

                $label->update($request->all());

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Label name is updated successfully',
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
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to delete the particular Label.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (Labels::where('id',$id)->exists()){
                Labels::where('id',$id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Label is deleted successfully',
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
     * Method allow to delete the set of labels.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->labels_id)) {
                foreach ($request->labels_id as $label_id) {
                    $labels = Labels::findOrFail($label_id);
                    $labels->delete();
                }

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Labels are deleted successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one Category to delete'
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
     * Method allow to change the order of display
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function sorting(Request $request):JsonResponse
    {
        try {
            foreach ($request->labels_sorting as $sorting)
            {
                $index = $sorting['id'];
                $newposition = $sorting['newposition'];
                $displayordernumber = [
                    'display_order' => $newposition
                ];
                Labels::where('id',$index)->update($displayordernumber);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'The Labels are sorted successfully',
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

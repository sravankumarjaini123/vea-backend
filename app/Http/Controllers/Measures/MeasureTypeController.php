<?php

namespace App\Http\Controllers\Measures;

use App\Http\Controllers\Controller;
use App\Models\MeasuresTypes;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;

class MeasureTypeController extends Controller
{
    /**
     * Method allow to display list of all Measures Types.
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $measures_types = DB::table('measures_types')
                ->orderBy('display_order')
                ->get();
            return response()->json([
                'data' => $measures_types,
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
     * Method allow to store or create the new Measures Types.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:measures_types'
            ]);

            $ordernumber = MeasuresTypes::max('display_order');

            $measures_types = DB::table('measures_types')->insert([
                'name' => $request->name,
                'display_order' =>  $ordernumber + 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Measures Type is added successfully',
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
     * Method allow to show the particular Measures Type.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            if (MeasuresTypes::where('id',$id)->exists()){
                $measures_types = MeasuresTypes::where('id',$id)->first();
                return response()->json([
                    'data' => $measures_types,
                    'message' => 'Success',
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
     * Method allow to update the name of the particular Measures Processor.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            $measures_types = MeasuresTypes::where('id',$id)->first();
            $request->validate([
                'name' => ['required','string', Rule::unique('measures_types', 'name')->ignore($measures_types->id)]
            ]);

            if (MeasuresTypes::where('id',$id)->exists()){

                MeasuresTypes::where('id',$id)
                    ->update([
                        'name' => $request->name,
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Measures Type name is updated successfully',
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
     * Method allow to delete the particular Measures Type.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (MeasuresTypes::where('id',$id)->exists()){
                MeasuresTypes::where('id',$id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Measures Type is deleted successfully',
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
     * Method allow to change the order of display
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function sorting(Request $request):JsonResponse
    {
        try {
            foreach ($request->types_sorting as $sorting)
            {
                $index = $sorting['id'];
                $newposition = $sorting['newposition'];
                $displayordernumber = [
                    'display_order' => $newposition
                ];
                MeasuresTypes::where('id',$index)->update($displayordernumber);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'The Measures Types are sorted successfully',
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
     * Method allow to soft delete the set of Measures Types.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->types_id)) {
                foreach ($request->types_id as $type_id) {
                    $category = MeasuresTypes::findOrFail($type_id);
                    $category->delete();
                }

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Measures Types are deleted successfully',
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
}

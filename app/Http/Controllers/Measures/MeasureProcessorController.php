<?php

namespace App\Http\Controllers\Measures;

use App\Http\Controllers\Controller;
use App\Models\MeasuresProcessors;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;

class MeasureProcessorController extends Controller
{
    /**
     * Method allow to display list of all Measures Processors.
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $measures_processors = DB::table('measures_processors')
                ->orderBy('display_order')
                ->get();
            return response()->json([
                'data' => $measures_processors,
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
     * Method allow to store or create the new Measures Processor.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:measures_processors'
            ]);

            $ordernumber = MeasuresProcessors::max('display_order');

            $measures_processors = DB::table('measures_processors')->insert([
                'name' => $request->name,
                'display_order' =>  $ordernumber + 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Measures Processor is added successfully',
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
     * Method allow to show the particular Measures Processor.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            if (MeasuresProcessors::where('id',$id)->exists()){
                $measures_processors = MeasuresProcessors::where('id',$id)->first();
                return response()->json([
                    'data' => $measures_processors,
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
            $measures_processor = MeasuresProcessors::where('id',$id)->first();
            $request->validate([
                'name' => ['required','string', Rule::unique('measures_processors', 'name')->ignore($measures_processor->id)]
            ]);

            if (MeasuresProcessors::where('id',$id)->exists()){

                MeasuresProcessors::where('id',$id)
                    ->update([
                        'name' => $request->name,
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Measures Processor name is updated successfully',
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
     * Method allow to delete the particular Measures Processor.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (MeasuresProcessors::where('id',$id)->exists()){
                MeasuresProcessors::where('id',$id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Measures Processor is deleted successfully',
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
            foreach ($request->processors_sorting as $sorting)
            {
                $index = $sorting['id'];
                $newposition = $sorting['newposition'];
                $displayordernumber = [
                    'display_order' => $newposition
                ];
                MeasuresProcessors::where('id',$index)->update($displayordernumber);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'The Measures Processors are sorted successfully',
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
     * Method allow to soft delete the set of Measures Processor.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->processors_id)) {
                foreach ($request->processors_id as $processor_id) {
                    $category = MeasuresProcessors::findOrFail($processor_id);
                    $category->delete();
                }

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Measures Processors are deleted successfully',
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

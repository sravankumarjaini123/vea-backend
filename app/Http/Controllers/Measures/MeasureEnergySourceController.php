<?php

namespace App\Http\Controllers\Measures;

use App\Http\Controllers\Controller;
use App\Models\MeasuresEnergySources;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;

class MeasureEnergySourceController extends Controller
{
    /**
     * Method allow to display list of all Measures Energy Sources.
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $measures_energy_sources = DB::table('measures_energy_sources')
                ->orderBy('name')
                ->get();
            return response()->json([
                'data' => $measures_energy_sources,
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
     * Method allow to store or create the new Measures Energy Sources.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:measures_energy_sources'
            ]);

            $measures_types = DB::table('measures_energy_sources')->insert([
                'name' => $request->name,
                'co2_emission_factor' => $request->co2_emission_factor ?? null,
                'energy_price' => $request->energy_price ?? null,
                'commodity_price' => $request->commodity_price ?? null,
                'charges_and_levies' => $request->charges ?? null,
                'comments' => $request->comments ?? null,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Measures Energy Source is added successfully',
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
     * Method allow to show the particular Measures Energy Source.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            if (MeasuresEnergySources::where('id',$id)->exists()){
                $measures_energy_sources = MeasuresEnergySources::where('id',$id)->first();
                return response()->json([
                    'data' => $measures_energy_sources,
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
     * Method allow to update the name of the particular Measures Energy Sources.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            $measures_energy_sources = MeasuresEnergySources::where('id',$id)->first();
            $request->validate([
                'name' => ['required','string', Rule::unique('measures_energy_sources', 'name')->ignore($measures_energy_sources->id)]
            ]);

            if (MeasuresEnergySources::where('id',$id)->exists()){

                MeasuresEnergySources::where('id',$id)
                    ->update([
                        'name' => $request->name,
                        'co2_emission_factor' => $request->co2_emission_factor ?? null,
                        'energy_price' => $request->energy_price ?? null,
                        'commodity_price' => $request->commodity_price ?? null,
                        'charges_and_levies' => $request->charges ?? null,
                        'comments' => $request->comments ?? null,
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Measures Energy Source is updated successfully',
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
     * Method allow to delete the particular Measures Energy Source.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (MeasuresEnergySources::where('id',$id)->exists()){
                MeasuresEnergySources::where('id',$id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Measures Energy Source is deleted successfully',
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
     * Method allow to soft delete the set of Measures Energy Sources.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->energy_sources_id)) {
                foreach ($request->energy_sources_id as $energy_source_id) {
                    $category = MeasuresEnergySources::findOrFail($energy_source_id);
                    $category->delete();
                }

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Measures Energy Sources are deleted successfully',
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

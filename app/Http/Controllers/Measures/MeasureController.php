<?php

namespace App\Http\Controllers\Measures;

use App\Http\Controllers\Controller;
use App\Models\Measures;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;

class MeasureController extends Controller
{
    /**
     * Method allow to display list of all Measures
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request):JsonResponse
    {
        try {
            $measures = Measures::all();
            $data = $this->getMeasureDetails($measures);
            return response()->json([
                'measure' => $data,
                'message' => 'Success',
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getLine(),
            ], 500);
        }
    } // End Function

    public function getMeasureDetails($measures):array
    {
        $result_array = array();
        if (!empty($measures)) {
            foreach ($measures as $measure) {
                if ($measure->measures_processors_id != null) {
                    $measure_processor_name = $measure->processor->name;
                }
                if ($measure->measures_categories_id != null) {
                    $measure_category_name = $measure->category->name;
                }
                if ($measure->measures_types_id != null) {
                    $measure_type_name = $measure->type->name;
                }
                if ($measure->industries_sectors_id != null) {
                    $industries_sectors_name = $measure->industrySector->name;
                    $industries_sectors_group_name = $measure->industrySector->industryGroup->name;
                }
                if ($measure->contacts_persons_id != null) {
                    if ($measure->contact->partners_id != null) {
                        $contact_person_partner_name = $measure->contact->company->name;
                    }
                    if ($measure->contact->profile_photo_id != null) {
                        $contact_person_profile_photo = $measure->contact->profilePhoto->file_path;
                    }
                    $contact_person_details = [
                        'firstname' => $measure->contact->firstname,
                        'lastname' => $measure->contact->lastname,
                        'email' => $measure->contact->email,
                        'profile_photo' => $contact_person_profile_photo ?? null,
                        'company_name' =>  $contact_person_partner_name ?? null,
                    ];
                }
                // Energy Sources
                $energy_sources = $this->getEnergySourcesDetails($measure->energySources);

                $result_array[] = [
                    'id' => $measure->id,
                    'status' => $measure->status,
                    'name' => $measure->name,
                    'description' => $measure->description,
                    'measure_processor_id' => $measure->measures_processors_id,
                    'measure_processor_name' => $measure_processor_name ?? null,
                    'measure_type_id' => $measure->measures_types_id,
                    'measure_type_name' => $measure_type_name ?? null,
                    'measure_category_id' => $measure->measures_categories_id,
                    'measures_category_name' => $measure_category_name ?? null,
                    'implementation_time' => $measure->implementation_time,
                    'operating_life' => (int)$measure->operating_life,
                    'investment_amount' => (int)$measure->investment_amount,
                    'investment_year' => (int)$measure->investment_year,
                    'investment_comments' => $measure->investment_comments,
                    'obstacles' => $measure->obstacles,
                    'interactions' => $measure->interactions,
                    'additional_benefits' => $measure->additional_benefits,
                    'funding' => $measure->funding,
                    'industry_sector_id' => $measure->industries_sectors_id,
                    'industry_sector_name' => $industries_sectors_name ?? null,
                    'industry_sector_group_name' => $industries_sectors_group_name ?? null,
                    'company_size' => $measure->company_size,
                    'contact_person_details' => $contact_person_details ?? (object)array(),
                    'energy_sources' => $energy_sources,
                    'sources' => json_decode($measure->source) ?? null,
                ];
            }
        }
        return $result_array;
    } // End Function

    /**
     * Method allow to store new Fundings.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required',
                'measure_processor_id' => 'required'
            ]);

            $measure_id = Measures::insertGetId([
                'name' => $request->name,
                'status' => 'open',
                'measures_processors_id' => $request->measure_processor_id ?? null,
                'measures_types_id' => $request->type_id ?? null,
                'measures_categories_id' => $request->measure_category_id ?? null,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'measureId' => $measure_id,
                'status' => 'Success',
                'message' => 'Funding is created successfully',
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
     * Method allow to retrieve the single Measure
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function show($id):JsonResponse
    {
        try {
            if (Measures::where('id', $id)->exists()) {
                $measures = Measures::where('id', $id)->get();
                $measures_array = $this->getMeasureDetails($measures);
                foreach ($measures_array as $array) {
                    $result_array = $array;
                }
                return response()->json([
                    'measure' => $result_array ?? array(),
                    'message' => 'Success',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
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

    /**
     * Method allow to update the General details of the Measure
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateGeneral(Request $request, $id):JsonResponse
    {
        try {
            if (Measures::where('id', $id)->exists()) {
                $measure = Measures::where('id', $id)->first();
                $request->validate([
                    'name' => 'required',
                ]);
                $measure->name = $request->name;
                $measure->description = $request->description ?? null;
                $measure->save();
                $result_array = [
                    'id' => $measure->id,
                    'name' => $measure->name,
                    'description' => $measure->description,
                ];
                return response()->json([
                    'measure' => $result_array,
                    'message' => 'Success',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
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

    /**
     * Method allow to update the Status the Measure.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateStatus(Request $request, $id):JsonResponse
    {
        try {
            if (Measures::where('id', $id)->exists()) {
                $request->validate([
                    'status' => 'required|in:open,inProgress,complete'
                ]);
                $measure = Measures::where('id', $id)->first();
                $measure->status = $request->status;
                $measure->save();
                $result_array = [
                    'id' => $measure->id,
                    'status' => $measure->status,
                ];
                return response()->json([
                    'measure' => $result_array,
                    'message' => 'Success',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Method allow to update the different Master Data of the Measure at once.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateMasterData(Request $request, $id):JsonResponse
    {
        try {
            if (Measures::where('id', $id)->exists()) {
                $request->validate([
                    'type' => 'required|in:type,category,processor,contact,industry_sector',
                ]);
                $measure = Measures::where('id', $id)->first();
                switch ($request->type) {
                    case('type'):
                        $measure->measures_types_id = $request->datas_id ?? null;
                        $measure->save();
                        break;
                    case('category'):
                        $measure->measures_categories_id = $request->datas_id ?? null;
                        $measure->save();
                        break;
                    case('processor'):
                        $measure->measures_processors_id = $request->datas_id ?? null;
                        $measure->save();
                        break;
                    case('contact'):
                        $measure->contacts_persons_id = $request->datas_id ?? null;
                        $measure->save();
                        break;
                    case('industry_sector'):
                        $measure->industries_sectors_id = $request->datas_id ?? null;
                        $measure->save();
                        break;
                }
                $updated_measure = Measures::where('id', $id)->get();
                $measures_array = $this->getMeasureDetails($updated_measure);
                foreach ($measures_array as $array) {
                    $result_array = $array;
                }
                return response()->json([
                    'measure' => $result_array ?? array(),
                    'message' => 'Success',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to update the Investment details of the Measure.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateInvestment(Request $request, $id):JsonResponse
    {
        try {
            if (Measures::where('id', $id)->exists()) {
                $measure = Measures::where('id', $id)->first();
                $measure->operating_life = $request->operating_life ?? 0;
                $measure->investment_amount = $request->investment_amount ?? 0;
                $measure->investment_year = $request->investment_year ?? null;
                $measure->investment_comments = $request->investment_comments ?? null;
                $measure->save();
                $result_array = [
                    'id' => $measure->id,
                    'operating_life' => (int)$measure->operating_life,
                    'investment_amount' => (int)$measure->investment_amount,
                    'investment_year' => (int)$measure->investment_year,
                    'investment_comments' => $measure->investment_comments,
                ];
                return response()->json([
                    'measure' => $result_array,
                    'message' => 'Success',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to update the Additional Details of the Measures
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateAdditional(Request $request, $id):JsonResponse
    {
        try {
            if (Measures::where('id', $id)->exists()) {
                $request->validate([
                    'type' => 'required|in:obstacles,interactions,benefits,funding,implementation_time,company_size,source',
                ]);
                if ($request->type === 'source') {
                    $request->validate([
                        'data' => 'nullable|array'
                    ]);
                }
                if ($request->type === 'implementation_time') {
                    $request->validate([
                        'data' => 'nullable|in:immediate,medium,slow'
                    ]);
                }
                if ($request->type === 'company_size') {
                    $request->validate([
                        'data' => 'nullable|in:KMU,large,any'
                    ]);
                }
                $measure = Measures::where('id', $id)->first();
                switch ($request->type) {
                    case('obstacles'):
                        $measure->obstacles = $request->data ?? null;
                        $measure->save();
                        break;
                    case('interactions'):
                        $measure->interactions = $request->data ?? null;
                        $measure->save();
                        break;
                    case('benefits'):
                        $measure->additional_benefits = $request->data ?? null;
                        $measure->save();
                        break;
                    case('funding'):
                        $measure->funding = $request->data ?? null;
                        $measure->save();
                        break;
                    case('industry_sector'):
                        $measure->industries_sectors_id = $request->data ?? null;
                        $measure->save();
                        break;
                    case('implementation_time'):
                        $measure->implementation_time = $request->data ?? null;
                        $measure->save();
                        break;
                    case('company_size'):
                        $measure->company_size = $request->data ?? null;
                        $measure->save();
                        break;
                    case ('source'):
                        $measure->source = json_encode($request->data) ?? null;
                        $measure->save();
                        break;
                }
                $updated_measure = Measures::where('id', $id)->get();
                $measures_array = $this->getMeasureDetails($updated_measure);
                foreach ($measures_array as $array) {
                    $result_array = $array;
                }
                return response()->json([
                    'measure' => $result_array ?? array(),
                    'message' => 'Success',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to attach the Energy Source for the Measure with respective Saving.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function attachEnergySources(Request $request, $id):JsonResponse
    {
        try {
            if (Measures::where('id',$id)->exists()){
                $request->validate([
                    'energy_source_id' => 'required',
                    'energy_source_saving' => 'required'
                ]);
                $measure = Measures::where('id', $id)->first();
                if (!$measure->energySources()->where('measures_energy_sources_id', $request->energy_source_id)->exists()) {
                    $measure->energySources()->attach($request->energy_source_id,
                        ['measures_energy_savings' => $request->energy_source_saving]);
                }
                $energy_sources = $this->getEnergySourcesDetails($measure->energySources);
                return response()->json([
                    'measure' => $energy_sources,
                    'message' => 'Success',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to Detach the Energy Source for the Measure.
     * @param $id
     * @param $data_id
     * @return JsonResponse
     * @throws Exception
     */
    public function detachEnergySources($id, $data_id):JsonResponse
    {
        try {
            if (Measures::where('id',$id)->exists()){
                DB::table('measures_measures_energy_sources')->where('id', $data_id)->delete();
                $measure = Measures::where('id', $id)->first();
                $energy_sources = $this->getEnergySourcesDetails($measure->energySources);
                return response()->json([
                    'measure' => $energy_sources,
                    'message' => 'Success',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function getEnergySourcesDetails($sources):array
    {
        $result_array = array();
        if (!empty($sources)) {
            foreach ($sources as $source) {
                $result_array[] =[
                    'data_id' => $source->pivot->id,
                    'energy_saving_id' => $source->id,
                    'energy_saving_name' => $source->name,
                    'energy_source_saving' => $source->pivot->measures_energy_savings,
                ];
            }
        }
        return $result_array;
    } // End Function


    /**
     * Method allow to delete the particular Measure.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (Measures::where('id',$id)->exists()){
                Measures::where('id',$id)->delete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Measure is deleted successfully',
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
     * Method allow to soft delete the set of Measures.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->measures_id)){
                foreach ($request->measures_id as $measure_id)
                {
                    $funding = Measures::findOrFail($measure_id);
                    $funding->delete();
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Measures are deleted successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one Measure to delete'
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
     * Method allow to Retrieve list of deleted Measures.
     * @return JsonResponse
     * @throws Exception
     */
    public function retrieve():JsonResponse
    {
        try {
            $measures = Measures::onlyTrashed()->get();
            $measure_details = $this->getMeasureDetails($measures);
            return response()->json([
                'measures' => $measure_details,
                'message' => 'Success',
            ], 200);

        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to retrieve all the parameters for Measures Calculation
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function indexParameters():JsonResponse
    {
        try {
            $parameters = DB::table('measures_parameters')->get();
            $internals = array();
            $price_indices = array();
            foreach ($parameters as $parameter) {
                if ($parameter->type === 'internal') {
                    $internals[] = [
                        'id' => $parameter->id,
                        'key' => $parameter->key,
                        'value' => $parameter->value,
                    ];
                }
                if ($parameter->type === 'price_index') {
                    $price_indices[] = [
                        'id' => $parameter->id,
                        'key' => $parameter->key,
                        'key_extra' => $parameter->key_extra,
                        'value' => $parameter->value,
                    ];
                }
            }
            return response()->json([
                'measuresInternalParameters' => $internals,
                'measuresPriceIndicesParameters' => $price_indices,
                'message' => 'Success',
            ], 200);
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to Restore the particular Measure.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function restore($id):JsonResponse
    {
        try {
            if (Measures::where('id',$id)->onlyTrashed()->exists()){
                $measures = Measures::where('id',$id)->onlyTrashed()->restore();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Funding is restored successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
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

    /**
     * Method allow to Restore group of Measures.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massRestore(Request $request):JsonResponse
    {
        try {
            if (!empty($request->measures_id)){
                foreach ($request->measures_id as $measure_id)
                {
                    $measure = Measures::where('id',$measure_id)->onlyTrashed()->first();
                    if (!empty($measure)){
                        $measure->restore();
                    }
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Measures are restored successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one Measure to delete'
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
     * Method allow to Delete the Measures permanently
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function forceDelete($id):JsonResponse
    {
        try {
            if (Measures::where('id',$id)->onlyTrashed()->exists()){
                Measures::where('id',$id)->forceDelete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Measure is successfully deleted permanently!',
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
     * Method allow to Delete multiple Measures permanently
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massForceDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->measures_id)){
                foreach ($request->measures_id as $measure_id)
                {
                    $measure = Measures::where('id',$measure_id)->onlyTrashed()->first();
                    if (!empty($measure)){
                        $measure->forceDelete();
                    }
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Measures are permanently deleted successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one Measure to delete'
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
     * Method allow to calculate all the measured values depending on the various parameters
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function measuresCalculate($id):JsonResponse
    {
        try {
            dd('Hi');
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // Emd Function
}

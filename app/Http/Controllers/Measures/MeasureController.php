<?php

namespace App\Http\Controllers\Measures;

use App\Http\Controllers\Controller;
use App\Models\Measures;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;

class MeasureController extends Controller
{
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
}

<?php

namespace App\Http\Controllers\Fundings;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Fundings;
use App\Models\FundingsStates;
use App\Models\Groups;
use App\Models\Posts;
use App\Models\Tags;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Nette\Schema\ValidationException;
use Illuminate\Support\Facades\DB;

class FundingController extends Controller
{
    /**
     * Method allow to display list of all Fundings
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'is_active' => 'in:0,1',
            ]);
            $fundings = Fundings::where('deleted_at', '=', null);
            if ($request->is_active != null) {
                $fundings = $fundings->where('is_active', $request->is_active);
            }
            if ($request->limit == null){
                $limit = 10;
            } else {
                $limit = (int)$request->limit;
            }

            // filter for Multi selection data
            $array = array();
            $funding_condition_states = array();
            if (!empty($request->states_ids)) {
                $funding_condition_states = $this->fundingsMasterData('fundings_states',$request->states_ids, 'or');
                $array[] = $funding_condition_states;
            }
            $funding_condition_subjects = array();
            if (!empty($request->subjects_ids)) {
                $funding_condition_subjects = $this->fundingsMasterData('fundings_subjects',$request->subjects_ids, 'or');
                $array[] = $funding_condition_subjects;
            }
            $funding_condition_eligibilities = array();
            if (!empty($request->eligibilities_ids)) {
                $funding_condition_eligibilities = $this->fundingsMasterData('fundings_eligibilities',$request->eligibilities_ids, 'or');
                $array[] = $funding_condition_eligibilities;
            }
            if ($request->states_ids != null && $request->subjects_ids != null && $request->eligibilities_ids != null) {
                $funding_condition_final = array_intersect($funding_condition_states, $funding_condition_subjects, $funding_condition_eligibilities);
            } else {
                $count = 0;
                if (count($array) >= 2) {
                    foreach ($array as $test_array) {
                        if (!empty($test_array)) {
                            $count++;
                        }
                    }
                }
                if ($count != count($array)) {
                    $funding_condition_final = call_user_func_array('array_intersect', $array);
                } else {
                    $result = array_filter($array);
                    $funding_condition_final = array_shift($result);
                    foreach ($result as $filter) {
                        $funding_condition_final = array_intersect($funding_condition_final, $filter);
                    }
                }
            }
            if($funding_condition_final != null){
                $fundings = $fundings->whereIn('id', $funding_condition_final);
            }
            if($request->search_keyword != null) {
                $fundings = $fundings->where('programme', 'like', '%' . $request->search_keyword . '%');
            }
            if ($request->fundings_requirements_id != null){
                $fundings = $fundings->whereIn('fundings_requirements_id', json_decode($request->fundings_requirements_id));
            }
            if ($request->fundings_types_id != null){
                $fundings = $fundings->whereIn('fundings_types_id', json_decode($request->fundings_types_id));
            }
            if ($request->fundings_bodies_id != null){
                $fundings = $fundings->whereIn('fundings_bodies_id', json_decode($request->fundings_bodies_id));
            }
            $total_count = count($fundings->get());
            $funding_details = $this->getFundingDetails($fundings->paginate($limit));
            $pagination_final = $this->getPaginationDetails($fundings, $limit, $total_count);
            return response()->json([
                'filterData' => $funding_details,
                'pagination' => $pagination_final,
                'status' => 'Success',
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ], 500);
        }
    } // End Function

    public function getFundingDetails($fundings):array
    {
        $result_array = array();
        if (!empty($fundings)) {
            foreach ($fundings as $funding) {
                $funding_states = $this->getMasterDataForFunding($funding->states);
                $funding_subjects = $this->getMasterDataForFunding($funding->subjects);
                $funding_eligibilities = $this->getMasterDataForFunding($funding->eligibilities);
                if ($funding->fundings_requirements_id != null ) {
                    $funding_requirement_name = $funding->requirement->name;
                } else {
                    $funding_requirement_name = null;
                }
                if ($funding->fundings_types_id != null ) {
                    $funding_type_name = $funding->type->name;
                } else {
                    $funding_type_name = null;
                }
                if ($funding->fundings_bodies_id != null ) {
                    $funding_body_name = $funding->body->name;
                } else {
                    $funding_body_name = null;
                }
                $result_array[] = [
                    'id' => $funding->id,
                    'states' => $funding_states,
                    'subjects' => $funding_subjects,
                    'eligibilities' => $funding_eligibilities,
                    'funding_requirement_id' => $funding->fundings_requirements_id,
                    'funding_requirement_name' => $funding_requirement_name,
                    'funding_type_id' => $funding->fundings_types_id,
                    'funding_type_name' => $funding_type_name,
                    'funding_body_id' => $funding->fundings_bodies_id,
                    'funding_body_name' => $funding_body_name,
                    'programme' => $funding->programme,
                    'details' => $funding->details,
                    'head' => $funding->head,
                    'deadline' => $funding->deadline,
                    'period' => $funding->period,
                    'sources' => json_decode($funding->source) ?? null,
                    'created_at' => $funding->created_at,
                    'updated_at' => $funding->updated_at,
                ];
            }
        }
        return $result_array;
    } // End Function

    /**
     * Method allow to get the details of respective master data
     * @param $details
     * @return array
     */
    public function getMasterDataForFunding($details):array
    {
        $funding_details = array();
        if (!empty($details)){
            foreach ($details as $detail){
                $funding_details[] = [
                    'id' => $detail->id,
                    'name' => $detail->name,
                ];
            }
        }
        return $funding_details;
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
                'programme' => 'required'
            ]);

            $funding_id = Fundings::insertGetId([
                'programme' => $request->programme,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
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
     * Method allow to retrieve the single Funding
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function show($id):JsonResponse
    {
        try {
            if (Fundings::where('id', $id)->exists()) {
                $fundings = Fundings::where('id', $id)->get();
                $fundings_array = $this->getFundingDetails($fundings);
                foreach ($fundings_array as $array) {
                    $result_array = $array;
                }
                return response()->json([
                    'funding' => $result_array ?? array(),
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
     * Method allow to update the different parameters of the Funding at once.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            if (Fundings::where('id', $id)->exists()) {
                $request->validate([
                    'type' => 'required|in:programme,details,head,deadline,period,source,status',
                ]);
                if ($request->type === 'programme') {
                    $request->validate([
                        'data' => 'required'
                    ]);
                }
                if ($request->type === 'period') {
                    $request->validate([
                        'data' => 'nullable|date_format:Y-m-d'
                    ]);
                }
                if ($request->type === 'source') {
                    $request->validate([
                        'data' => 'nullable|array'
                    ]);
                }
                if ($request->type === 'status') {
                    $request->validate([
                        'data' => 'required|bool'
                    ]);
                }
                $funding = Fundings::where('id', $id)->first();
                switch ($request->type) {
                    case ('programme'):
                        $funding->programme = $request->data;
                        $funding->save();
                        break;
                    case ('details'):
                        $funding->details = $request->data ?? null;
                        $funding->save();
                        break;
                    case ('head'):
                        $funding->head = $request->data ?? null;
                        $funding->save();
                        break;
                    case ('deadline'):
                        $funding->deadline = $request->data ?? null;
                        $funding->save();
                        break;
                    case ('period'):
                        $funding->period = $request->data ?? null;
                        $funding->save();
                        break;
                    case ('source'):
                        $funding->source = json_encode($request->data) ?? null;
                        $funding->save();
                        break;
                    case ('status'):
                        $funding->status = $request->data ?? 1;
                        $funding->save();
                        break;
                }

                return response()->json([
                    'status' => 'Success',
                    'message' => 'Funding is Updated successfully',
                ], 200);

            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
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
     * Method allow to update the different Master Data of the Funding at once.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateMasterData(Request $request, $id):JsonResponse
    {
        try {
            if (Fundings::where('id', $id)->exists()) {
                $request->validate([
                    'type' => 'required|in:state,subject,eligibility,requirement,type,body',
                ]);
                if ($request->type === 'state' || $request->type === 'subject' || $request->type === 'eligibility') {
                    $request->validate([
                        'datas_id' => 'nullable|array',
                    ]);
                } else {
                    $request->validate([
                        'datas_id' => 'nullable|integer',
                    ]);
                }
                $funding = Fundings::where('id', $id)->first();
                switch ($request->type) {
                    case ('state'):
                        $funding_states = $funding->states;
                        if (!empty($funding_states)) {
                            foreach ($funding_states as $funding_state) {
                                $funding->states()->detach($funding_state->id);
                            }
                        }
                        if (!empty($request->datas_id)) {
                            foreach ($request->datas_id as $data_id) {
                                $funding->states()->attach($data_id, ['created_at' => Carbon::now()->format('Y-m-d H:i:s')]);
                            }
                        }
                        break;
                    case ('subject'):
                        $funding_subjects = $funding->subjects;
                        if (!empty($funding_subjects)) {
                            foreach ($funding_subjects as $funding_subject) {
                                $funding->subjects()->detach($funding_subject->id);
                            }
                        }
                        if (!empty($request->datas_id)) {
                            foreach ($request->datas_id as $data_id) {
                                $funding->subjects()->attach($data_id, ['created_at' => Carbon::now()->format('Y-m-d H:i:s')]);
                            }
                        }
                        break;
                    case ('eligibility'):
                        $funding_eligibilities = $funding->eligibilities;
                        if (!empty($funding_eligibilities)) {
                            foreach ($funding_eligibilities as $funding_eligibility) {
                                $funding->eligibilities()->detach($funding_eligibility->id);
                            }
                        }
                        if (!empty($request->datas_id)) {
                            foreach ($request->datas_id as $data_id) {
                                $funding->eligibilities()->attach($data_id, ['created_at' => Carbon::now()->format('Y-m-d H:i:s')]);
                            }
                        }
                        break;
                    case('requirement'):
                        $funding->fundings_requirements_id = $request->datas_id ?? null;
                        $funding->save();
                        break;
                    case('type'):
                        $funding->fundings_types_id = $request->datas_id ?? null;
                        $funding->save();
                        break;
                    case('body'):
                        $funding->fundings_bodies_id = $request->datas_id ?? null;
                        $funding->save();
                        break;

                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Funding MasterData is Updated successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
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
     * Method allow to delete the particular Funding.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (Fundings::where('id',$id)->exists()){
                Fundings::where('id',$id)->delete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Funding is deleted successfully',
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
     * Method allow to soft delete the set of groups.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->fundings_id)){
                foreach ($request->fundings_id as $funding_id)
                {
                    $funding = Fundings::findOrFail($funding_id);
                    $funding->delete();
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Fundings are deleted successfully',
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
     * Method allow to Retrieve list of deleted Fundings.
     * @return JsonResponse
     * @throws Exception
     */
    public function retrieve():JsonResponse
    {
        try {
            $fundings = Fundings::onlyTrashed()->get();
            $funding_details = $this->getFundingDetails($fundings);
            return response()->json([
                'fundings' => $funding_details,
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
     * Method allow to Restore the particular Funding.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function restore($id):JsonResponse
    {
        try {
            if (Fundings::where('id',$id)->onlyTrashed()->exists()){
                $fundings = Fundings::where('id',$id)->onlyTrashed()->restore();
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
     * Method allow to Restore group of Fundings.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massRestore(Request $request):JsonResponse
    {
        try {
            if (!empty($request->fundings_id)){
                foreach ($request->fundings_id as $funding_id)
                {
                    $funding = Fundings::where('id',$funding_id)->onlyTrashed()->first();
                    if (!empty($funding)){
                        $funding->restore();
                    }
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Fundings are restored successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one legalText to delete'
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
     * Method allow to Delete the Fundings permanently
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function forceDelete($id):JsonResponse
    {
        try {
            if (Fundings::where('id',$id)->onlyTrashed()->exists()){
                Fundings::where('id',$id)->forceDelete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Funding is successfully deleted permanently!',
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
     * Method allow to Delete multiple posts permanently
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massForceDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->fundings_id)){
                foreach ($request->fundings_id as $funding_id)
                {
                    $funding = Fundings::where('id',$funding_id)->onlyTrashed()->first();
                    if (!empty($funding)){
                        $funding->forceDelete();
                    }
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Fundings are permanently deleted successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one legalText to delete'
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
     * Method allow to retrieve all the fundings with filter conditions
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */



}

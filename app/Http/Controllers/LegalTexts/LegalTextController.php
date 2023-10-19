<?php

namespace App\Http\Controllers\LegalTexts;

use App\Http\Controllers\Controller;
use App\Models\LegalTexts;
use App\Models\LegalTextsSettings;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;

class LegalTextController extends Controller
{
    /**
     * Method allow to display list of all legalTexts.
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $legal_texts = DB::table('legal_texts')
                ->select('version_id')
                ->groupBy('version_id')
                ->get();
            $legal_text_details = array();
            if (!empty($legal_texts)) {
                foreach ($legal_texts as $legal_text) {
                    $details = LegalTexts::where('version_id', $legal_text->version_id)->orderBy('id', 'desc')->first();
                    if ($details) {
                        $published = DB::table('legal_texts')
                            ->where('version_id', $legal_text->version_id)
                            ->where('is_published', 1)->first();
                        $published_at = DB::table('legal_texts')
                            ->where('version_id', $legal_text->version_id)->max('published_at');
                        $created_at = DB::table('legal_texts')
                            ->where('version_id', $legal_text->version_id)->min('created_at');
                        if ($published) {
                            $is_published = 1;
                        } else {
                            $is_published = 0;
                        }
                        $legal_text_details[] = [
                            'id' => $details->id,
                            'version_id' => $details->version_id,
                            'name' => $details->name,
                            'title' => $details->title,
                            'description' => $details->description,
                            'element' => $details->element,
                            'is_published' => $is_published,
                            'published_at' => $published_at,
                            'created_at' => $created_at,
                        ];
                    }
                }
            }
            return response()->json([
                'legalTextDetails' => $legal_text_details,
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
     * Method allow to store or create the new legalText.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'title' => 'required|string',
            ]);
            $version_number = LegalTexts::withTrashed()->max('version_id');
            if ($request->save_type == 'draft'){
                $legal_text_id = DB::table('legal_texts')->insertGetId([
                    'version_id' => $version_number+1,
                    'name' => $request->name,
                    'title' => $request->title,
                    'description' => $request->description ?? null,
                    'element' => $request->element ?? null,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
                $legal_text_draft_details = DB::table('legal_texts')->where('id',$legal_text_id)->first();
                return response()->json([
                    'draftDetails' => $legal_text_draft_details,
                    'publishDetails' => null,
                    'status' => 'Success',
                ], 200);
            } else if ($request->save_type == 'publish') {
                $legal_text_publish_id = DB::table('legal_texts')->insertGetId([
                    'version_id' => $version_number+1,
                    'name' => $request->name,
                    'title' => $request->title,
                    'description' => $request->description,
                    'element' => $request->element,
                    'is_published' => 1,
                    'is_active' => 1,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'published_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
                $legal_text_draft_id = DB::table('legal_texts')->insertGetId([
                    'version_id' => $version_number+1,
                    'name' => $request->name,
                    'title' => $request->title,
                    'description' => $request->description,
                    'element' => $request->element,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
                $legal_text_publish_details = DB::table('legal_texts')->where('id',$legal_text_publish_id)->first();
                $legal_text_details = $this->getLegalTextsByVersionId($legal_text_publish_details->version_id);
                if (!empty($legal_text_details)) {
                    $draft_details = array();
                    $publish_details = array();
                    for ($i = 0; $i < 1; $i++) {
                        $draft_details = $legal_text_details[$i];
                    }
                    for ($i = 1; $i < count($legal_text_details); $i++) {
                        $publish_details[] = $legal_text_details[$i];
                    }
                    return response()->json([
                        'draftDetails' => $draft_details,
                        'publishDetails' => $publish_details,
                        'status' => 'Success',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'No Content',
                        'message' => 'There is no relevant information for selected query'
                    ], 210);
                }
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please enter the correct save_type:accepted are draft or publish',
                ], 400);
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
     * Method allow to update the existing legalText.
     * @param Request $request
     * @param $version_id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $version_id):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'title' => 'required|string',
            ]);
            $legalTexts = DB::table('legal_texts')
                ->select('version_id',DB::raw('MAX(id) as id'))
                ->where('version_id',$version_id)
                ->groupBy('version_id')
                ->first();
            if ($request->save_type == 'draft'){
                $update_legal_texts = [
                    'name' => $request->name,
                    'title' => $request->title,
                    'description' => $request->description ?? null,
                    'element' => $request->element ?? null,
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ];
                $update = DB::table('legal_texts')->where('version_id',$legalTexts->version_id)
                    ->where('id',$legalTexts->id)
                    ->update($update_legal_texts);
                $legal_text_details = $this->getLegalTextsByVersionId($version_id);
                if (!empty($legal_text_details)) {
                    $draft_details = array();
                    $publish_details = array();
                    for ($i = 0; $i < 1; $i++) {
                        $draft_details = $legal_text_details[$i];
                    }
                    for ($i = 1; $i < count($legal_text_details); $i++) {
                        $publish_details[] = $legal_text_details[$i];
                    }
                    return response()->json([
                        'draftDetails' => $draft_details,
                        'publishDetails' => $publish_details,
                        'status' => 'Success',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'No Content',
                        'message' => 'There is no relevant information for selected query'
                    ], 210);
                }

            } else if ($request->save_type == 'publish'){

                $check_is_active = DB::table('legal_texts')->where('version_id',$version_id)->where('is_active',1)->take(1);
                if ($check_is_active){
                    $check_is_active->update(['is_active' => false]);
                }
                $update_publish = DB::table('legal_texts')->where('version_id',$legalTexts->version_id)
                    ->where('id',$legalTexts->id)
                    ->update([
                        'name' => $request->name,
                        'title' => $request->title,
                        'description' => $request->description,
                        'element' => $request->element,
                        'is_active' => 1,
                        'is_published' => 1,
                        'published_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                $create_replicate_id = DB::table('legal_texts')->insertGetId([
                    'version_id' => $version_id,
                    'name' => $request->name,
                    'title' => $request->title,
                    'description' => $request->description,
                    'element' => $request->element,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
                $legal_text_publish_details = $this->getLegalTextsByVersionId($version_id);
                if (!empty($legal_text_publish_details)) {
                    $draft_details = array();
                    $publish_details = array();
                    for ($i = 0; $i < 1; $i++) {
                        $draft_details = $legal_text_publish_details[$i];
                    }
                    for ($i = 1; $i < count($legal_text_publish_details); $i++) {
                        $publish_details[] = $legal_text_publish_details[$i];
                    }
                    return response()->json([
                        'draftDetails' => $draft_details,
                        'publishDetails' => $publish_details,
                        'status' => 'Success',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'No Content',
                        'message' => 'There is no relevant information for selected query'
                    ], 210);
                }
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please enter the correct save_type:accepted are draft or publish',
                ], 400);
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
     * Method allow to retrieve all the settings for the different scenarios in the system
     * @return JsonResponse
     * @throws ValidationException
     */
    public function getSettings():JsonResponse
    {
        try {
            $user_setting = LegalTextsSettings::where('assigned_for', 'users_registration')->get();
            $result_user_setting = $this->getLegalTextsSettingsDetails($user_setting);
            $event_setting = LegalTextsSettings::where('assigned_for', 'free_events_registration')->get();
            $result_event_setting = $this->getLegalTextsSettingsDetails($event_setting);
            $offer_setting = LegalTextsSettings::where('assigned_for', 'stripes_offers')->get();
            $result_offer_setting = $this->getLegalTextsSettingsDetails($offer_setting);
            $result = [
                'users_registrations_settings' => $result_user_setting,
                'free_events_registrations_settings' => $result_event_setting,
                'stripes_offers_settings' => $result_offer_setting,
            ];
            return response()->json([
                'legalTextsSettings' => $result,
                'status' => 'Success',
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
     * Method allow to add the Settings for the particular condition of the system
     * @param  Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function addSettings(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'assigned_for' => 'required|in:users_registration,free_events_registration,stripes_offers',
                'legal_text_id' => 'required',
                'required' => 'required|boolean'
            ]);
            if (LegalTexts::where('version_id', $request->legal_text_id)->exists()){
                $setting_id = LegalTextsSettings::insertGetId([
                    'assigned_for' => $request->assigned_for,
                    'legal_texts_id' => $request->legal_text_id,
                    'required' => $request->required,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
                $legal_text_settings = LegalTextsSettings::where('id', $setting_id)->get();
                $details = $this->getLegalTextsSettingsDetails($legal_text_settings);
                $result = array();
                foreach ($details as $detail) {
                    $result = $detail;
                }
                return response()->json([
                    'legalTextsSettings' => $result,
                    'status' => 'Success',
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
                'message' => $exception,
            ], 500);
        }
    } // End Function

    public function getLegalTextsSettingsDetails($settings):array
    {
        $result_array = array();
        if (!empty($settings)){
            foreach ($settings as $setting) {
                $legal_text = LegalTexts::where('version_id', $setting->legal_texts_id)
                    ->where('is_published',1)->where('is_active',1)->first();
                if (!empty($legal_text)) {
                    $result_array[] = [
                        'id' => $setting->id,
                        'assigned_for' => $setting->assigned_for,
                        'legal_text_id' => $legal_text->id,
                        'legal_text_version_id' => $legal_text->version_id,
                        'legal_text_name' => $legal_text->name,
                        'legal_text_element' => $legal_text->element,
                        'required' => $setting->required
                    ];
                }
            }
        }
        return $result_array;
    } // End Function

    /**
     * Method allow to delete the Legal Text Setting.
     * @param  $setting_id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function deleteSettings($setting_id):JsonResponse
    {
        try {
            if (LegalTextsSettings::where('id', $setting_id)->exists()){
                LegalTextsSettings::where('id', $setting_id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Legal Text Setting is deleted Successfully'
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
     * Method allow to show the complete details of the legalText.
     * @param  $version_id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function show($version_id):JsonResponse
    {
        try {
            $legalTexts = $this->getLegalTextsByVersionId($version_id);
            if (!empty($legalTexts)) {
                $draft_details = array();
                $publish_details = array();
                for ($i = 0; $i < 1; $i++) {
                    $draft_details = $legalTexts[$i];
                }
                for ($i = 1; $i < count($legalTexts); $i++) {
                    $publish_details[] = $legalTexts[$i];
                }
                return response()->json([
                    'draftDetails' => $draft_details,
                    'publishDetails' => $publish_details,
                    'status' => 'Success',
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
     * Method allow to show the detail of the particular version of legalText
     * @param $version_id
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function showDetail($version_id, $id):JsonResponse
    {
        try {
            $legalText = DB::table('legal_texts')
                ->where('id',$id)
                ->where('version_id',$version_id)
                ->first();
            if (!empty($legalText)){
                return response()->json([
                    'legalTextDetail' => $legalText,
                    'status' => 'Success',
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
     * Method allow to show the active legal texts
     * @return JsonResponse
     * @throws Exception
     */
    public function showActive():JsonResponse
    {
        try {
            $legalTexts = LegalTexts::where('is_active',1)
                ->where('is_published',1)
                ->get();
            $legalTexts_details = array();
            if (!empty($legalTexts)){
                foreach ($legalTexts as $legalText){
                    $legalTexts_details[] = [
                        'id' => $legalText->id,
                        'version_id' => $legalText->version_id,
                        'name' => $legalText->name,
                    ];
                }
                return response()->json([
                    'legalTextDetail' => $legalTexts_details,
                    'status' => 'Success',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query for active '
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

    public function getLegalTextsByVersionId($version_id)
    {
        try {
            $result_array = array();
            if (LegalTexts::where('version_id',$version_id)->exists()){
                $result_array = DB::table('legal_texts')->where('version_id',$version_id)->orderBy('id','desc')->get();
                return $result_array;
            } else {
                return $result_array;
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
     * Method allow to softDelete the particular legalText
     * @param $version_id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function destroy($version_id):JsonResponse
    {
        try {
            if (LegalTexts::where('version_id',$version_id)->exists()){
                $legal = LegalTexts::where('version_id', $version_id)->first();
                // $this->destroyRelations($legal);
                $legal_texts = LegalTexts::where('version_id',$version_id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Legal text is deleted successfully'
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

    public function destroyRelations($legal_text):bool
    {
        $impression = ['impression_id' => null, 'impression' => 0];
        $data_privacy = ['data_privacy_id' => null, 'data_privacy' => 0];
        $terms_of_use = ['terms_of_use_id' => null, 'terms_of_use' => 0];
        // System Settings
        $legal_text->systemsImpressum()->update($impression);
        $legal_text->systemsDataPrivacy()->update($data_privacy);
        $legal_text->systemsTerms()->update($terms_of_use);

        return true;

    } // End Function

    /**
     * Method allow to delete the group of legalTexts
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->legal_texts_id)){
                foreach ($request->legal_texts_id as $legal_text_id)
                {
                    $legal = LegalTexts::where('version_id', $legal_text_id)
                        ->where('is_published',1)->where('is_active',1)->first();
                    // $this->destroyRelations($legal);
                    $legal_text = LegalTexts::where('version_id', $legal_text_id)->delete();
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The legal Texts are deleted successfully',
                ],200);
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
    }

    /**
     * Method allow to retrieve the list of deleted legalTexts
     * @return JsonResponse
     * @throws ValidationException
     */
    public function retrieve():JsonResponse
    {
        try {
            $legal_texts = DB::table('legal_texts')
                ->select('version_id')
                ->groupBy('version_id')
                ->get();
            $legal_text_details = array();
            if (!empty($legal_texts)) {
                foreach ($legal_texts as $legal_text) {
                    $detailsTrashed = LegalTexts::where('version_id', $legal_text->version_id)->onlyTrashed()->orderBy('id', 'desc')->first();
                    if ($detailsTrashed) {
                        $published = DB::table('legal_texts')
                            ->where('version_id', $legal_text->version_id)
                            ->where('is_published', 1)->first();
                        $published_at = DB::table('legal_texts')
                            ->where('version_id', $legal_text->version_id)->max('published_at');
                        $created_at = DB::table('legal_texts')
                            ->where('version_id', $legal_text->version_id)->min('created_at');
                        if ($published) {
                            $is_published = 1;
                        } else {
                            $is_published = 0;
                        }
                        $legal_text_details[] = [
                            'id' => $detailsTrashed->id,
                            'version_id' => $detailsTrashed->version_id,
                            'name' => $detailsTrashed->name,
                            'is_published' => $is_published,
                            'published_at' => $published_at,
                            'created_at' => $created_at,
                        ];
                    }
                }
            }
            return response()->json([
                'legalTextDetails' => $legal_text_details,
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
     * Method allow to restore the particular legalText
     * @param $version_id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function restore($version_id):JsonResponse
    {
        try {
            if (LegalTexts::where('version_id',$version_id)->onlyTrashed()->exists()){
                $legal_texts = LegalTexts::where('version_id',$version_id)->onlyTrashed()->restore();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Legal text is restored successfully'
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
     * Method allow to restore the group of legalTexts
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function massRestore(Request $request):JsonResponse
    {
        try {
            if (!empty($request->legal_texts_id)) {
                foreach ($request->legal_texts_id as $legal_text_id) {
                    LegalTexts::where('version_id', $legal_text_id)->withTrashed()->restore();
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Legal Texts are restored successfully'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one legalText to restore'
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
     * Method allow to retrieve the information globally for the active legal Texts
     * @param $id // Technically it is version_id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function showDetailWithId($id):JsonResponse
    {
        try {
            $legalText = DB::table('legal_texts')
                ->where('version_id',$id)
                ->where('is_active',1)
                ->where('is_published',1)
                ->first();
            if (!empty($legalText)){
                return response()->json([
                    'legalTextDetail' => $legalText,
                    'status' => 'Success',
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

} // End Class

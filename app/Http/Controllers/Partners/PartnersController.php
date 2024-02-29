<?php

namespace App\Http\Controllers\Partners;

use App\Http\Controllers\Contacts\ContactController;
use App\Http\Controllers\Controller;
use App\Models\FoldersFiles;
use App\Models\IndustriesSectors;
use App\Models\Labels;
use App\Models\Partners;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Nette\Schema\ValidationException;

class PartnersController extends Controller
{
    /**
     * Method allow to display list of all Partners.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'search_keyword' => 'nullable|string|min:3|max:200',
                'limit' => 'nullable|in:10,20,30,50'
            ]);
            if ($request->limit == null){
                $limit = 10;
            } else {
                $limit = (int)$request->limit;
            }
            $partners = Partners::where('code', '!=', null);
            if ($request->search_keyword != null){
                $keyword = $request->search_keyword;
                $partners = $partners->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%');
                });
            }
            $pagination_details = $this->getPaginationDetails($partners, $limit, count($partners->get()));
            $partners_details = $this->getPartnerList($partners->paginate($limit));
            return response()->json([
                'partnerDetails' => $partners_details,
                'partners_pagination' => $pagination_details,
                'status' => 'Success',
            ], 200);

        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getLine(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to get 8 random partners.
     * @return JsonResponse
     * @throws Exception
     */
    public function randomPartners(){
        try {
            $partners = Partners::inRandomOrder()->where('main_logo_file_id', '!=', null)->limit(8);
            $limit = 8;
            $partners_details = $this->getPartnerList($partners->paginate(8));
            return response()->json([
                'partnerDetails' => $partners_details,
                'status' => 'Success',
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Method allow to get Partners with GroupBy of Industries and sectors
     * @return JsonResponse
     * @throws Exception
     */
    public function indexGroupBySectorsIndex():JsonResponse
    {
        try {
            $sectors = IndustriesSectors::all();
            foreach ($sectors as $sector) {
                $sectors_partners = $sector->industriesSectorsGroups;
                if (!$sectors_partners->isEmpty()) {
                    $partner_details = $this->getBasicPartnerDetails($sectors_partners);
                    $result_array[] = [
                        'name' => $sector->name,
                        'partners' => $partner_details,
                    ];
                }
            }
            $partners = Partners::whereDoesntHave('partnersIndustriesSectors')->get();
            $no_partner_details = $this->getBasicPartnerDetails($partners);
            $result_array[] = ['name' => 'Unassigned', 'partners' => $no_partner_details];
            return response()->json([
                'partnerDetails' => $result_array ?? array(),
                'status' => 'Success',
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function getBasicPartnerDetails($sectors_partners):array
    {
        $partner_details = array();
        if (!empty($sectors_partners)) {
            foreach ($sectors_partners as $sectors_partner) {
                if ($sectors_partner->logo_rectangle_file_id != null) {
                    $partner_logo_rectangle_url = $sectors_partner->partnerRectangleLogo->file_path;
                } else {
                    $partner_logo_rectangle_url = null;
                }
                if ($sectors_partner->logo_square_file_id != null) {
                    $partner_logo_square_url = $sectors_partner->partnerSquareLogo->file_path;
                } else {
                    $partner_logo_square_url = null;
                }
                if ($sectors_partner->main_logo_file_id != null) {
                    $partner_main_logo_url = $sectors_partner->partnerMainLogo->file_path;
                } else {
                    $partner_main_logo_url = null;
                }
                $partner_details[] = [
                    'name' => $sectors_partner->name,
                    'logo_rectangle_url' => $partner_logo_rectangle_url,
                    'logo_square_url' => $partner_logo_square_url,
                    'main_logo_url' => $partner_main_logo_url,
                ];
            }
        }
        return $partner_details;
    } // End Function

    /**
     * Method allow to update the logos of the Partners.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function storeLogos(Request $request, $id):JsonResponse
    {
        try {
            if (Partners::where('id', $id)->exists()) {
                $request->validate([
                    'type' => 'required|in:logo_rectangle,logo_square,main_logo',
                ]);
                $partners = Partners::where('id', $id)->first();
                if ($request->type === 'main_logo') {
                    $request->validate([
                        'file_id' => 'required|integer'
                    ]);
                    $partners->main_logo_file_id = $request->file_id ?? null;
                    $partners->save();
                    if ($partners->main_logo_file_id != null) {
                        $partner_main_logo_url = $partners->partnerMainLogo->file_path;
                    } else {
                        $partner_main_logo_url = null;
                    }
                    $partner_details = [
                        'id' => $id,
                        'main_logo_url' => $partner_main_logo_url
                    ];
                } else {
                    $request->validate([
                        'store_type' =>  'required|string',
                        'file' => 'required'
                    ]);
                    $media = $request->file;
                    $edited_logo_id = $this->storeTempFile($media, $request->store_type);
                    $partners = Partners::where('id', $id)->first();
                    if ($request->type == 'logo_rectangle') {
                        $partners->logo_rectangle_file_id = $edited_logo_id;
                        $partners->save();
                        if ($partners->logo_rectangle_file_id != null) {
                            $partner_logo_rectangle_url = $partners->partnerRectangleLogo->file_path;
                        } else {
                            $partner_logo_rectangle_url = null;
                        }
                        $partner_details = [
                            'id' => $id,
                            'logo_rectangle_url' => $partner_logo_rectangle_url
                        ];
                    } else {
                        $partners->logo_square_file_id = $edited_logo_id;
                        $partners->save();
                        if ($partners->logo_square_file_id != null) {
                            $partner_logo_square_url = $partners->partnerSquareLogo->file_path;
                        } else {
                            $partner_logo_square_url = null;
                        }
                        $partner_details = [
                            'id' => $id,
                            'logo_square_url' => $partner_logo_square_url
                        ];
                    }
                }
                return response()->json([
                    'partnerDetails' => $partner_details,
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
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to delete the Logos for the system.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteLogos(Request $request, $id):JsonResponse
    {
        try {
            if(Partners::where('id',$id)->exists()) {
                $request->validate([
                    'type' => 'required|in:logo_rectangle,logo_square,main_logo',
                ]);
                $partners = Partners::where('id',$id)->first();
                if ($request->type == 'logo_rectangle'){
                    $file_name = $partners->logo_rectangle_file_id;
                } else {
                    $file_name = $partners->logo_square_file_id;
                }
                if(!empty($partners)) {
                        //Delete old media file from disk
                        if(!empty($file_name)) {
                            $delete_file = $this->destroyMediaFile($file_name);
                        }
                        if( $request->type == 'logo_rectangle') {
                            $partners->logo_rectangle_file_id = null;
                        } elseif ($request->type == 'main_logo') {
                            $partners->main_logo_file_id = null;
                        } else {
                            $partners->logo_square_file_id = null;
                        }
                        $partners->save();

                        return response()->json([
                            'status' => 'Success',
                            'message' => 'Respective partners logo is deleted successfully',
                        ], 200);
                } else {
                    return response()->json([
                        'status' => 'No Content',
                        'message' => 'There is no relevant information for selected query'
                    ],210);
                }
            } else{
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to update the Finance Details of the Partners.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateFinance(Request $request, $id):JsonResponse
    {
        try {
            if (Partners::where('id',$id)->exists()){
                $partners = Partners::where('id',$id)->first();
                $partners->tax_number = $request->tax_number;
                $partners->ust_id = $request->ust_id;
                $partners->debtor_number = $request->debtor_number;
                $partners->creditor_number = $request->creditor_number;
                $partners->invoices_seperated = $request->invoices_seperated ?? 1;
                $partners->billing_send_email = $request->billing_email;
                $partners->payment_target_days = $request->payment_target_days;
                $partners->save();

                $partner_details = [
                    'id' => $id,
                    'tax_number' => $partners->tax_number,
                    'ust_id' => $partners->ust_id,
                    'debtor_number' => $partners->debtor_number,
                    'creditor_number' => $partners->creditor_number,
                    'invoices_seperated' => $partners->invoices_seperated,
                    'billing_email' => $partners->billing_send_email,
                    'payment_target_days' => $partners->payment_target_days,
                ];

                return response()->json([
                    'partnerDetails' => $partner_details,
                    'status' => 'Success',
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
     * Method allow to update the Notes of the Partners.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateNotes(Request $request, $id):JsonResponse
    {
        try {
            if (Partners::where('id',$id)->exists()){
                $partners = Partners::where('id',$id)->first();
                $partners->notes = $request->notes;
                $partners->save();

                $partner_details = [
                    'id' => $id,
                    'notes' => $partners->notes,
                ];
                return response()->json([
                    'partnerDetails' => $partner_details,
                    'status' => 'Success',
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

    public function importPartnersResources(Request $request):JsonResponse
    {
        try {
            $partners = Partners::all();
            if (!empty($partners)) {
                foreach ($partners as $partner) {
                    $update = $this->updateResources($request, $partner->id);
                }
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'Resources had been assigned Successfully'
            ], 200);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to assign the Labels for Partners.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateResources(Request $request, $id):JsonResponse
    {
        try {
            if (Partners::where('id',$id)->exists()){
                $partner = Partners::where('id',$id)->first();
                $partner_resources = $partner->resources;
                // Delete related records
                if (!empty($partner_resources)){
                    foreach ($partner_resources as $partner_resource){
                        $partner->resources()->detach($partner_resource->id);
                    }
                }
                // Save new related data
                if (!empty($request->resources_id)){
                    foreach ($request->resources_id as $resource_id){
                        $partner->resources()->attach($resource_id, ['created_at'=>Carbon::now()->format('Y-m-d H:i:s')]);
                    }
                }
                // Build Response array
                $updated_partner = Partners::where('id',$id)->first();
                $partner_label_details = $updated_partner->resources;
                $partner_resources_details = $this->getPartnersResourcesDetails($partner_label_details);

                return response()->json([
                    'partner_resources' => $partner_resources_details,
                    'status' => 'Success',
                    'message' => 'Labels are updated successfully'
                ], 200);

            } else{
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);

            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to assign the Labels for Partners.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateLabels(Request $request, $id):JsonResponse
    {
        try {
            if (Partners::where('id',$id)->exists()){
                $partner = Partners::where('id',$id)->first();
                $partner_labels = $partner->partnersLabels;
                // Delete related records
                if (!empty($partner_labels)){
                    foreach ($partner_labels as $partner_label){
                        $partner->partnersLabels()->detach($partner_label->id);
                    }
                }
                // Save new related data
                if (!empty($request->labels_id)){
                    foreach ($request->labels_id as $label_id){
                        $partner->partnersLabels()->attach($label_id, ['created_at'=>Carbon::now()->format('Y-m-d H:i:s')]);
                    }
                }
                // Build Response array
                $updated_partner = Partners::where('id',$id)->first();
                $partner_label_details = $updated_partner->partnersLabels;
                $partner_details = $this->getPartnersDetails($partner_label_details);

                return response()->json([
                    'partner_labels' => $partner_details,
                    'status' => 'Success',
                    'message' => 'Labels are updated successfully'
                ], 200);

            } else{
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);

            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to assign the Industries Sectors for Partners.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateIndustriesSectors(Request $request, $id):JsonResponse
    {
        try {
            if (Partners::where('id',$id)->exists()){
                $partner_details = array();
                $partner = Partners::where('id',$id)->first();
                $partner_industries_sectors = $partner->partnersIndustriesSectors;
                // Delete releated records
                if (!empty($partner_industries_sectors)){
                    foreach ($partner_industries_sectors as $partner_industries_sector){
                        $partner->partnersIndustriesSectors()->detach($partner_industries_sector->id);
                    }
                }
                // Save new releated data
                if (!empty($request->industries_sectors_id)){
                    foreach ($request->industries_sectors_id as $industries_sector_id){
                        $partner->partnersIndustriesSectors()->attach($industries_sector_id, ['created_at'=>Carbon::now()->format('Y-m-d H:i:s')]);
                    }
                }
                // Build Response array
                $partner_industries_sectors_new = Partners::where('id',$id)->first();
                $partner_details = $this->getPartnersDetails($partner_industries_sectors_new->partnersIndustriesSectors);

                return response()->json([
                    'partnerDetails' => $partner_details,
                    'status' => 'Success',
                    'message' => 'Sectors are updated successfully'
                ], 200);

            } else{
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to show a particular Partner.
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function show($id):JsonResponse
    {
        try {
            if (Partners::where('id',$id)->exists()) {
                $partner = Partners::where('id', $id)->get();
                $partners_details_arrays = $this->getPartnerList($partner, 'show');
                $partner_details = array();
                foreach ($partners_details_arrays as $partners_details_array) {
                    $partner_details = $partners_details_array;
                }
                return response()->json([
                    'partnerDetails' => $partner_details,
                    'message' => 'Success',
                ], 200);
            } else {
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
     * Method allow to show all contacts for the respective partner
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function getContacts($id):JsonResponse
    {
        try {
            if (Partners::where('id',$id)->exists()) {
                $partner = Partners::where('id', $id)->first();
                $partner_contacts = $partner->users()->get();
                $contacts = new ContactController();
                $contact_details = $contacts->getContactDetailsOverview($partner_contacts);
                return response()->json([
                    'partnerDetails' => $contact_details,
                    'status' => 'Success',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow get the list of single Partner of all Partners.
     * @param $partners
     * @return array
     * @throws ValidationException
     */
    public function getPartnerList($partners, $condition = null):array
    {
        $partners_details = array();
        if (!empty($partners)) {
            foreach ($partners as $partner) {

                $partners_labels = $this->getPartnersDetails($partner->partnersLabels);
                $partners_sectors = $this->getPartnersDetails($partner->partnersIndustriesSectors);

                if ($partner->countries_id != null){
                    $country_name = $partner->country->name;
                    $country_emoji = $partner->country->emoji;
                } else {
                    $country_name = null;
                    $country_emoji = null;
                }

                // Logos
                if ($partner->logo_rectangle_file_id != null) {
                    $partner_logo_rectangle_url = $partner->partnerRectangleLogo->file_path;
                } else {
                    $partner_logo_rectangle_url = null;
                }
                if ($partner->logo_square_file_id != null) {
                    $partner_logo_square_url = $partner->partnerSquareLogo->file_path;
                } else {
                    $partner_logo_square_url = null;
                }
                if ($partner->main_logo_file_id != null) {
                    $partner_main_logo_url = $partner->partnerMainLogo->file_path;
                } else {
                    $partner_main_logo_url = null;
                }

                // Resources
                $partner_label_details = $partner->resources;
                $partner_resources_details = $this->getPartnersResourcesDetails($partner_label_details);

                // Contacts
                if ($condition != null) {
                    $partners_users = $partner->users;
                    $partners_users_details = $this->getPartnersUsersDetails($partners_users);
                }

                // Response Array
                $partners_details[] = [
                    'id' => $partner->id,
                    'code' => $partner->code,
                    'name' => $partner->name,
                    'address_1' => $partner->street,
                    'address_2' => $partner->street_extra,
                    'zip_code' => $partner->zip_code,
                    'city' => $partner->city,
                    'country_id' => $partner->countries_id,
                    'country_name' => $country_name,
                    'country_emoji' => $country_emoji,
                    'tax_number' => $partner->tax_number,
                    'ust_id' => $partner->ust_id,
                    'debtor_number' => $partner->debtor_number,
                    'creditor_number' => $partner->creditor_number,
                    'invoices_seperated' => $partner->invoices_seperated,
                    'billing_email' => $partner->billing_send_email,
                    'payment_target_days' => $partner->payment_target_days,
                    'notes' => $partner->notes,
                    'logo_rectangle_url' => $partner_logo_rectangle_url,
                    'logo_square_url' => $partner_logo_square_url,
                    'main_logo_url' => $partner_main_logo_url,
                    'labels' => $partners_labels,
                    'industries_sectors' => $partners_sectors,
                    'contacts' => $partners_users_details ?? array(),
                    'resources' => $partner_resources_details,
                    'created_by' => $partner->created_by,
                    'created_at' => $partner->created_at,
                ];
            }
        }
        return $partners_details;
    } // End function

    /**
     * Method allow to get details of Contacts attached to the User
     * @param $users
     * @return array
     */
    public function getPartnersUsersDetails($users):array
    {
        $result_array = array();
        if (!empty($users)) {
            foreach ($users as $user) {
                if ($user->titles_id != null) {
                    $title = $user->title->title;
                } else {
                    $title = null;
                }
                if ($user->profile_photo_id != null) {
                    $profile_photo_url = $user->profilePhoto->file_path;
                } else {
                    $profile_photo_url = null;
                }
                $result_array[] = [
                    'id' => $user->id,
                    'salutation_id' => $user->salutations_id,
                    'salutation' => $user->salutation->salutation,
                    'title_id' => $user->titles_id,
                    'title' => $title,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'username' => $user->username,
                    'profile_photo_id' => $user->profile_photo_id,
                    'profile_photo_url' => $profile_photo_url,
                ];
            }
        }
        return $result_array;
    }

    /**
     * Method allow to get details of Master Data by passing parameters.
     * @param $details
     * @return array
     */
    public function getPartnersDetails($details):array
    {
        $partner_details = array();
        if (!empty($details)){
            foreach ($details as $detail){
                $partner_details[] = [
                    'id' => $detail->id,
                    'name' => $detail->name,
                ];
            }
        }
        return $partner_details;
    } // End Function

    /**
     * Method allow to get details of Master Data by passing parameters.
     * @param $details
     * @return array
     */
    public function getPartnersResourcesDetails($details):array
    {
        $partner_details = array();
        if (!empty($details)){
            foreach ($details as $detail){
                $partner_details[] = [
                    'id' => $detail->id,
                    'name' => $detail->name,
                    'slug' => $detail->slug
                ];
            }
        }
        return $partner_details;
    } // End Function

    /**
     * Method allow get the list of filtered partners from all Partners.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function getFilterPartners(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'search_keyword' => 'nullable|string|min:3|max:200',
                'limit' => 'nullable|in:10,20,30,50'
            ]);
            if ($request->limit == null){
                $limit = 10;
            } else {
                $limit = (int)$request->limit;
            }
            $partners = Partners::where('id', '!=', 1);
            if ($request->country_id != null) {
                $partners = $partners->where('countries_id', '=', $request->country_id);
            }
            if ($request->city != null) {
                $request->validate(['city' => 'nullable|string|min:3|max:200']);
                $city = $request->city;
                $partners = $partners->where(function ($query) use ($city){
                    $query->where('city', 'like', '%' . $city . '%');
                });
            }
            if ($request->zip_code != null) {
                $partners = $partners->where('zip_code', '=', (int)$request->zip_code);
            }
            if ($request->search_keyword != null){
                $keyword = $request->search_keyword;
                $partners = $partners->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%');
                });
            }
            if ($request->labels != null) {
                $labels = json_decode($request->labels);
                foreach ($labels as $label){
                    $label_details = Labels::where('id', $label)->first();
                    if (!empty($label_details)){
                        $partner_labels[] = $label_details->partners()->pluck('id')->toArray();
                    }
                }
                $final_array = call_user_func_array('array_merge',$partner_labels);
                $partner_condition_group = array_unique($final_array);
                $partners = $partners->whereIn('id', $partner_condition_group);
            }
            if ($request->sectors != null) {
                $sectors = json_decode($request->sectors);
                foreach ($sectors as $sector){
                    $label_details = IndustriesSectors::where('id', $sector)->first();
                    if (!empty($label_details)){
                        $partner_sectors[] = $label_details->industriesSectorsGroups()->pluck('id')->toArray();
                    }
                }
                $final_array = call_user_func_array('array_merge',$partner_sectors);
                $partner_condition_group = array_unique($final_array);
                $partners = $partners->whereIn('id', $partner_condition_group);
            }
            $partners_details = $this->getPartnerList($partners->paginate($limit));
            $pagination_details = $this->getPaginationDetails($partners, $limit, count($partners->get()));
            return response()->json([
                'partnerDetails' => $partners_details,
                'partners_pagination' => $pagination_details,
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
     * Method allow to store or create the new Partner.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            // Check User input
            $request->validate([
                'name' => 'required|string|max:255|unique:partners',
            ]);
            // Generate the random code for the regulation
            $x = 0;
            do {
                $randomString = $this->generateCode(12);
                if (Partners::where('code', '=', $randomString)->first()) {
                    $x = 1;
                }
            } while ($x > 0);
            $partner_id = Partners::insertGetId([
                'code' => $randomString,
                'name' => $request->name,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            $partners = Partners::where('id',$partner_id)->get();

            // Get all the details of the Partners and display as the array
            $partner_details_lists = $this->getPartnerList($partners);
            $partner_details = array();
            foreach ($partner_details_lists as $partner_details_list) {
                $partner_details = $partner_details_list;
            }
            return response()->json([
                'partnerDetails' => $partner_details,
                'status' => 'Success',
                'message' => 'Partner is stored successfully.',
            ], 200);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End function

    public function importProductionPartners(Request $request):JsonResponse
    {
        try {
            $array = (object)array();
            $excel_parameters = Excel::toArray($array, $request->media);
            foreach ($excel_parameters[0] as $key => $excel_parameter) {
                if ($key != 0) {
                    $name = $excel_parameter[7];
                    if (! Partners::where('name',$name)->exists()) {
                        $x = 0;
                        do {
                            $randomString = $this->generateCode(12);
                            if (Partners::where('code', '=', $randomString)->first()) {
                                $x = 1;
                            }
                        } while ($x > 0);
                        $partner_id = Partners::insertGetId([
                            'code' => $randomString,
                            'name' => $name,
                            'countries_id' => 84,
                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        ]);
                    } else {
                        $partner = Partners::where('name', $name)->first();
                        $partner_id = $partner->id;
                        $partner->street = $excel_parameter[3];
                        $partner->zip_code = $excel_parameter[5];
                        $partner->city = $excel_parameter[6];
                        $partner->countries_id = 84;
                        $partner->save();
                    }
                    if ($partner_id != null) {
                        $label_id = Labels::where('name', 'Klimafreundlicher-Mittelstand')->first()->id;
                        $partner = Partners::where('id', $partner_id)->first();
                        $partner->partnersLabels()->detach();
                        $partner_labels = $partner->partnerLabels;
                        if ($partner_labels == null) {
                            $partner->partnersLabels()->attach($label_id, ['created_at' => Carbon::now()->format('Y-m-d H:i:s')]);
                        }
                        if (!User::where('email', $excel_parameter[4])->exists()) {
                            $password = 'Start1234@$';
                            User::insertGetId([
                                'salutations_id' => $excel_parameter[0],
                                'firstname' => $excel_parameter[1],
                                'lastname' => $excel_parameter[2],
                                'email' => $excel_parameter[4],
                                'password' => Hash::make($password),
                                'address_1' => $excel_parameter[3],
                                'city' => $excel_parameter[6],
                                'zip_code' => $excel_parameter[5],
                                'partners_id' => $partner_id,
                                'country_id' => 84,
                                'sys_admin' => 0,
                                'sys_customer' => 1,
                                'created_at' => Carbon::now()->format('Y-m-d H:i:s')
                            ]);
                        } else {
                            $user = User::where('email', $excel_parameter[4])->first();
                            $user->firstname = $excel_parameter[1];
                            $user->lastname = $excel_parameter[2];
                            $user->address_1 = null;
                            $user->city = null;
                            $user->zip_code = null;
                            $user->country_id = null;
                            $user->save();
                        }
                    }
                }
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'Partners and respective contacts are imported successfully.',
            ], 200);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function




    /**
     * Method allow to update the general details of the post.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateGeneral(Request $request, $id):JsonResponse
    {
        try {
            if (Partners::where('id', $id)->exists()) {
                $partners = Partners::where('id', $id)->first();
                $request->validate([
                    'name' => ['required', 'string', Rule::unique('partners', 'name')->ignore($partners->id)],
                    'zip_code' => 'nullable|integer',
                ]);

                $partners->name = $request->name;
                $partners->street = $request->address_1;
                $partners->street_extra = $request->address_2;
                $partners->zip_code = $request->zip_code;
                $partners->city = $request->city;
                $partners->countries_id = $request->country_id;
                $partners->save();

                if ($partners->countries_id != null) {
                    $country_name = $partners->country->name;
                    $country_emoji = $partners->country->emoji;
                } else {
                    $country_name = null;
                    $country_emoji = null;
                }

                $partner_details = [
                    'id' => $id,
                    'name' => $partners->name,
                    'address_1' => $partners->street,
                    'address_2' => $partners->street_extra,
                    'zip_code' => (int)$partners->zip_code,
                    'city' => $partners->city,
                    'country_id' => (int)$partners->countries_id,
                    'country_name' => $country_name,
                    'country_emoji' => $country_emoji,
                ];

                return response()->json([
                    'partnerDetails' => $partner_details,
                    'status' => 'Success',
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
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to delete the particular Partner.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (Partners::where('id',$id)->exists()) {
                Partners::where('id',$id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Partner is deleted successfully',
                ],200);
            }else{
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to mass delete the set of groups.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->partners_id)) {
                foreach ($request->partners_id as $partner_id) {
                    $category = Partners::where('id', $partner_id);
                    $category->delete();
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Partners are deleted successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one partner to delete'
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

} // End class

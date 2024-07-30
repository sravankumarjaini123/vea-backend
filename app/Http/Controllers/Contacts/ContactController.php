<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Partners\PartnersController;
use App\Http\Controllers\Users\UserController;
use App\Models\Labels;
use App\Models\Partners;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Nette\Schema\ValidationException;

class ContactController extends Controller
{
    public function __construct()
    {
        // Code
    }

    /**
     * Method allow to display list of all USERS
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search_keyword' => 'nullable|string|min:3|max:200',
                'limit' => 'nullable|in:10,20,30,50'
            ]);
            if ($request->limit == null) {
                $limit = 10;
            } else {
                $limit = (int)$request->limit;
            }
            $all_users = User::where('sys_admin', 0);
            if ($request->search_keyword != null){
                $keyword = $request->search_keyword;
                $all_users = $all_users->where(function ($query) use ($keyword) {
                    $query->orwhere('firstname', 'like', '%' . $keyword . '%');
                    $query->orwhere('lastname', 'like', '%' . $keyword . '%');
                    $query->orwhere('email', 'like', '%' . $keyword . '%');
                });
            }
            $pagination_details = $this->getPaginationDetails($all_users, $limit, count($all_users->get()));
            $users_details = $this->getContactDetailsOverview($all_users->paginate($limit));

            return response()->json([
                'customers' => $users_details,
                'customers_pagination' => $pagination_details,
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to get Details Overview of particular contact
     * @param $contacts
     * @return array
     */
    public function getContactDetailsOverview($contacts):array
    {
        $result_array = array();
        if(!empty($contacts)){
            foreach ($contacts as $contact){
                if ($contact->titles_id != null) {
                    $title = $contact->title->title;
                } else {
                    $title = null;
                }
                if ($contact->profile_photo_id != null) {
                    $profile_photo_url = $contact->profilePhoto->file_path;
                } else {
                    $profile_photo_url = null;
                }

                if ($contact->two_factor_secret != null) {
                    $has2FA = true;
                } else {
                    $has2FA = false;
                }
                if ($contact->country_id != null){
                    $country_name = $contact->country->name;
                    $country_emoji = $contact->country->emoji;
                } else {
                    $country_name = null;
                    $country_emoji = null;
                }
                $user_label_details = $contact->labels;
                $details = new PartnersController();
                $user_labels = $details->getPartnersDetails($user_label_details);

                if ($contact->partners_id != null) {
                    $company = $contact->company()->first();
                    $company_address = [
                        'code' => $company->code,
                        'name' => $company->name,
                        'email' => $company->email,
                        'VAT' => $company->VAT,
                        'tax_number' => $company->tax_number,
                        'address_1' => $company->street,
                        'address_2' => $company->street_extra,
                        'zip_code' => $company->zip_code,
                        'city' => $company->city,
                        'country_id' => $company->countries_id,
                        'country' => (!empty($company->country)) ? $company->country->name : '',
                    ];
                } else {
                    $company_address = null;
                }

                $result_array[] = [
                    'id' => $contact->id,
                    'salutation_id' => $contact->salutations_id,
                    'salutation' => $contact->salutation->salutation,
                    'title_id' => $contact->titles_id,
                    'title' => $title,
                    'firstname' => $contact->firstname,
                    'lastname' => $contact->lastname,
                    'email' => $contact->email,
                    'has2FA' => $has2FA,
                    'labels' => $user_labels,
                    'is_blocked' => $contact->is_blocked,
                    'profile_photo_id' => $contact->profile_photo_id,
                    'profile_photo_url' => $profile_photo_url,
                    'last_login' => $contact->last_login,
                    'personalAddress' => [
                        'address_1' => $contact->address_1,
                        'address_2' => $contact->address_2,
                        'zip_code' => $contact->zip_code,
                        'city' => $contact->city,
                        'country_id' => $contact->country_id,
                        'country' => $country_name,
                        'country_emoji' => $country_emoji,
                    ],
                    'companyAddress' => $company_address,
                    'created_at' => $contact->created_at,
                ];
            }
        }
        return $result_array;
    } // End Function

    /**
     * Method allow to get Details of  particular contact
     * @param $customer_detail
     * @param $usershow
     * @return array
     */
    public function getContactDetails($customer_detail, $user_show=null): array
    {
        $result_array = array();
        if (!empty($customer_detail)) {
            if ($customer_detail->salutations_id != null) {
                $salutation = $customer_detail->salutation->salutation;
            } else {
                $salutation = null;
            }

            if ($customer_detail->titles_id != null) {
                $title = $customer_detail->title->title;
            } else {
                $title = null;
            }
            if ($customer_detail->profile_photo_id != null) {
                $profile_photo_url = $customer_detail->profilePhoto->file_path;
            } else {
                $profile_photo_url = null;
            }

            $user_label_details = $customer_detail->labels;
            $details = new PartnersController();
            $user_labels = $details->getPartnersDetails($user_label_details);
            $personal_details = [
                'id' => $customer_detail->id,
                'salutation_id' => $customer_detail->salutations_id,
                'salutation' => $salutation,
                'title_id' => $customer_detail->titles_id,
                'title' => $title,
                'firstname' => $customer_detail->firstname,
                'lastname' => $customer_detail->lastname,
                'email' => $customer_detail->email,
                'username' => $customer_detail->username,
                'sys_admin' => $customer_detail->sys_admin,
                'sys_customer' => $customer_detail->sys_customer,
                'is_blocked' => $customer_detail->is_blocked,
                'verification_status' => $customer_detail->verification_status,
                'profile_photo_id' => $customer_detail->profile_photo_id,
                'profile_photo_url' => $profile_photo_url,
                'created_at' => $customer_detail->created_at,
                'labels' => $user_labels,
            ];

            // Get personal address details of particular contact
            if ($customer_detail->address_1 != null) {
                $personal_address = [
                    'address_1' => $customer_detail->address_1,
                    'address_2' => $customer_detail->address_2,
                    'zip_code' => $customer_detail->zip_code,
                    'city' => $customer_detail->city,
                    'country_id' => $customer_detail->country_id,
                    'country' => $customer_detail->country->name,
                    'country_emoji' => $customer_detail->country->emoji,
                ];
            } else {
                $personal_address = null;
            }

            // Get Company address details of particular contact
            if ($customer_detail->partners_id != null) {
                $company = $customer_detail->company()->first();
                $partner = Partners::where('id', $company->id)->first();
                $partners_sectors = (new PartnersController())->getPartnersDetails($partner->partnersIndustriesSectors);
                $company_address = [
                    'id' => $partner->id,
                    'code' => $company->code,
                    'name' => $company->name,
                    'email' => $company->email,
                    'VAT' => $company->VAT,
                    'tax_number' => $company->tax_number,
                    'address_1' => $company->street,
                    'address_2' => $company->street_extra,
                    'zip_code' => $company->zip_code,
                    'city' => $company->city,
                    'country_id' => $company->countries_id,
                    'country' => (!empty($company->country)) ? $company->country->name : '',
                    'sectors' => $partners_sectors,
                ];
            } else {
                $company_address = null;
            }
            $user_logins = array();

            if(!empty($user_show)) {
                if (!empty($customer_detail->userLogins)) {
                    foreach ($customer_detail->userLogins as $userLogin) {
                        $user_logins[] = [
                            'users_id' => $userLogin->users_id,
                            'ip' => $userLogin->ip,
                            'date' => $userLogin->date,
                            'browser_agent' => $userLogin->browser_agent,
                            'status' => $userLogin->status,
                        ];
                    }
                }
            }

            $result_array = array_merge($result_array, ['personalInformation' => $personal_details,
                'personalAddress' => $personal_address,
                'companyAddress' => $company_address,
                'userLogins' => $user_logins,
            ]);
        }
        return $result_array;
    } // End Function

    /**
     * Method allow to show the particular customer.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id): JsonResponse
    {
        try {
            if (User::where('id', $id)->where('sys_admin', 0)->where('sys_customer', 1)->exists()) {
                $customer_details = User::with(['userLogins'])->where('id', $id)->first();
                $result = $this->getContactDetails($customer_details, 'usershow');
                return response()->json([
                    'personalInformation' => $result['personalInformation'],
                    'personalAddress' => $result['personalAddress'],
                    'companyAddress' => $result['companyAddress'],
                    'userLogins' => $result['userLogins'],
                    'message' => 'Success'
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
                'line' => $exception->getLine(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to filter the contacts and display accordingly
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getFilterContacts(Request $request):JsonResponse
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
            $contacts = User::where('sys_admin', '=', 0)->where('sys_customer', '=', 1);
            if ($request->is_blocked != null && $request->is_blocked != 0) {
                $contacts = $contacts->where('is_blocked', '=', 1);
            }
            if ($request->country_id != null) {
                $contacts = $contacts->where('country_id', '=', $request->country_id);
            }
            if ($request->city != null) {
                $request->validate(['city' => 'nullable|string|min:3|max:200']);
                $city = $request->city;
                $contacts = $contacts->where(function ($query) use ($city){
                    $query->where('city', 'like', '%' . $city . '%');
                });
            }
            if ($request->zip_code != null) {
                $contacts = $contacts->where('zip_code', '=', (int)$request->zip_code);
            }
            if ($request->labels != null){
                $labels = json_decode($request->labels);
                foreach ($labels as $label){
                    $label_details = Labels::where('id', $label)->first();
                    if (!empty($label_details)){
                        $contact_labels[] = $label_details->users()->pluck('id')->toArray();
                    }
                }
                $final_array = call_user_func_array('array_merge',$contact_labels);
                $contact_condition_group = array_unique($final_array);
                $contacts = $contacts->whereIn('id', $contact_condition_group);
            }
            $keyword = $request->search_keyword;
            if ($keyword != null) {
                $contacts = $contacts->where(function ($query) use ($keyword) {
                    $query->orwhere('firstname', 'like', '%' . $keyword . '%');
                    $query->orwhere('lastname', 'like', '%' . $keyword . '%');
                    $query->orwhere('email', 'like', '%' . $keyword . '%');
                });
            }
            $total_count = $contacts->count();
            $contacts_details = $this->getContactDetailsOverview($contacts->paginate($limit));
            $pagination_details = $this->getPaginationDetails($contacts, $limit, $total_count);
            return response()->json([
                'customers' => $contacts_details,
                'customers_pagination' => $pagination_details,
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to show the login profile for the Web app.
     * @return JsonResponse
     * @throws Exception
     */
    public function contactProfile(): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            if (!empty($user)) {
                $details = User::where('id', $user->id)->first();
                $result = $this->getContactDetails($details);
                return response()->json([
                    'personalInformation' => $result['personalInformation'],
                    'personalAddress' => $result['personalAddress'],
                    'companyAddress' => $result['companyAddress'],
                    'message' => 'Success'
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
     * Method allow to add the personal address of the customer
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function storePersonal(Request $request, $id):JsonResponse
    {
        try {
            $request->validate([
                'address_1' => 'required|string',
                'zip_code' => 'required|integer',
                'city' => 'required|string',
                'country_id' => 'required|integer'
            ]);

            $user = User::where('id',$id)->first();
            if ($user){
                $user->address_1 = $request->address_1;
                $user->address_2 = $request->address_2;
                $user->zip_code = $request->zip_code;
                $user->city = $request->city;
                $user->country_id = $request->country_id;
                $user->save();
                $user_personal = User::where('id',$id)->first();
                $user_personal_details = [
                    'address_1' => $user_personal->address_1,
                    'address_2' => $user_personal->address_2,
                    'zip_code' => $user_personal->zip_code,
                    'city' => $user_personal->city,
                    'country_id' => $user_personal->country_id,
                    'country' => $user_personal->country->name,
                ];
                return response()->json([
                    'personalAddress' => $user_personal_details,
                    'status' => 'Success',
                    'message' => 'Personal Address is added to the Customer',
                ],200);
            } else {
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
     * Method allow to delete the personal address of the customer
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function deletePersonal($id):JsonResponse
    {
        try {
            $user = User::where('id',$id)->first();
            if ($user){
                $user->address_1 = null;
                $user->address_2 = null;
                $user->zip_code = null;
                $user->city = null;
                $user->country_id = null;
                $user->save();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Personal Address is removed successfully',
                ],200);
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
     * Method allow to add the personal address of the customer
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function storeCompany(Request $request, $id):JsonResponse
    {
        try {
            $user = User::where('id',$id)->where('sys_admin', 0)->where('sys_customer', 1)->first();
            if ($user) {
                $request->validate([
                    'partner_code' => 'required|string',
                ]);
                $company = Partners::where('code', $request->partner_code)->first();
                if ($company) {
                    $user->partners_id = $company->id;
                    $user->save();
                    $company_detail = $user->company()->first();
                    if ($company_detail->countries_id != null) {
                        $country_name = $company_detail->country->name;
                    } else {
                        $country_name = null;
                    }
                    $company_address = [
                        'code' => $company_detail->code,
                        'name' => $company_detail->name,
                        'email' => $company_detail->billing_send_email,
                        'VAT' => $company_detail->VAT,
                        'tax_number' => $company_detail->tax_number,
                        'address_1' => $company_detail->street,
                        'address_2' => $company_detail->street_extra,
                        'zip_code' => $company_detail->zip_code,
                        'city' => $company_detail->city,
                        'country_id' => $company_detail->countries_id,
                        'country' => $country_name,
                    ];
                    return response()->json([
                        'companyAddress' => $company_address,
                        'status' => 'Success',
                        'message' => 'Company Address for existing is added successfully',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Please enter the valid company code',
                    ], 422);
                }
            } else {
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
     * Method allow to update the new email/username.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function changeEmail(Request $request, $id):JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|unique:users',
            ]);
            $user = User::where('id',$id)->first();

            $user->email = $request->email;
            $user->save();
            // After changing if the User is same then User need to logout.
            (new UserController())->revokeAndDeleteTokens($user);
            if(!empty($user)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Email updated successfully!',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'There is no relevant information for selected query',
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
     * Method allow to update the new password.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function changePassword(Request $request, $id):JsonResponse
    {
        try {
            $user = User::where('id',$id)->first();
            if ($user) {
                $request->validate([
                    'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
                ]);
                $password = [
                    'password' => Hash::make($request->password)
                ];
                User::where('id', $id)->update($password);
                (new UserController())->revokeAndDeleteTokens($user);
                return response()->json([
                    'status' => 'success',
                    'message' => 'The password has changed Successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'There is no relevant information for selected query',
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

    /**
     * Method allow to update the Company Address of the user
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateCompany(Request $request, $id):JsonResponse
    {
        try {
            $user = User::where('id',$id)->first();
            if ($user) {
                $request->validate([
                    'company_code' => 'required|string',
                ]);
                $company_detail = Partners::where('company_code',$request->company_code)->first();
                $comp = Partners::find($company_detail->id);
                if ($company_detail){
                    if ($company_detail->created_by == $user->id) {
                        $request->validate([
                            'name' => 'required|string|max:255',
                            'email' => ['required', Rule::unique('companies', 'email')->ignore($comp->id)],
                            'address_1' => 'required|string',
                            'zip_code' => 'required|integer',
                            'city' => 'required|string',
                            'country_id' => 'required|integer'
                        ]);
                        $company = DB::table('companies')
                            ->where('company_code','=',$request->company_code)->update([
                            'name' => $request->name,
                            'email' => $request->email,
                            'VAT' => $request->VAT,
                            'tax_number' => $request->tax_number,
                            'address_1' => $request->address_1,
                            'address_2' => $request->address_2,
                            'zip_code' => $request->zip_code,
                            'city' => $request->city,
                            'country_id' => $request->country_id,
                        ]);
                        $updated_company = Partners::where('company_code',$request->company_code)->first();
                        $company_address = [
                            'code' => $updated_company->company_code,
                            'name' => $updated_company->name,
                            'email' => $updated_company->email,
                            'VAT' => $updated_company->VAT,
                            'tax_number' => $updated_company->tax_number,
                            'address_1' => $updated_company->address_1,
                            'address_2' => $updated_company->address_2,
                            'zip_code' => $updated_company->zip_code,
                            'city' => $updated_company->city,
                            'country_id' => $updated_company->country_id,
                            'country' => $updated_company->country->name,
                        ];
                        return response()->json([
                            'companyDetails' => $company_address,
                            'status' => 'Success',
                            'message' => 'Company is updated successfully. You can invite your team members',
                        ], 200);
                    } else{
                        return response()->json([
                            'status' => 'Error',
                            'message' => 'Cannot update the company, please contact administrator',
                        ], 401);
                    }
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Please enter the valid company code',
                    ], 210);
                }
            } else {
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
     * Method allow to delete the Company Address of the user
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteCompany($id):JsonResponse
    {
        try {
            $user = User::where('id',$id)->first();
            if ($user) {
                $user->partners_id = null;
                $user->save();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Company for the user is removed successfully.',
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
     * Method allow to add the Company details to the Contact from Web App
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function addAndAttachCompanyToContact(Request $request, $id):JsonResponse
    {
        try {
            if (User::where('id', $id)->exists()) {
                $request->validate([
                    'type' => 'required|in:new,existing',
                ]);
                if ( $request->type === 'new') {
                    $request->validate([
                        'name' => 'required|string|max:255|unique:partners',
                        'address_1' => 'required',
                        'city' => 'required',
                        'zip_code' => 'required',
                        'country_id' => 'required',
                    ]);
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
                        'street' => $request->address_1,
                        'street_extra' => $request->address_2 ?? null,
                        'zip_code' => $request->zip_code,
                        'city' => $request->city,
                        'country_id' => $request->country_id,
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                } else {
                    $request->validate([
                        'code' => 'required',
                    ]);
                    $partner = Partners::where('code', $request->code)->first();
                    if (!empty($partner)) {
                        $partner_id = $partner->id;
                    } else {
                        return response()->json([
                            'status' => 'Error',
                            'message' => 'Please enter the valid company code',
                        ], 422);
                    }
                }
                $user = User::where('id', $id)->first();
                $user->partners_id = $partner_id;
                $user->save();
                $result = $this->getContactDetails($user);
                return response()->json([
                    'companyAddress' => $result['companyAddress'],
                    'status' => 'Success',
                    'message' => 'Company Address is added successfully'
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
     * Method allow to update the Company details to the Contact from Web App
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function updateAttachedCompany(Request $request, $id):JsonResponse
    {
        try {
            if (User::where('id', $id)->where('partners_id', '!=' , null)->exists()) {
                $users = User::where('id', $id)->first();
                $partners = Partners::where('id', $users->partners_id)->first();
                $request->validate([
                    'name' => ['required', 'string', Rule::unique('partners', 'name')->ignore($partners->id)],
                    'address_1' => 'required',
                    'city' => 'required',
                    'zip_code' => 'required',
                    'country_id' => 'required',
                ]);
                $partners->name = $request->name;
                $partners->street =  $request->address_1;
                $partners->street_extra =  $request->address_2;
                $partners->city =  $request->city;
                $partners->zip_code =  $request->zip_code;
                $partners->country_id =  $request->country_id;
                $partners->save();
                $result = $this->getContactDetails($users);
                return response()->json([
                    'companyAddress' => $result['companyAddress'],
                    'status' => 'Success',
                    'message' => 'Company Address is updated successfully'
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
     * Method allow to remove the company details for User
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function removeAttachedCompany($id):JsonResponse
    {
        try {
            if (User::where('id', $id)->where('partners_id', '!=' , null)->exists()) {
                $users = User::where('id', $id)->first();
                $partners = Partners::where('id', $users->partners_id)->first();
                $users->partners_id = null;
                $users->save();

                $partners_users = $partners->users;
                if ($partners_users->isEmpty()) {
                    Partners::where('id', $partners->id)->delete();
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Company Address is removed successfully'
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
     * Method allow to update the State a blocked or not of Contact
     * @param $id
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function updateState(Request $request, $id):JsonResponse
    {
        try {
            if (User::where('id',$id)->where('sys_admin', 0)->where('sys_customer', 1)->exists()) {
                $user = User::where('id', $id)->first();
                $user->is_blocked = $request->state;
                $user->save();
                $result = [
                    'id' => $user->id,
                    'is_blocked' => (int)$user->is_blocked,
                ];
                return response()->json([
                    'contactDetails' => $result,
                    'status' => 'Success',
                    'message' => 'The contact state is updated successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'There is no relevant information for selected query',
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
     * Method allow to update the Profile Photo of contact
     * @param $id
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function updateProfilePicture(Request $request, $id):JsonResponse
    {
        try {
            if (User::where('id',$id)->where('sys_admin', 0)->where('sys_customer', 1)->exists()) {
                $user = User::where('id', $id)->first();

                if ($request->profile_photo_id == null) {
                    $profile_photo_id = $user->profile_photo_id;
                } else {
                    $profile_photo_id = $request->profile_photo_id;
                }
                $user->profile_photo_id = $profile_photo_id;
                $user->save();
                if ($user->profile_photo_id != null) {
                    $profile_photo_url = $user->profilePhoto->file_path;
                } else {
                    $profile_photo_url = null;
                }

                $result = [
                    'id' => $id,
                    'profile_photo_url' => $profile_photo_url,
                ];
                return response()->json([
                    'contactDetails' => $result,
                    'status' => 'Success',
                    'message' => 'The contact profile photo update successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'There is no relevant information for selected query',
                ], 210);
            }
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to delete contacts profile photo
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteContactPhoto($id): JsonResponse
    {
        try {
            if (User::where('id',$id)->exists()) {
                $user = User::where('id', $id)->first();

                if ($user->profile_photo_url != null) {
                    $delete_media = $this->destroyMediaFile($user->profile_photo_url);

                    $user->profile_photo_url = null;
                    $user->save();

                    return response()->json([
                        'status' => 'Success',
                        'message' => 'File is deleted successfully',
                    ],200);
                } else {
                    return response()->json([
                        'status' => 'Success',
                        'message' => 'There is no picture is available to delete.',
                    ],422);
                }
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
    } //End function

    /**
     * Method allow to DeActivate the Account for the App.
     * @return JsonResponse
     * @throws Exception
     */
    public function deactivateContact():JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            if (!empty($user)) {
                if ($user->sys_admin == 0 && $user->sys_customer == 1) {
                    $tokens =  $user->tokens->pluck('id');
                    if (!empty($tokens)) {
                        Token::whereIn('id', $tokens)
                            ->update(['revoked' => true]);

                        RefreshToken::whereIn('access_token_id', $tokens)->update(['revoked' => true]);
                        $accessToken = Auth::guard('api')->user()->tokens->each(function ($token, $key) {
                            $token->delete();
                        });
                    }
                    // Delete the Contact
                    User::where('id',$user->id)->delete();

                    return response()->json([
                        'status' => 'Success',
                        'message' => 'Account has been deactivated Successfully',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Cannot Deactivate the Account system Admin through App'
                    ], 422);
                }
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

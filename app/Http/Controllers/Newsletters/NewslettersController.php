<?php

namespace App\Http\Controllers\Newsletters;

use App\Http\Controllers\Controller;
use App\Jobs\NewslettersMails;
use App\Models\EmailsSettings;
use App\Models\NewslettersInterests;
use App\Models\NewslettersUsers;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;
use Illuminate\Support\Facades\Hash;
class NewslettersController extends Controller
{
    /**
     * Method allow to display list of all Newsletters Interests or single Newsletters Interests.
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $newslettersInterests = NewslettersInterests::orderBy('display_order', 'ASC')
                ->get();
            return response()->json([
                'data' => $newslettersInterests,
                'message' => 'Success',
            ], 200);

        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End function

    /**
     * Method allow to store or create the new Newsletters Interests.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:newsletters_interests'
            ]);

            $ordernumber = NewslettersInterests::max('display_order');

            $interests = DB::table('newsletters_interests')->insert([
                'name' => $request->name,
                'display_order' =>  $ordernumber + 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Newsletter Interests is added successfully',
            ],200);

        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End function

    /**
     * Method allow to store or create the new Newsletters Interests.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function usersStore(Request $request, $id):JsonResponse
    {
        try {
            $interests_array = [];
            $check_newsletters_user = [];

            $request->validate([
                'email' => 'required|string',
            ]);

            $check_users = User::where('email', $request->email)->first();
            $hashed_email = base64_encode($request->email);
            $condition = 'newsletters_activation_link';
            if(!empty($check_users)) {
                $newsletters_user = NewslettersUsers::where('contacts_id', $check_users->id)->first();
                if(empty($newsletters_user)) {
                    $newsletters_user = NewslettersUsers::create([
                        'contacts_id' => $check_users->id,
                        'hashed_user_email' => $hashed_email,
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                }
            } else {
                $newsletters_user = NewslettersUsers::where('email', $request->email)->first();
                if(empty($newsletters_user)) {
                    $newsletters_user = NewslettersUsers::create([
                        'salutations_id' => $request->salutations_id,
                        'firstname' => $request->firstname,
                        'lastname' => $request->lastname,
                        'email' => $request->email,
                        'hashed_user_email' => $hashed_email,
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                }
            }
            $newsletters_interests = $newsletters_user->newslettersInterests;
            if( count($newsletters_interests) > 0 ) {
                foreach ($newsletters_interests as $interests) {
                    $interests_array1[] = $interests->id;
                }
            }

            if(count($request->newsletters_interests) > 0 ) {
                foreach ($request->newsletters_interests as $newsletters_interest) {
                    $interests_array2[] = $newsletters_interest;
                }
            }

            // Combine the arrays
            $combinedArray = array_merge($interests_array1, $interests_array2);

            // Remove duplicates while preserving the order
            $uniqueArray = array_values(array_unique($combinedArray));
            $email_settings = EmailsSettings::where('technologies', 'newsletters')->where('name','newsletters_confirmation')->first();
            if(!empty($email_settings) && $newsletters_user->is_activated == 0) {
                if( $email_settings->emails_id != '' && $email_settings->emails_templates_id != '') {
                    $email_id = $email_settings->emails_id;
                    $email_template_id = $email_settings->emails_templates_id;
                        NewslettersMails::dispatch($email_id, $email_template_id, $newsletters_user, $condition, $email_settings->id);
                }
            }
            $newsletters_user->newslettersInterests()->detach();
            if (!empty($uniqueArray)) {
                $newsletters_user->newslettersInterests()->attach($uniqueArray);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'Newsletter Users is added successfully',
            ],200);
        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End function

    /**
     * Method allow to show the particular Newsletters Interests.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            if (NewslettersInterests::where('id',$id)->exists()){
                $newslettersInterests = NewslettersInterests::where('id',$id)->first();

                return response()->json([
                    'data' => $newslettersInterests,
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
                'message' => $exception,
            ], 500);
        }
    } // End function

    /**
     * Method allow to update the name of the particular Newletters Interests.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            $newslettersInterests = NewslettersInterests::where('id',$id)->first();
            $request->validate([
                'name' => ['required','string', Rule::unique('newsletters_interests', 'name')->ignore($newslettersInterests->id)]
            ]);

            if (NewslettersInterests::where('id',$id)->exists()){
                NewslettersInterests::where('id',$id)
                    ->update([
                        'name' => $request->name,
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Newsletters Interests is updated successfully',
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
    } // End function

    /**
     * Method allow to delete the particular Newsletters Interests.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (NewslettersInterests::where('id',$id)->exists()){
                NewslettersInterests::where('id',$id)->delete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Newsletters Interests is deleted successfully',
                ],200);

            }else{
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }
        } catch (\Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End function

    /**
     * Method allow to soft delete the set of Newsletters Interests.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->newsletters_interests_id)){
                foreach ($request->newsletters_interests_id as $interests_id)
                {
                    $interests = NewslettersInterests::findOrFail($interests_id);
                    $interests->delete();
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Newsletters Interests are deleted successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one Newsletters Interests to delete'
                ], 422);
            }

        } catch (\Exception $exception)
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
            foreach ($request->newsletters_interests_sorting as $sorting)
            {
                $index = $sorting['id'];
                $newposition = $sorting['newposition'];
                $displayordernumber = [
                    'display_order' => $newposition
                ];
                NewslettersInterests::where('id',$index)->update($displayordernumber);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'The Newsletters Interests are sorted successfully',
            ],200);
        } catch (\Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to display subscribed users
     * @return JsonResponse
     * @throws Exception
     */
    public function usersIndex(Request $request):JsonResponse
    {
        try {
            if ($request->limit == null) {
                $limit = 10;
            } else {
                $limit = (int)$request->limit;
            }
            $users_interests = [];
            $all_users = [];
            $get_users = new NewslettersUsers();
            if( !empty($request->search_term) ) {
                $get_users = $get_users
                    ->where('firstname', 'like', '%' . $request->search_term . '%')
                    ->orWhere('lastname', 'like', '%' . $request->search_term . '%')
                    ->orWhere('email', 'like', '%' . $request->search_term . '%');
            }
            if( !empty($request->status) ) {
                if( $request->status == 'activated') {
                    $get_users = $get_users
                        ->where('is_activated', 1);
                } else {
                    $get_users = $get_users
                        ->where('is_activated', 0);
                }
            }
            if( !empty($request->interests_id) ) {
                $get_data = $this->interestsMasterData($request->interests_id);
            }
            if(!empty($get_data)) {
                $get_users = $get_users->whereIn('id', $get_data);
            }
            if( $request->type == 'guest') {
                $get_users = $get_users->where('contacts_id', null);
            } else if( $request->type == 'contacts') {
                $get_users = $get_users->where('contacts_id', '!=', '');
            }
            $get_users1 = $get_users->get();

            foreach ($get_users1 as $get_user1) {
                $user_data = array();
                $newsletters_interests = array();
                $user_data['id'] = $get_user1['id'];
                if($get_user1->contacts_id != '') {
                    $user = User::where('id', $get_user1->contacts_id)->first();
                    $user_data = [
                        'id' => $get_user1['id'],
                        'firstname' => $user['firstname'],
                        'lastname' => $user['lastname'],
                        'email' => $user['email'],
                        'user_type' => 'contacts',
                    ];
                } else {
                    $user_data = [
                        'id' => $get_user1['id'],
                        'firstname' => $get_user1['firstname'],
                        'lastname' => $get_user1['lastname'],
                        'email' => $get_user1['email'],
                        'user_type' => 'guest',
                    ];
                }

                $total_newsletters_array = [];
                if(count($get_user1->newslettersInterests) > 0) {
                    foreach ($get_user1->newslettersInterests as $newslettersInterest) {
                        $newsletters_interests = [
                            'id' => $newslettersInterest->id,
                            'name' => $newslettersInterest->name,
                            'display_order' => $newslettersInterest->display_order,
                        ];
                        $total_newsletters_array[] = $newsletters_interests;
                    }

                }

                $newsletters_interests = [];
                $user_data['newsletters_interests'] = $total_newsletters_array;
                $all_users[] = $user_data;
            }
            $pagination_details = $this->getPaginationDetails($get_users, $limit, count($get_users->get()));
            return response()->json([
                'status' => 'Success',
                'users_pagination' => $pagination_details,
                'users' => $all_users ? $all_users : '',
            ],200);

        } catch (\Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ], 500);
        }
    } // End function

    /**
     * Method allow to get list of interests
     * @return JsonResponse
     * @param $data
     * @throws Exception
     */
    public function interestsMasterData($data):array
    {
        $post_ids = array();
        foreach ($data as $interests_ids) {
            $interests = NewslettersInterests::where('id', $interests_ids)->first();
            if (!empty($interests)) {
                $users_ids[] = $interests->newslettersUsersIntersts->pluck('id')->toArray();
            }
        }
        $users_condition_group = call_user_func_array('array_merge',$users_ids);
        $users_condition_group = array_unique($users_condition_group);
        return $users_condition_group;
    } // End Function

}

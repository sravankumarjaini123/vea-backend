<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\UserController;
use App\Jobs\SendDoubleOptInMails;
use App\Models\EmailsSettings;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;
use Laravel\Jetstream\Jetstream;
use Nette\Schema\ValidationException;
use Illuminate\Validation\Rules;
use Exception;
class AuthenticateController extends Controller
{
    public function __construct(Request $request = null)
    {
        //code
    } // End Function

    /**
     * Method to allow the New sys_user to Register
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function register(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'salutation_id' => 'required|integer',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
                'condition' => 'nullable|in:app_user_signup',
            ]);

            if ($request->password != null) {
                $password = $request->password;
            } else {
                $password = 'Welcome1234@$';
            }
            $username = $this->generateRandomUserName();

            $customer_id = User::insertGetId([
                'salutations_id' => $request->salutation_id,
                'titles_id' => $request->title_id ?? null ,
                'firstname' => $request->first_name,
                'lastname' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($password),
                'sys_admin' => false,
                'sys_customer' => true,
                'username' => $username,
                'verification_status' => null,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            if ($customer_id != null) {
                $customer = User::where('id', $customer_id)->first();
                $customer_array = (new ContactController())->getContactDetails($customer);

                return response()->json([
                    'personalInformation' => $customer_array['personalInformation'],
                    'personalAddress' => $customer_array['personalAddress'],
                    'companyAddress' => $customer_array['companyAddress'],
                    'userLogins' => $customer_array['userLogins'],
                    'message' => 'Registration is successful, Please check your mail for activating your account',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select the Email template for Registration confirmation',
                ], 422);
            }
        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getLine(),
            ], 500);
        }
    } // End Function

    /**
     * Method to Destroy authentication session
     * @return JsonResponse
     * @throws UnauthorizedException
     */
    public function destroy():JsonResponse
    {
        try {
            $customer = Auth::user();
            $accessToken = Auth::user()->tokens()
                ->where('user_id', $customer->id)
                ->where('name','=','Frontend');

            if($accessToken){
                $accessToken->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Thank you and we are looking forward to see you again.',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please login first.',
                ], 210);
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
     * Method to send the Double opt in confirmation mail with all validations
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function doubleOptInConfirmationMail($id):JsonResponse
    {
        try {
            $customer = User::where('id', $id)->first();
            if (!empty($customer)) {
                if (!$customer->double_opt_in) {
                    $emails_settings = EmailsSettings::where('technologies', 'system')->where('name', 'user_double_opt_in')->first();

                    if ($emails_settings->emails_id != null && $emails_settings->emails_templates_id != null) {
                        $code_id = (new UserController())->checkAndGenerateCode($customer->email, 'users_doubleoptins');
                        if (!empty($code_id)) {
                            SendDoubleOptInMails::dispatch($emails_settings->emails_id, $emails_settings->emails_templates_id, $customer->id, $code_id, $emails_settings->id);
                        }
                        return response()->json([
                            'status' => 'Success',
                            'message' => 'The Double Opt In confirmation mail has been sent successfully',
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 'Error',
                            'message' => 'Email settings are not been set, please contact administrator!!!',
                        ],422);
                    }
                } else {
                    return response()->json([
                        'status' => 'No Content',
                        'message' => 'Account is already activated',
                    ], 210);
                }
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for the query',
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
}


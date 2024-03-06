<?php

namespace App\Http\Controllers\ContactForms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Partners\PartnersController;
use App\Http\Controllers\Users\UserController;
use App\Models\Partners;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ContactFormController extends Controller
{
    public function registerContactFromContactForm(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required',
                'company_name' => 'required',
            ]);
            // Check and Create / Retrieve the Details of the Partners
            if (!User::where('email', $request->email)->exists()) {
                if (!Partners::where('name', $request->company_name)->exists()) {
                    $partner_request = new Request();
                    $partner_request->setMethod('post');
                    $partner_request->request->add([
                        'name' => $request->company_name,
                    ]);
                    $partner_details = (new PartnersController())->store($partner_request);
                    if ($partner_details->getStatusCode() === 200) {
                        $partner_id = $partner_details->getData()->partnerDetails->id;
                        $update_partner_request = new Request();
                        $update_partner_request->setMethod('post');
                        $update_partner_request->request->add([
                            'name' => $partner_details->getData()->partnerDetails->name,
                            'address_1' => $request->address,
                            'address_2' => $request->address_extra,
                            'zip_code' => (int)$request->zip_code,
                            'city' => $request->city,
                            'countries_id' => $request->country_id ?? 84,
                            'email' => $request->company_email ?? null,
                            'telephone' => $request->telephone ?? null,
                            'website' => $request->website ?? null,
                        ]);
                        (new PartnersController())->updateGeneral($update_partner_request, $partner_id);

                    } else {
                        return response()->json([
                            'status' => 'Error',
                            'message' => 'Issue in creating the Partner, Please check at Partner level'
                        ], 421);
                    }
                } else {
                    $partner_id = Partners::where('name', $request->company_name)->first()->id;
                }

                // Check and Create / Update the User with the Respective Partner Details
                $password = Str::random(20);
                $username = $this->generateRandomUserName();
                $customer_id = User::insertGetId([
                    'salutations_id' => $request->salutations_id ?? null,
                    'titles_id' => $request->title_id ?? null,
                    'firstname' => $request->first_name,
                    'lastname' => $request->last_name,
                    'email' => $request->email,
                    'password' => Hash::make($password),
                    'sys_admin' => false,
                    'sys_customer' => true,
                    'username' => $username,
                    'verification_status' => null,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'partners_id' => $partner_id,
                ]);
                $customer_type = 'new';

                // Update the Resources or Licenses of the Partner created or existing
                $license_request = new Request();
                $license_request->setMethod('post');
                $license_request->request->add([
                    'resources_id' => [19, 21, 17, 18, 20, 2]
                ]);
                (new PartnersController())->updateResources($license_request, $partner_id);

                (new UserController())->sendAccountInfo($customer_id);

                return response()->json([
                    'status' => 'Success',
                    'message' => 'You are successfully connected with Us, Your login credentials are successfully sent to the respective Email'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The User already exisits in the System So nothing to do.'
                ], 200);
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

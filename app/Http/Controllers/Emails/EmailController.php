<?php

namespace App\Http\Controllers\Emails;

use App\Http\Controllers\Controller;
use App\Models\Emails;
use App\Models\EmailsTemplates;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;
use Swift_SmtpTransport;
use Swift_Mailer;

class EmailController extends Controller
{
    /**
     * Method allow to display list of all Email accounts of the Mailing
     * @return JsonResponse
     * @throws Exception
     */
    public function indexEmails():JsonResponse
    {
        try {
            $query = Emails::all();
            return response()->json([
                'emailsDetails' => $query,
                'message' => 'Success',
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to create the new Email account for the system
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function storeEmails(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'username' => 'required|string',
                'password' => 'required|string',
                'encryption' => 'required|string',
                'port' => 'required|string',
                'host' => 'required|string',
                'senders_address' => 'required|string',
                'senders_name' => 'required|string',
            ]);

            $email_id = DB::table('emails')->insertGetId([
                'name' => $request->name,
                'username' => $request->username,
                'password' => $request->password,
                'encryption' => $request->encryption,
                'port' => $request->port,
                'host' => $request->host,
                'senders_address' => $request->senders_address,
                'senders_name' => $request->senders_name,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            if (!empty($email_id)){
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Email account is created successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'Database-Error',
                    'message' => 'There is some error creating the Email account.',
                ],422);
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    public function testSMTPConnection(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'username' => 'required|string',
                'password' => 'required|string',
                'encryption' => 'required|string|in:tls,ssl',
                'port' => 'required|string',
                'host' => 'required|string',
                'senders_address' => 'required|string',
                'senders_name' => 'required|string',
            ]);

            $transport_status = $this->transportStatus($request);
            if ($transport_status->getStatusCode() === 200) {
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Account is verified successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => $transport_status->getData()->message,
                ], $transport_status->getStatusCode());
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    public function transportStatus($request):JsonResponse
    {
        try {
            $transport = new Swift_SmtpTransport($request->host, $request->port, $request->encryption);
            $transport->setUsername($request->username);
            $transport->setPassword($request->password);
            $mailer = new Swift_Mailer($transport);
            $mailer->getTransport()->start();
            return response()->json([
                'status' => 'Success',
                'message' => 'SMTP is verified successfully',
            ],200);
        }
        catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to show the single Email Account
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function showEmails($id):JsonResponse
    {
        try {
            if (Emails::where('id',$id)->exists()){
                $query = Emails::where('id',$id)->first();
                return response()->json([
                    'emailsDetails' => $query,
                    'message' => 'Success',
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

    public function updateEmails(Request $request, $id):JsonResponse
    {
        try {
            if (Emails::where('id', $id)->exists()) {
                $email = Emails::where('id', $id)->first();
                $request->validate([
                    'name' => ['required', 'string', Rule::unique('emails', 'name')->ignore($email->id)],
                    'username' => 'required|string',
                    'encryption' => 'required|string',
                    'port' => 'required|string',
                    'host' => 'required|string',
                    'senders_address' => 'required|string',
                    'senders_name' => 'required|string',
                ]);
                if (!empty($request->password)){
                    $password = $request->password;
                } else {
                    $password = $email->password;
                }
                $email->name = $request->name;
                $email->username = $request->username;
                $email->password = $password;
                $email->encryption = $request->encryption;
                $email->port = $request->port;
                $email->host = $request->host;
                $email->senders_address = $request->senders_address;
                $email->senders_name = $request->senders_name;
                $email->save();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Email is updated successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }

        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to soft delete the particular Email.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (Emails::where('id',$id)->exists()){
                Emails::where('id',$id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Email is deleted successfully',
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
     * Method allow to soft delete the set of Emails.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->emails_id)) {
                foreach ($request->emails_id as $email_id) {
                    $template = Emails::findOrFail($email_id);
                    $template->delete();
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Emails are deleted successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one Email template to delete'
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

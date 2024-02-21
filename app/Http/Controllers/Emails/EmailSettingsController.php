<?php

namespace App\Http\Controllers\Emails;

use App\Http\Controllers\Controller;
use App\Models\EmailsSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;
use Exception;

class EmailSettingsController extends Controller
{
    /**
     * Method allow to display list of all Email Settings
     * @return JsonResponse
     * @throws Exception
     */
    public function indexSettings():JsonResponse
    {
        try {
            $system_settings = EmailsSettings::where('technologies', 'system')->get();
            $app_settings = EmailsSettings::where('technologies','app')->get();
            $news_letters_settings = EmailsSettings::where('technologies','newsletters')->get();
            $query = array_merge(['system' => $system_settings], ['app' => $app_settings], ['newsletters' => $news_letters_settings]);
            return response()->json([
                'emailsSettingsDetails' => $query,
                'message' => 'Success',
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to update the settings of the Emails for system and App
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateSettings(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'technology' => 'required|in:system,newsletters,app',
                'name' => 'required',
            ]);

            $email_settings = EmailsSettings::where('technologies', $request->technology)
                ->where('name', $request->name)->first();
            if (!empty($email_settings)){
                $email_settings->emails_id = $request->email_id;
                $email_settings->emails_templates_id = $request->email_template_id;
                $email_settings->subject = $request->subject;
                $email_settings->save();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Email Settings is updated successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'There is some issue in selecting the values, Please contact Administrator',
                ],422);
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function
}

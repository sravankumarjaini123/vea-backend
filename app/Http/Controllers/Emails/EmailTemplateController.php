<?php

namespace App\Http\Controllers\Emails;

use App\Http\Controllers\Controller;
use App\Models\EmailsTemplates;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;

class EmailTemplateController extends Controller
{

    /**
     * Method allow to display list of all Email templates of the system
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $query = EmailsTemplates::all();
            return response()->json([
                'emailsTemplatesDetails' => $query,
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
     * Method allow to display list of all Email templates Samples
     * @return JsonResponse
     * @throws Exception
     */
    public function emailTemplateSampleIndex():JsonResponse
    {
        try {
            $query = DB::table('emails_templates_samples')->get();
            return response()->json([
                'emailsTemplatesSampleDetails' => $query,
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
     * Method allow to store the Email templates from existing templates.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function storeSampleEmailTemplates(Request $request):JsonResponse
    {
        try {
                $emailSettings = '';
                $request->validate([
                    'name' => ['required','string', Rule::unique('emails_templates', 'name')],
                ]);
                $sample_template = DB::table('emails_templates_samples')->where('id', $request->existing_template_id)->first();
                if(!empty($sample_template)) {
                    $emailSettings = EmailsTemplates::create([
                        'name' => $request->name,
                        'type' => 'N/A',
                        'description' => $sample_template->description,
                        'previous_state' => $sample_template->previous_state,
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                    return response()->json([
                        'EmailsTemplates' => $emailSettings,
                        'status' => 'Success',
                        'message' => 'The Email template is created successfully',
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
     * Method allow to create the new Email Template for the system
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:emails_templates',
            ]);
            $email_template = DB::table('emails_templates')->insert([
                'name' => $request->name,
                'type' => 'N/A',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            return response()->json([
                'status' => 'Success',
                'message' => 'Email Template is added successfully',
            ],200);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to show the single email template
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            if (EmailsTemplates::where('id',$id)->exists()){
                $query = EmailsTemplates::where('id',$id)->first();
                return response()->json([
                    'emailsTemplatesDetails' => $query,
                    'message' => 'Success',
                ],200);
            }else{
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
     * Method allow to update the Email template.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            if (EmailsTemplates::where('id', $id)->exists()){
                $email_template = EmailsTemplates::where('id', $id)->first();
                $request->validate([
                    'name' => ['required','string', Rule::unique('emails_templates', 'name')->ignore($email_template->id)],
                ]);
                EmailsTemplates::where('id', $id)->update([
                    'name' => $request->name,
                    'description' => $request->description,
                    'previous_state' => $request->state,
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Email template is updated successfully',
                ],200);
            } else{
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
     * Method allow to soft delete the particular Email Template.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (EmailsTemplates::where('id',$id)->exists()){
                EmailsTemplates::where('id',$id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Email Template is deleted successfully',
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
     * Method allow to soft delete the set of groups.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->emails_templates_id)) {
                foreach ($request->emails_templates_id as $email_template_id) {
                    $template = EmailsTemplates::findOrFail($email_template_id);
                    $template->delete();
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Email templates are deleted successfully',
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

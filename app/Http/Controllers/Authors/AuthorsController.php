<?php

namespace App\Http\Controllers\Authors;

use App\Http\Controllers\Controller;
use App\Models\Authors;
use App\Models\FoldersFiles;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;
use Exception;

class AuthorsController extends Controller
{
    /**
     * Method allow to display list of all authors
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $query = Authors::all()->sortBy('display_order');
            $result_authors = $this->getAuthorDetails($query);

            return response()->json([
                'data' => $result_authors,
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

    public function getAuthorDetails($authors):array
    {
        $result_array = array();
        if (!empty($authors)){
            foreach ($authors as $author){
                if ($author->titles_id != null) {
                    $title = $author->title->title;
                } else {
                    $title = null;
                }
                if ($author->profile_photo_id != null) {
                    $file = FoldersFiles::where('id', $author->profile_photo_id)->first();
                    $profile_photo_url = $file->file_path;
                    $profile_photo_resolutions = $this->getFilesResolutionDetails($file->id);
                } else {
                    $profile_photo_url = null;
                    $profile_photo_resolutions = array();
                }
                $result_array[] = [
                    'id' => $author->id,
                    'salutation_id' => $author->salutations_id,
                    'salutation' => $author->salutation->salutation,
                    'title_id' => $author->titles_id,
                    'title' => $title,
                    'firstname' => $author->firstname,
                    'lastname' => $author->lastname,
                    'profile_photo_id' => $author->profile_photo_id,
                    'profile_photo_url' => $profile_photo_url,
                    'profile_photo_resolutions' => $profile_photo_resolutions,
                    'display_order' => $author->display_order,
                    'description' => $author->description,
                    'display_name' => $author->display_name,
                    'active' => $author->active,
                    'created_at' => $author->created_at,
                    'updated_at' => $author->updated_at,
                ];
            }
        }
        return $result_array;
    } // End Function

    /**
     * Method allow to display list of all active authors
     * @return JsonResponse
     * @throws Exception
     */
    public function getActiveAuthors():JsonResponse
    {
        try {
            $query = Authors::all()->where('active', '=', 1);
            $result_authors = $this->getAuthorDetails($query);

            return response()->json([
                'data' => $result_authors,
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
     * Method allow to store or create the new author.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'salutations_id' => 'required|int',
                'firstname' => 'required|string',
                'lastname' => 'required|string',
                'display_name' => 'nullable|string',
            ]);
            $order_number = Authors::max('display_order');
            $author = DB::table('authors')->insert([
                'active' => 1,
                'salutations_id' => $request->salutations_id,
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'display_name' => $request->display_name ?? null,
                'display_order' => $order_number + 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s')
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Author is added successfully',
            ],200);

        } catch (ValidationException $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to show the particular Author.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse {

        try {
            if (Authors::where('id',$id)->exists()){
                $query = Authors::where('id',$id)->get();
                $result_details = $this->getAuthorDetails($query);
                $result_array = array();
                foreach ($result_details as $result_detail){
                    $result_array = $result_detail;
                }
                /*$author = Authors::where('id', $id)->first();
                $author_posts = $author->posts()->where('status_id', 2)->orderBy('published_at', 'DESC')->get();
                $result_array['posts'] = (new PostController())->getRelatedPostsDetails($author_posts) ?? array();*/

                return response()->json([
                    'data' => $result_array,
                    'message' => 'Success',
                ],200);

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
     * Method allow to update the status of the particular author.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateStatus(Request $request, $id):JsonResponse {

        try {

            $request->validate([
                'active' => 'required|int',
            ]);

            if (Authors::where('id',$id)->exists()) {
                $authors = Authors::where('id', $id)->first();
                $authors->active = $request->active;
                $authors->save();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Author status is updated successfully',
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
     * Method allow to update the status of the particular author.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateGeneral(Request $request, $id):JsonResponse {

        try {

            $request->validate([
                'salutations_id' => 'required|int',
                'firstname' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'display_name' => 'nullable|string|max:255',
            ]);

            if (Authors::where('id',$id)->exists()) {
                if ($request->titles_id != null) {
                    $title = $request->titles_id;
                } else {
                    $title = null;
                }
                $authors = Authors::where('id', $id)->first();
                $authors->salutations_id = $request->salutations_id;
                $authors->titles_id = $title;
                $authors->firstname = $request->firstname;
                $authors->lastname = $request->lastname;
                $authors->display_name = $request->display_name ?? null;
                $authors->save();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Author general data is updated successfully',
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
     * Method allow to update the status of the particular author.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateDescription(Request $request, $id):JsonResponse {

        try {

            $request->validate([
                'description' => 'required|string',
            ]);
            if (Authors::where('id',$id)->exists()) {

                $authors = Authors::where('id', $id)->first();
                $authors->description = $request->description;
                $authors->save();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Author description is updated successfully',
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

    public function updatePicture(Request $request, $id):JsonResponse {

        try {

            if (Authors::where('id',$id)->exists()) {
                if ($request->profile_photo_id != null) {
                    $profile_photo_id = $request->profile_photo_id;
                } else {
                    $profile_photo_id = null;
                }
                $authors = Authors::where('id', $id)->first();
                $authors->profile_photo_id = $profile_photo_id;
                $authors->save();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Author profile photo is updated successfully',
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
     * Method allow to delete the particular Author.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse {

        try {
            if (Authors::where('id',$id)->exists()){
                Authors::where('id',$id)->delete();
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Author is deleted successfully',
                ],200);
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
     * Method allow to delete the set of Authors.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse {

        try {

            if (!empty($request->authors_id)){
                foreach ($request->authors_id as $author_id)
                {
                    $author = Authors::findOrFail($author_id);
                    $author->delete();
                }
                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Authors are deleted successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one Author to delete'
                ], 422);
            }
        } catch (Exception $exception) {
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
            foreach ($request->authors_sorting as $sorting) {
                $index = $sorting['id'];
                $new_position = $sorting['newposition'];
                $display_order_number = [
                    'display_order' => $new_position
                ];
                Authors::where('id',$index)->update($display_order_number);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'The Authors are sorted successfully',
            ],200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

} // End class

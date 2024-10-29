<?php

namespace App\Http\Controllers\Videos;

use App\Http\Controllers\Controller;
use App\Models\ExternalVideos;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;
use Exception;

class ExternalVideosController extends Controller
{

    /**
     * Method allow to display list of all categories or single category.
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request):JsonResponse
    {
        try {
            $videos = DB::table('external_videos');
            if ($request->is_active != null) {
                $videos = $videos->where('is_active', '=', $request->is_active)->get();
            } else {
                $videos = $videos->get();
            }
            $query = $this->getExternalVideoDetails($videos);
            return response()->json([
                'data' => $query,
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

    public function getExternalVideoDetails($videos):array
    {
        $result_array = array();
        if (!empty($videos)) {
            foreach ($videos as $video) {
                $result_array[] = [
                    'id' => $video->id,
                    'title' => $video->title,
                    'url' => $video->url,
                    'is_active' => $video->is_active,
                    'date_added' => $video->date_added,
                    'created_at' => $video->created_at,
                    'source' => $video->source,
                ];
            }
        }
        return $result_array;
    } // End Function

    /**
     * Method allow to store or create the new Category.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string',
                'url' => 'required',
                'date_added' => 'required|date_format:Y-m-d',
                'is_active' => 'required|boolean'
            ]);

            ExternalVideos::insert([
                'title' => $request->title,
                'url' => $request->url,
                'source' => $request->source ?? null,
                'date_added' => $request->date_added,
                'is_active' => $request->is_active ?? 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'External Video is added successfully',
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
     * Method allow to delete the particular Category.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            if (ExternalVideos::where('id',$id)->exists()){
                $video = ExternalVideos::where('id',$id)->first();
                return response()->json([
                    'data' => $video,
                    'message' => 'Success',
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
    } // End Function

    /**
     * Method allow to Update the External Video with basic Information
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            if (ExternalVideos::where('id', $id)->exists()) {
                $request->validate([
                    'title' => 'required|string',
                    'url' => 'required',
                    'date_added' => 'required|date_format:Y-m-d',
                    'is_active' => 'required|boolean'
                ]);

                ExternalVideos::where('id', $id)->update([
                    'title' => $request->title,
                    'url' => $request->url,
                    'source' => $request->source ?? null,
                    'date_added' => $request->date_added,
                    'is_active' => $request->is_active ?? 1,
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                return response()->json([
                    'status' => 'Success',
                    'message' => 'External Video is updated successfully',
                ], 200);
            } else{
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
     * Method allow to soft delete the particular External Video.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (ExternalVideos::where('id',$id)->exists()){
                ExternalVideos::where('id',$id)->delete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The External Video is deleted successfully',
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
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to Force delete the set of Videos.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->videos_id)) {
                foreach ($request->videos_id as $video_id) {
                    $video = ExternalVideos::findOrFail($video_id);
                    $video->delete();
                }

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The External Videos are deleted successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one Category to delete'
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

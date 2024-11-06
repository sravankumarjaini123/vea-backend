<?php

namespace App\Http\Controllers\Videos;

use App\Http\Controllers\Controller;
use App\Models\Archives;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;
use Exception;

class ArchivesController extends Controller
{

    /**
     * Method allow to display list of all categories or single category.
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request):JsonResponse
    {
        try {
            $videos = DB::table('archives');
            if ($request->is_active != null) {
                $videos = $videos->where('is_active', '=', $request->is_active)->orderBy('date_added', 'DESC')->get();
            } else {
                $videos = $videos->orderBy('date_added', 'DESC')->get();
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

    public function getExternalVideoDetails($archives):array
    {
        $result_array = array();
        if (!empty($archives)) {
            foreach ($archives as $archive) {
                $archive_elo = Archives::where('id', $archive->id)->first();
                if ($archive->type === 'file') {
                    if ($archive->file_id != null) {
                        $file_path = $archive_elo->file->file_path;
                    } else {
                        continue;
                    }
                }
                $result_array[] = [
                    'id' => $archive->id,
                    'type' => $archive->type,
                    'title' => $archive->title,
                    'url' => $archive->url,
                    'file_id' => $archive->file_id,
                    'file_path' => $file_path ?? null,
                    'is_active' => $archive->is_active,
                    'date_added' => $archive->date_added,
                    'created_at' => $archive->created_at,
                    'source' => $archive->source,
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
                'type' => 'required|in:video,file',
                'title' => 'required|string',
                'date_added' => 'required|date_format:Y-m-d',
                'is_active' => 'required|boolean'
            ]);
            if ($request->type === 'file') {
                $request->validate([
                    'file_id' => 'required',
                ]);
            } else {
                $request->validate([
                    'url' => 'required',
                ]);
            }

            Archives::insert([
                'title' => $request->title,
                'type' => $request->type,
                'file_id' => $request->file_id,
                'url' => $request->url ?? null,
                'source' => $request->source ?? null,
                'date_added' => $request->date_added,
                'is_active' => $request->is_active ?? 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Archive is added successfully',
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
            if (Archives::where('id',$id)->exists()){
                $video = Archives::where('id',$id)->first();
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
            if (Archives::where('id', $id)->exists()) {
                $request->validate([
                    'type' => 'required|in:file,video',
                    'title' => 'required|string',
                    'date_added' => 'required|date_format:Y-m-d',
                    'is_active' => 'required|boolean'
                ]);

                if ($request->type === 'file') {
                    $request->validate([
                        'file_id' => 'required',
                    ]);
                } else {
                    $request->validate([
                        'url' => 'required',
                    ]);
                }

                Archives::where('id', $id)->update([
                    'title' => $request->title,
                    'type' => $request->type,
                    'file_id' => $request->file_id,
                    'url' => $request->url,
                    'source' => $request->source ?? null,
                    'date_added' => $request->date_added,
                    'is_active' => $request->is_active ?? 1,
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                return response()->json([
                    'status' => 'Success',
                    'message' => 'Archive is updated successfully',
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
            if (Archives::where('id',$id)->exists()){
                Archives::where('id',$id)->delete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Archive is deleted successfully',
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
            if (!empty($request->archives_id)) {
                foreach ($request->archives_id as $archive_id) {
                    $archive = Archives::findOrFail($archive_id);
                    $archive->delete();
                }

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Archives are deleted successfully',
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

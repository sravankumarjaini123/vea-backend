<?php

namespace App\Http\Controllers\Tags;

use App\Http\Controllers\Controller;
use App\Models\Tags;
use App\Models\Wordpress;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;

class TagController extends Controller
{
    /**
     * Method allow to display list of all groups or single group.
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $tags = DB::table('tags')
                ->orderBy('display_order')
                ->get();
            $query = $this->getMasterDataDetailsOverview($tags);
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

    /**
     * Method allow to store or create the new group.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:tags'
            ]);

            $order_number = Tags::max('display_order');

            $tag = DB::table('tags')->insert([
                'name' => $request->name,
                'display_order' =>  $order_number + 1,
                'seo_title' => $request->seo_title,
                'seo_description' => $request->seo_description,
                'seo_picture_id' => $request->seo_picture_id,
                'is_visibility' => $request->is_visible ?? 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Tag is added successfully',
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
     * Method allow to delete the particular group.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id):JsonResponse
    {
        try {
            if (Tags::where('id',$id)->exists()){
                $query = Tags::where('id',$id)->get();

                return response()->json([
                    'data' => $query,
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
     * Method allow to update the name of the particular group.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            $tag = Tags::where('id',$id)->first();
            $request->validate([
                'name' => ['required','string', Rule::unique('tags', 'name')->ignore($tag->id)]
            ]);

            if (Tags::where('id',$id)->exists()){
                Tags::where('id',$id)
                    ->update([
                        'name' => $request->name,
                        'seo_title' => $request->seo_title,
                        'seo_description' => $request->seo_description,
                        'seo_picture_id' => $request->seo_picture_id,
                        'is_visibility' => $request->is_visible ?? 1,
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);

                // Check the WordPress sites are existing or not and continue
                $wordpress = Wordpress::all();
                if (!empty($wordpress)){
                    $sync = new TagSyncController();
                    $sync->updateSyncTag($id);
                }

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Tag name is updated successfully',
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
     * Method allow to soft delete the particular group.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (Tags::where('id',$id)->exists()){

                // Check the wordpress sites are existing or not and continue
                $wordpress = Wordpress::all();
                if (!empty($wordpress)){
                    $sync = new TagSyncController();
                    $sync->deleteSyncTag($id);
                }
                Tags::where('id',$id)->delete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Tag is deleted successfully',
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
     * Method allow to soft delete the set of groups.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->tags_id)){
                foreach ($request->tags_id as $tag_id)
                {
                    $category = Tags::findOrFail($tag_id);
                    $category->delete();
                }

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Tags are deleted successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one legalText to delete'
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

    /**
     * Method allow to change the order of display
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function sorting(Request $request):JsonResponse
    {
        try {
            foreach ($request->tags_sorting as $sorting)
            {
                $index = $sorting['id'];
                $new_position = $sorting['newposition'];
                $display_order_number = [
                    'display_order' => $new_position
                ];
                Tags::where('id',$index)->update($display_order_number);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'The Tags are sorted successfully',
            ],200);

        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

} // End class

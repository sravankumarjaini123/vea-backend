<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Wordpress;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;
use Exception;

class CategoryController extends Controller
{
    /**
     * Method allow to display list of all categories or single category.
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $categories = DB::table('categories')
                ->orderBy('display_order')
                ->get();
            $query = $this->getMasterDataDetailsOverview($categories);
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
     * Method allow to store or create the new Category.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:categories'
            ]);

            $order_number = Categories::max('display_order');

            $category = DB::table('categories')->insert([
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
                'message' => 'Category is added successfully',
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
            if (Categories::where('id',$id)->exists()){
                $categories = Categories::where('id',$id)->get();
                $query = $this->getMasterDataDetailsOverview($categories);
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
     * Method allow to update the name of the particular Category.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            $category = Categories::where('id',$id)->first();
            $request->validate([
                'name' => ['required','string', Rule::unique('categories', 'name')->ignore($category->id)]
            ]);

            if (Categories::where('id',$id)->exists()){

                Categories::where('id',$id)
                    ->update([
                        'name' => $request->name,
                        'seo_title' => $request->seo_title,
                        'seo_description' => $request->seo_description,
                        'seo_picture_id' => $request->seo_picture_id,
                        'is_visibility' => $request->is_visible ?? 1,
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);

                // Check the wordpress sites are existing or not and continue
                $wordpress = Wordpress::all();
                if (!empty($wordpress)){
                    $sync = new CategorySyncController();
                    $update = $sync->updateSyncCategory($id);
                }

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Category name is updated successfully',
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
     * Method allow to soft delete the particular Category.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id):JsonResponse
    {
        try {
            if (Categories::where('id',$id)->exists()){

                // Check the wordpress sites are existing or not and continue
                $wordpress = Wordpress::all();
                if (!empty($wordpress)){
                    $sync = new CategorySyncController();
                    $un_sync = $sync->deleteSyncCategory($id);
                }

                Categories::where('id',$id)->delete();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Category is deleted successfully',
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
     * Method allow to soft delete the set of Categories.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request):JsonResponse
    {
        try {
            if (!empty($request->categories_id)) {
                foreach ($request->categories_id as $category_id) {
                    $category = Categories::findOrFail($category_id);
                    $category->delete();
                }

                return response()->json([
                    'status' => 'Success',
                    'message' => 'The Categories are deleted successfully',
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

    /**
     * Method allow to change the order of display for categories
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function sorting(Request $request):JsonResponse
    {
        try {
            foreach ($request->categories_sorting as $sorting)
            {
                $index = $sorting['id'];
                $new_position = $sorting['newposition'];
                $display_order_number = [
                    'display_order' => $new_position
                ];
                Categories::where('id',$index)->update($display_order_number);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'The Categories are sorted successfully',
            ],200);

        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

} // End Class

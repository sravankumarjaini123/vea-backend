<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Titles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class TitleController extends Controller
{
    /**
     * Method allow to display list of all groups or single group.
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $query = Titles::all()->sortBy('display_order');

            return response()->json([
                'data' => $query,
                'message' => 'Success',
            ], 200);

        } catch (\Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function
}

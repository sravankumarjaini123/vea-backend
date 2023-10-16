<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Countries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class CountryController extends Controller
{
    /**
     * Method allow to display list of all countries
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $country_details = Countries::all();
            $country = array();
            if (!empty($country_details)) {
                foreach ($country_details as $country_detail) {
                    $country[] = [
                        'id' => $country_detail->id,
                        'name' => $country_detail->name,
                        'emoji' => $country_detail->emoji,
                    ];
                }
            }
            return response()->json([
                'countries' => $country,
                'message' => 'Success',
            ], 200);
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}

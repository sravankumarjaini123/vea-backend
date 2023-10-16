<?php

namespace App\Http\Controllers\RolesPermissions;

use App\Http\Controllers\Controller;
use App\Models\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class PermissionController extends Controller
{
    /**
     * Method allow to display list of all Permissions
     * @return JsonResponse
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $query = Permissions::all();
            return response()->json([
                'data' => $query,
                'message' => 'Success',
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function
}

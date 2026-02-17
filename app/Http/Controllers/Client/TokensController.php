<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Nette\Schema\ValidationException;

class TokensController extends Controller
{
    /**
     * Method to retrieve the client tokens from the application
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request):JsonResponse
    {
        // dd($request->all());
        try {
            $request->validate([
                'channel' => 'required|in:backend,web',
            ]);
            // Check if Existing or not
            $client_details = DB::table('oauth_clients')->get();
            // If Not then create the default Client Tokens
            if (count($client_details) === 0){
                Artisan::call('passport:install');
            }

            if (! DB::table('oauth_clients')->where('name','=','Web Password Grant Client')->exists()) {
                $secret = Str::random(48);
                DB::table('oauth_clients')->insert([
                    'name' => 'Web Password Grant Client',
                    'secret' => $secret,
                    'provider' => 'users',
                    'redirect' => 'http://localhost',
                    'personal_access_client' => 0,
                    'password_client' => 1,
                    'revoked' => 0,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }

            if ($request->channel === 'backend'){
                $id = 2;
            } else {
                $id = 3;
            }
            $client_detail = DB::table('oauth_clients')->where('id','=', $id)->first();
            if (!empty($client_detail)) {
                $result_array = [
                    'id' => $client_detail->id,
                    'secret' => $client_detail->secret,
                    'revoked' => $client_detail->revoked,
                    'created_at' => $client_detail->created_at
                ];
                return response()->json([
                    'clientDetails' => $result_array,
                    'status' => 'Success',
                ], 201);
            } else {
                return response()->json([
                    'status' => 'Warning',
                    'message' => 'Sorry, there are no client tokens available.',
                ], 201);
            }
        } catch (ValidationException $exception){
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 400);
        }
    } // End Function
}

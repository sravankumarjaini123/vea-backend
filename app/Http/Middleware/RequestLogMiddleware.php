<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class RequestLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        $response = $next($request);

        if (app()->environment('local')) {

            $log = [
                'URI' => $request->getUri(),
                'METHOD' => $request->getMethod(),
                'REQUEST_BODY' => $request->all(),
                'RESPONSE' => $request->getContent(),
            ];

            Log::info(json_encode($log));

        } // End if

        return $response;
    }

    /**
     * Method allow to save the new company through invitation
     * @param Request $request
     * @param JsonResponse $response
     * @throws Exception
     */
    public function terminate(Request $request, JsonResponse $response)
    {

        $user = Auth::guard('api')->user();

        if ($user) {
            $user_id = $user->id;
        } else {
            $user_id = null;
        }

        if ($request->getMethod() == 'POST'){
            $data = $response->getData();
        } else {
            $data = null;
        }

        $log_entry = DB::table('log_entries')->insert([
            'user_id' => $user_id,
            'url' => $request->path(),
            'http_method' => $request->method(),
            'status_code' => $response->status(),
            'response_data' => json_encode($data),
            'ip' => $request->ip(),
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

    } // End function

} // End class

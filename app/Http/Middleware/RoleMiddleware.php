<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $resource, $permission_id): Response
    {
        $user = Auth::guard('api')->user();
        $users_roles = Auth::guard('api')->user()->getUserRole();
        if ($resource === 'contacts' && $permission_id == 3 && $request->id == $user->id) {
            return $next($request);
        }
        if (!$users_roles->isEmpty() && $user->sys_admin == true) {
            foreach ($users_roles as $users_role) {
                if ($resource === 'users' || $resource === 'roles-and-permissions' || $resource === 'applications') {
                    if ($users_role->slug === 'super-administrator') {
                        return $next($request);
                    } else {
                        return response()->json([
                            'status' => 'Error',
                            'message' => 'Only accessed to the Super Administrator'
                        ], 450);
                    }
                } else {
                    if ($users_role->slug != 'super-administrator') {
                        if (!Auth::guard('api')->user()->hasResourceRole($resource, $users_role->id, $permission_id)) {
                            return response()->json([
                                'status' => 'Error',
                                'message' => 'Sorry, There is no permission to perform this action.'
                            ], 450);
                        }
                    }
                }
            }
            return $next($request);
        } else {
            return response()->json([
                'status' => 'Error',
                'message' => 'Sorry, There is no permission to perform this action.'
            ], 450);
        }
    }
}

<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Notifications;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class SystemSettingsController extends Controller
{
    /**
     * Method allow to display all the notifications for system by all users
     * @return JsonResponse
     * @throws Exception
     */
    public function systemNotifications():JsonResponse
    {
        try {
            $notifications = Notifications::all()->sortByDesc('created_at');
            $users_notifications = array();
            foreach ($notifications as $notification) {
                $notification_detail = DB::table('users_notifications')->where('notifications_id', $notification->id)->first();
                if (!empty($notification_detail)) {
                    $users = User::where('id', $notification_detail->users_id)->first();
                    if (!empty($users)) {
                        $users_notifications[] = [
                            'id' => $notification->id,
                            'user_id' => $users->id,
                            'user_name' => $users->firstname . ' ' . $users->lastname,
                            'data_name' => $notification->data_name,
                            'notification_type' => $notification->notification_type,
                            'data_channel' => $notification->data_channel,
                            'status' => $notification->status,
                            'error_message' => $notification->error_message,
                            'created_at' => $notification->created_at,
                            'updated_at' => $notification->updated_at,
                        ];
                    }
                }
            }
            return response()->json([
                'systemNotifications' => $users_notifications,
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

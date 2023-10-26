<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class JobStatusNotificationController extends Controller
{
    private $user_id;

    /**
     * The stream source.
     */
    public function statusNotification($user_id)
    {
        $this->user_id = $user_id;
        return response()->stream(function (){
            while (true) {
                // Break the loop if the client aborted the connection (closed the page)
                if (connection_aborted()) {
                    break;
                }

                $users = User::where('id', $this->user_id)->first();
                $date = Carbon::now()->subDays(3);
                $user_notifications = $users->notifications()->where('notifications.updated_at', '>=', $date)->latest()->get();
                $result_array = array();
                if (!empty($user_notifications)) {
                    foreach ($user_notifications as $user_notification){
                        $result_array[] = [
                            'data_id' => $user_notification->data_id,
                            'data_name' => $user_notification->data_name,
                            'notification_type' => $user_notification->notification_type,
                            'data_channel' => $user_notification->data_channel,
                            'status' => $user_notification->status,
                            'error_message' => $user_notification->error_message,
                            'updated_at' => $user_notification->updated_at,
                        ];
                    }
                    echo 'data: {"notificationDetails":' . json_encode($result_array) . '}' . "\n\n";
                } else {
                    echo 'data: {"message : There is no jobs running at this moment !!!"}' . "\n\n";
                }
                ob_end_flush();
                flush();
                for ($i=0; $i<=3; $i++){
                    sleep($i);
                }
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
        ]);
    }
}

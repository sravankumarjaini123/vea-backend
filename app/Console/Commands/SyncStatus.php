<?php

namespace App\Console\Commands;

use App\Models\FoldersFiles;
use App\Models\Notifications;
use App\Models\Posts;
use App\Models\Wordpress;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Artisan command to check the status of Notifications and stop the process if it is timed out';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = Carbon::now()->subMinutes(10)->format('Y-m-d H:i:s');
        $date_image = Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s');
        $notifications = Notifications::where('notification_type', 'posts_sync')
            ->where('status', 'processing')->where('created_at','<',$date)->get();
        $image_notifications = Notifications::where('notification_type', 'image_optimization')
            ->where('status', '=', 'processing')->where('created_at','<',$date_image)->get();

        /*if (!empty($notifications)) {
            foreach ($notifications as $notification) {
                $post = Posts::where('id', $notification->data_id)->first();
                $wordpress = Wordpress::where('name', $notification->data_channel)->first();
                $post->wordpress()->updateExistingPivot($wordpress->id, ['sync_status' => 'lastSyncFailed']);
                Notifications::where('id', $notification->id)->update([
                    'status' => 'failed',
                    'error_message' => 'Connection time out'
                ]);
            }
        }*/
        if (!empty($image_notifications)) {
            foreach ($image_notifications as $image_notification){
                $notification_detail = Notifications::where('id', $image_notification->id)->first();
                FoldersFiles::where('id', $notification_detail->data_id)->update([
                    'optimizing_status' => 'lastOptimizationFailed',
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
                $notification_detail->status = 'failed';
                $notification_detail->error_message = 'Connection time out';
                $notification_detail->save();
            }
        }
        return 1;
    } // End Function
}

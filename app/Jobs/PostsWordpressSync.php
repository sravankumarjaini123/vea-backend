<?php

namespace App\Jobs;

use App\Http\Controllers\Posts\PostsSyncController;
use App\Http\Controllers\Wordpress\WordpressController;
use App\Models\Notifications;
use App\Models\Posts;
use App\Models\Wordpress;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Notifications\JobStatusNotificationController;

class PostsWordpressSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $sites_id;
    public $users_id;
    public $posts_id;
    public $notifications_id;
    public $result;
    /**
     * Create a new job instance.
     */
    public function __construct($sites_id, $posts_id, $notification_id, $user_id)
    {
        $this->sites_id = $sites_id;
        $this->posts_id = $posts_id;
        $this->notifications_id = $notification_id;
        $this->users_id = $user_id;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $notification = Notifications::where('id',$this->notifications_id)->first();
            $notification_status = Notifications::where('id','!=',$notification->id)
                ->where('status','processing')
                ->where('created_at','<',$notification->created_at)->get();
            if (count($notification_status) === 0) {
                $post = new PostsSyncController();
                $post_detail = Posts::where('id', $this->posts_id)->first();
                $return_answer = $post->postsSync($this->sites_id, $this->posts_id);
                if ($return_answer->getStatusCode() != 200) {
                    Notifications::where('id', $this->notifications_id)
                        ->update(['status' => 'failed',
                            'error_message' => $return_answer->getData()->message,
                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        ]);
                    // Trigger the event for React on status update
                    $notification_event = new JobStatusNotificationController();
                    $notification_event->statusNotification($this->users_id);
                    // Update the sync status for the post

                    $post_detail->wordpress()->updateExistingPivot($this->sites_id, ['sync_status' => 'lastSyncFailed']);
                    // Fail the Job
                    $this->fail($return_answer->getData());
                    return response()->json([
                        'status' => 'Success',
                        'message' => $return_answer->getData()->message
                    ], $return_answer->getStatusCode());
                } else {
                    if ($post_detail->shareable_type === 'wordpress' || empty($post_detail->shareable_type)) {
                        $request = new Request();
                        $request->setMethod('POST');
                        $request->request->add(['wordpress_id' => $this->sites_id]);
                        $posts_url = $post->getShareableURL($request, $this->posts_id);
                        if ($posts_url->getStatusCode() === 200) {
                            $shareable_url = $posts_url->getData()->callbackURL;
                            DB::table('posts')->where('id', $this->posts_id)->update([
                                'shareable_posts' => 1,
                                'shareable_type' => 'wordpress',
                                'shareable_callback_url' => $shareable_url
                            ]);
                        }
                    }
                    Notifications::where('id', $this->notifications_id)
                        ->update(['status' => 'success',
                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                        ]);
                    // Trigger the event for React on status update
                    $notification_event = new JobStatusNotificationController();
                    $notification_event->statusNotification($this->users_id);
                    return response()->json([
                        'status' => 'Success',
                        'message' => 'The Post had synced Successfully'
                    ], 200);
                }
            } else{
                PostsWordpressSync::dispatch($this->sites_id, $this->posts_id, $this->notifications_id, $this->users_id)->delay(now()->addSeconds(10));
            }
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function
}

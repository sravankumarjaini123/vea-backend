<?php

namespace App\Jobs;

use App\Models\FoldersFiles;
use App\Models\Notifications;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Exception;
use Intervention\Image\ImageManager;

class ImageOptimization implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $files_id;
    public $notifications_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($file_id, $notification_id)
    {
        $this->files_id = $file_id;
        $this->notifications_id = $notification_id;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $file = FoldersFiles::where('id', $this->files_id)->first();
        $notification = Notifications::where('id', $this->notifications_id)->first();
        $notification_status = Notifications::where('id','!=',$notification->id)
            ->where('status','processing')
            ->where('created_at','<',$notification->created_at)->get();
        if (count($notification_status) === 0) {
            if (env('DISK_DRIVER') === 'mounted') {
                $disk = 'volume';
            } else {
                $disk = 'media';
            }
            $aspect_ratio_array = [
                ['width' => 256, 'height' => 144,],
                ['width' => 512, 'height' => 288,],
                ['width' => 1024, 'height' => 576,],
                ['width' => 1920, 'height' => 1080,],
                ['width' => 2048, 'height' => 1152,],
            ];
            if (Storage::disk($disk)->exists($file->hash_name)) {
                $disk_path = Storage::disk($disk)->path('');
                $path = Storage::disk($disk)->path($file->hash_name);
                $fileName = pathinfo($path, PATHINFO_FILENAME);
                list($originalWidth, $originalHeight) = getimagesize($path);
                $height_out = (int)ceil(($originalWidth * 9) / 16);
                if ($originalHeight > $height_out) {
                    if ($originalWidth <= $originalHeight) {
                        $final_result = $this->optimizeForVerticallyRectangleImages($aspect_ratio_array, $originalHeight, $path, $fileName, $file, $disk_path, $disk);
                    } else {
                        $condition = 'fit';
                        $final_result = $this->optimizeStandardToHDResolution($aspect_ratio_array, $condition, $path, $fileName, $file, $disk_path, $disk);
                    }
                } elseif ($originalHeight < $height_out) {
                    $condition = 'fit';
                    $final_result = $this->optimizeStandardToHDResolution($aspect_ratio_array, $condition, $path, $fileName, $file, $disk_path, $disk);
                } else {
                    $condition = 'resize';
                    $final_result = $this->optimizeStandardToHDResolution($aspect_ratio_array, $condition, $path, $fileName, $file, $disk_path, $disk);
                }
                if ($final_result->getStatusCode() === 200) {
                    $notification->status = 'success';
                    $file->optimizing_status = 'success';
                } else {
                    $notification->status = 'failed';
                    $notification->error_message = $final_result->getData()->message;
                    $file->optimizing_status = 'lastOptimizationFailed';
                }

            } else {
                $notification->status = 'failed';
                $notification->error_message = 'There is no file to optimize, Please check the file and optimize again';
                $file->optimizing_status = 'lastOptimizationFailed';
            }

            $notification->save();
            $file->save();
        } else {
            ImageOptimization::dispatch($this->files_id, $this->notifications_id)->delay(now()->addSeconds(10));
        }
    } // End Function

    public function optimizeForVerticallyRectangleImages($aspect_ratio_array, $originalHeight, $path, $fileName, $file, $disk_path, $disk):JsonResponse
    {
        try {
            $final_height = $originalHeight;
            $final_width = (int)ceil(($originalHeight * 16) / 9);
            foreach ($aspect_ratio_array as $array){
                $image = new ImageManager(['driver' => 'gd']);
                $image = $image->canvas($final_width, $final_height);
                $image->fill('#C5C5C5');
                $image->insert($path, 'center', 0, 0);
                $image->resize($array['width'], $array['height'], function ($constraint) {
                    $constraint->upsize();
                });
                $hash_name = $disk_path . $fileName . $array['width'] . 'x' . $array['height'] . '.' . $file->type;
                $assign_name = $file->name . '-' . $array['width'] . 'x' . $array['height'];
                $file_hash_name = $fileName . $array['width'] . 'x' . $array['height'] . '.' . $file->type;
                $url = URL::to('/');
                $file_path = $url. '/storage/media/' . $file_hash_name;
                $image->save($hash_name);
                $size = Storage::disk($disk)->size($file_hash_name);
                FoldersFiles::insertGetId([
                    'name' => $assign_name,
                    'type' => $file->type,
                    'size' => $size,
                    'hash_name' => $file_hash_name,
                    'file_path' => $file_path,
                    'store_type' => 'optimization',
                    'copyright_text' => $file->copyright_text,
                    'resolution' => $array['width'] . 'x' . $array['height'],
                    'optimized_parent_id' => $file->id,
                ]);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'The file is optimized successfully',
            ], 200);
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    public function optimizeStandardToHDResolution($aspect_ratio_array, $condition, $path, $fileName, $file, $disk_path, $disk):JsonResponse
    {
        try {
            foreach ($aspect_ratio_array as $array){
                $image = new ImageManager(['driver' => 'gd']);

                $image = $image->make($path);
                if ($condition === 'fit') {
                    $image->fit($array['width'], $array['height'], function ($constraint) {
                        $constraint->upsize();
                    });
                } else {
                    $image->resize($array['width'], null, function ($constraint) {
                        $constraint->aspectratio();
                        $constraint->upsize();
                    });
                }
                $hash_name = $disk_path . $fileName . $array['width'] . 'x' . $array['height'] . '.' . $file->type;
                $assign_name = $file->name . '-' . $array['width'] . 'x' . $array['height'];
                $file_hash_name = $fileName . $array['width'] . 'x' . $array['height'] . '.' . $file->type;
                $url = URL::to('/');
                $file_path = $url. '/storage/media/' . $file_hash_name;
                $image->save($hash_name);
                $size = Storage::disk($disk)->size($file_hash_name);
                FoldersFiles::insert([
                    'name' => $assign_name,
                    'type' => $file->type,
                    'size' => $size,
                    'hash_name' => $file_hash_name,
                    'file_path' => $file_path,
                    'store_type' => 'optimization',
                    'copyright_text' => $file->copyright_text,
                    'resolution' => $array['width'] . 'x' . $array['height'],
                    'optimized_parent_id' => $file->id,
                ]);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'The file is optimized successfully for Standard to HD resolution',
            ], 200);
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function
}

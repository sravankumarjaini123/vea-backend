<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Posts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Artisan command to update the status of the Posts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $time = Carbon::now()->format('Y-m-d H:i');
        DB::table('posts')->where([['status_id','=',2], ['inactive_at','!=',null], ['inactive_at','<=',$time]])
            ->update([
                'status_id' => 4,
                'published_at' => null,
                'inactive_at' => $time,
            ]);

        DB::table('posts')->where([['status_id','=',3], ['published_at','<=',$time]])
            ->update([
                'status_id' => 2,
            ]);
    }
}

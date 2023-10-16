<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class EmailsSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('emails_settings')->insert([
            'technologies' => 'system',
            'name' => 'forgot_password',
            'display_name' => 'Forgot Password',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

    }

}

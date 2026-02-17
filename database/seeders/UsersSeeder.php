<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $password = Hash::make('password');
        DB::table('users')->insert([
            'salutations_id' => 1,
            'firstname' => 'VEA',
            'lastname' => 'software',
            'email' => 'welcome@vea.de',
            'password' => $password,
            'sys_admin' => 1,
            'sys_customer' => 1,
            'username' => 'Welcome User',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}

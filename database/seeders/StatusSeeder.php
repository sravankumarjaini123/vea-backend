<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (App::isLocale('de')) {

            DB::table('status')->insert([
                'id' => 1,
                'name' => 'Entwurf',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('status')->insert([
                'id' => 2,
                'name' => 'Veröffentlicht',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('status')->insert([
                'id' => 3,
                'name' => 'Geplant',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('status')->insert([
                'id' => 4,
                'name' => 'Deaktiviert',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        } // End if

        // ENGLISH
        if (App::isLocale('en')) {

            DB::table('status')->insert([
                'id' => 1,
                'name' => 'draft',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('status')->insert([
                'id' => 2,
                'name' => 'published',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('status')->insert([
                'id' => 3,
                'name' => 'scheduled',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('status')->insert([
                'id' => 4,
                'name' => 'inactive',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        } // End if

    } // End Function

} // End Class

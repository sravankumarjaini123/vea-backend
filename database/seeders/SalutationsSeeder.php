<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class SalutationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // GERMAN
        if (App::isLocale('de')) {

            DB::table('salutations')->insert([
                'salutation' => 'Herr',
                'display_order' => 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('salutations')->insert([
                'salutation' => 'Frau',
                'display_order' => 2,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('salutations')->insert([
                'salutation' => 'Diverse',
                'display_order' => 3,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        }

        // ENGLISH
        if (App::isLocale('en')) {

            DB::table('salutations')->insert([
                'salutation' => 'Mr',
                'display_order' => 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('salutations')->insert([
                'salutation' => 'Mrs',
                'display_order' => 2,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        }

    }
}

<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class TitlesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('titles')->insert([
            'title' => 'Dr.',
            'display_order' => 1,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        DB::table('titles')->insert([
            'title' => 'Prof.',
            'display_order' => 2,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        DB::table('titles')->insert([
            'title' => 'Prof. Dr.',
            'display_order' => 3,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}

<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeasuresParametersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('measures_parameters')->insert([
            [
                'type' => 'internal',
                'key' => 'Interest rate',
                'value' => '6.0',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'type' => 'internal',
                'key' => 'Annual energy price increase',
                'value' => '1.0',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]
        ]);
        DB::table('measures_parameters')->insert([
            [
                'type' => 'price_index',
                'key' => '2010',
                'key_extra' => 'Juni',
                'value' => '93',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'type' => 'price_index',
                'key' => '2011',
                'key_extra' => 'Juni',
                'value' => '94.8',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'type' => 'price_index',
                'key' => '2012',
                'key_extra' => 'Juni',
                'value' => '96.8',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'type' => 'price_index',
                'key' => '2013',
                'key_extra' => 'Juni',
                'value' => '98.1',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'type' => 'price_index',
                'key' => '2014',
                'key_extra' => 'Juni',
                'value' => '99.1',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'type' => 'price_index',
                'key' => '2015',
                'key_extra' => 'Juni',
                'value' => '100',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'type' => 'price_index',
                'key' => '2016',
                'key_extra' => 'Juni',
                'value' => '100.8',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'type' => 'price_index',
                'key' => '2017',
                'key_extra' => 'Juni',
                'value' => '101.8',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'type' => 'price_index',
                'key' => '2018',
                'key_extra' => 'Juni',
                'value' => '103.3',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'type' => 'price_index',
                'key' => '2019',
                'key_extra' => 'Juni',
                'value' => '105.1',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'type' => 'price_index',
                'key' => '2020',
                'key_extra' => 'Juni',
                'value' => '106.3',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'type' => 'price_index',
                'key' => '2021',
                'key_extra' => 'Juni',
                'value' => '107.6',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'type' => 'price_index',
                'key' => '2022',
                'key_extra' => 'Mai',
                'value' => '116.4',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]
        ]);
    }
}

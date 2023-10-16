<?php

namespace Database\Seeders;

use File;
use App\Models\Countries;
use Illuminate\Database\Seeder;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // Countries::truncate();

        $jsonCountries = File::get("database/data/countries.json");
        $countries = json_decode($jsonCountries);

        foreach ($countries as $key => $value) {
            Countries::create([
                "name" => $value->name,
                "iso2" => $value->iso2,
                "iso3" => $value->iso3,
                "numeric_code" => $value->numeric_code,
                "phone_code" => $value->phone_code,
                "capital" => $value->capital,
                "currency" => $value->currency,
                "currency_symbol" => $value->currency_symbol,
                "tld" => $value->tld,
                "native" => $value->native,
                "region" => $value->region,
                "subregion" => $value->subregion,
                "latitude" => $value->latitude,
                "longitude" => $value->longitude,
                "emoji" => $value->emoji,
                "emojiU" => $value->emojiU,
                "timezones" => json_encode($value->timezones),
                "translations" => json_encode($value->translations)
            ]);
        }

    } // End function

} // End class

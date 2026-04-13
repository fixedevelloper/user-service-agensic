<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CountriesSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $countries = [
            [
                'name' => 'Congo',
                'iso' => 'CG',
                'iso3' => 'COG',
                'phonecode' => 242,
                'currency' => 'XAF',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'RDC',
                'iso' => 'CD',
                'iso3' => 'COD',
                'phonecode' => 243,
                'currency' => 'CDF',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Cameroun',
                'iso' => 'CM',
                'iso3' => 'CMR',
                'phonecode' => 237,
                'currency' => 'XAF',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Gabon',
                'iso' => 'GA',
                'iso3' => 'GAB',
                'phonecode' => 241,
                'currency' => 'XAF',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => "Côte d'Ivoire",
                'iso' => 'CI',
                'iso3' => 'CIV',
                'phonecode' => 225,
                'currency' => 'XOF',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Sénégal',
                'iso' => 'SN',
                'iso3' => 'SEN',
                'phonecode' => 221,
                'currency' => 'XOF',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'France',
                'iso' => 'FR',
                'iso3' => 'FRA',
                'phonecode' => 033,
                'currency' => 'EUR',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Belgique',
                'iso' => 'BE',
                'iso3' => 'BEL',
                'phonecode' => 033,
                'currency' => 'EUR',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
        ];

        DB::table('countries')->upsert(
            $countries,
            ['iso'], // ⚠️ important (clé unique)
            ['name', 'iso3', 'phonecode', 'currency', 'status', 'updated_at']
        );
    }
}

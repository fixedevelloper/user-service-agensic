<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OperatorsSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        // Assure-toi que les pays existent déjà et récupère leurs IDs
        $countries = DB::table('countries')->pluck('id', 'name');

        $operators = [
            // Congo
            [
                'name' => 'MTN Congo',
                'code' => 'MTN',
                'logo' => 'https://example.com/logos/mtn.png',
                'status' => 1,
                'country_id' => $countries['Congo'],
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Airtel Congo',
                'code' => 'Airtel',
                'logo' => 'https://example.com/logos/airtel.png',
                'status' => 1,
                'country_id' => $countries['Congo'],
                'created_at' => $now,
                'updated_at' => $now
            ],

            // RDC
            [
                'name' => 'Vodacom RDC',
                'code' => 'Vodacom',
                'logo' => 'https://example.com/logos/vodacom.png',
                'status' => 1,
                'country_id' => $countries['RDC'],
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Airtel RDC',
                'code' => 'Airtel',
                'logo' => 'https://example.com/logos/airtel.png',
                'status' => 1,
                'country_id' => $countries['RDC'],
                'created_at' => $now,
                'updated_at' => $now
            ],

            // Cameroun
            [
                'name' => 'MTN Cameroun',
                'code' => 'MTN',
                'logo' => 'https://example.com/logos/mtn.png',
                'status' => 1,
                'country_id' => $countries['Cameroun'],
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Orange Cameroun',
                'code' => 'Orange',
                'logo' => 'https://example.com/logos/orange.png',
                'status' => 1,
                'country_id' => $countries['Cameroun'],
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Nexttel Cameroun',
                'code' => 'Nexttel',
                'logo' => 'https://example.com/logos/nexttel.png',
                'status' => 1,
                'country_id' => $countries['Cameroun'],
                'created_at' => $now,
                'updated_at' => $now
            ],

            // Gabon
            [
                'name' => 'MTN Gabon',
                'code' => 'MTN',
                'logo' => 'https://example.com/logos/mtn.png',
                'status' => 1,
                'country_id' => $countries['Gabon'],
                'created_at' => $now,
                'updated_at' => $now
            ],

            // Côte d’Ivoire
            [
                'name' => 'MTN Côte d’Ivoire',
                'code' => 'MTN',
                'logo' => 'https://example.com/logos/mtn.png',
                'status' => 1,
                'country_id' => $countries["Côte d'Ivoire"],
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Orange Côte d’Ivoire',
                'code' => 'Orange',
                'logo' => 'https://example.com/logos/orange.png',
                'status' => 1,
                'country_id' => $countries["Côte d'Ivoire"],
                'created_at' => $now,
                'updated_at' => $now
            ],

            // Sénégal
            [
                'name' => 'Orange Sénégal',
                'code' => 'Orange',
                'logo' => 'https://example.com/logos/orange.png',
                'status' => 1,
                'country_id' => $countries['Sénégal'],
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Tigo Sénégal',
                'code' => 'Tigo',
                'logo' => 'https://example.com/logos/tigo.png',
                'status' => 1,
                'country_id' => $countries['Sénégal'],
                'created_at' => $now,
                'updated_at' => $now
            ],
        ];

        DB::table('operators')->insert($operators);
    }
}

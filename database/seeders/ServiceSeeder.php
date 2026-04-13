<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run()
    {
        $services = [

            [
                'name' => 'Envoyer argent',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/3135/3135706.png',
                'route' => 'send_money',
                'position' => 1,
                'category' => 'Finance',
                'is_active' => true,
                'color' => '#723e64',
            ],
            [
                'name' => 'Recevoir argent',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/2920/2920244.png',
                'route' => 'receive_money',
                'position' => 2,
                'category' => 'Finance',
                'is_active' => true,
                'color' => '#ecf0c7',
            ],
            [
                'name' => 'Paiement factures',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/1041/1041886.png',
                'route' => 'bills',
                'position' => 3,
                'category' => 'Finance',
                'is_active' => true,
                'color' => '#e9383f',
            ],
            [
                'name' => 'Achat crédit',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/126/126510.png',
                'route' => 'airtime',
                'position' => 4,
                'category' => 'Finance',
                'is_active' => true,
                'color' => '#ecf0c7',
            ],
            [
                'name' => 'Pronostic IA',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/4712/4712027.png',
                'route' => 'ai_score',
                'position' => 5,
                'category' => 'IA',
                'is_active' => true,
                'color' => '#004700',
            ],
            [
                'name' => 'Paris sportifs',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/861/861512.png',
                'route' => 'betting',
                'position' => 6,
                'category' => 'Divertissement',
                'is_active' => true,
                'color' => '#ae642d',
            ],
            [
                'name' => 'Historique',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/1828/1828490.png',
                'route' => 'history',
                'position' => 7,
                'category' => 'Finance',
                'is_active' => true,
                'color' => '#0f0a3d',
            ],
            [
                'name' => 'Paramètres',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/2099/2099058.png',
                'route' => 'settings',
                'position' => 8,
                'category' => 'System',
                'is_active' => true,
                'color' => '#000080',
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['route' => $service['route']], // évite doublons
                $service
            );
        }
    }
}

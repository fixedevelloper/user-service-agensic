<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $config = config('services.whatsapp');
        $this->baseUrl = "https://graph.facebook.com/{$config['version']}/{$config['phone_number_id']}/messages";
        $this->token = $config['token'];
    }

    public function sendOtp($to, $code)
    {
        try {
            $response = Http::withToken($this->token)->post($this->baseUrl, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => 'auth_otp_code', // Le nom exact de ton template validé sur Meta
                    'language' => ['code' => 'fr'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $code]
                            ]
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'url',
                            'index' => '0',
                            'parameters' => [
                                ['type' => 'text', 'text' => $code] // Si ton bouton a un copier/coller
                            ]
                        ]
                    ]
                ]
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error("WhatsApp API Error: " . $e->getMessage());
            return false;
        }
    }
}

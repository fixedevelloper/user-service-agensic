<?php
namespace App\Jobs;

use App\Models\Deposit;
use Illuminate\Support\Facades\Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDeposit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $deposit;
    public $userData;

    public function __construct(Deposit $deposit, array $userData)
    {
        $this->deposit = $deposit;
        $this->userData = $userData;
    }

    public function handle()
    {
        // 📡 L'appel HTTP se fait ici, en arrière-plan
        $response = Http::withToken(env('API_SERVICE_TOKEN'))
            ->timeout(60)
            ->post('http://127.0.0.1:8001/api/deposit', [
                'user_id' => $this->userData['id'],
                'phone' => $this->userData['phone'],
                'amount' => $this->deposit->amount,
                'name' => $this->userData['name'],
                'order_id' => $this->deposit->reference,
                'return_url' => route('deposit.return'),
                'webhook_url' => route('deposit.webhook'),
            ]);

        if ($response->successful()) {
            $data = $response->json();
            // Ici, vous pouvez envoyer une notification (WebSocket/Firebase) 
            // à l'utilisateur avec l'URL de paiement
        } else {
            $this->deposit->update(['status' => 'failed']);
        }
    }
}
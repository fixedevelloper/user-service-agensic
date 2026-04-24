<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Deposit; // Assurez-vous d'avoir un modèle pour suivre les paiements
use App\Jobs\SyncDepositWithServiceA;
use Illuminate\Support\Facades\Log;

class CheckPaymentStatus extends Command
{
    // Signature de la commande
    protected $signature = 'payment:check-status';
    protected $description = 'Vérifie le statut des paiements en attente auprès de FusionPay';

   public function handle()
{
    // On récupère les dépôts en attente
    $pendingPayments = Deposit::where('status', 'pending')
        ->whereNotNull('reference') 
        ->get();
        logger($pendingPayments);

    foreach ($pendingPayments as $payment) {
        try {
            $response = Http::timeout(10)
                ->get("https://www.pay.moneyfusion.net/paiementNotif/{$payment->reference}");
            
            if ($response->successful()) {
                $body = $response->json();

                // 1. Vérifier si le statut global est true et si la data existe
                if (($body['statut'] ?? false) && isset($body['data'])) {
                    $data = $body['data'];

                    // 2. Vérifier si le paiement est marqué comme "paid"
                    if (($data['statut'] ?? '') === 'paid') {
                        
                        $this->info("Paiement confirmé pour l'ordre : " . $payment->reference);

                        // Mettre à jour le Service B
                        $payment->update([
                            'status' => 'success',
                            'transaction_id' => $data['numeroTransaction'] ?? null // Optionnel: stocker le numéro orange/mtn
                        ]);

                        // 3. Déclencher le Job pour synchroniser le Service A
                        SyncDepositWithServiceA::dispatch([
                            'user_id' => $payment->user_id,
                            'amount' => $payment->amount,
                            'order_id' => $payment->reference,
                            'status' => 'success',
                        ]);
                    } 
                    // Si FusionPay renvoie un autre statut d'erreur spécifique, vous pouvez gérer le 'failed' ici
                }
            }
        } catch (\Exception $e) {
            Log::error("Erreur check FusionPay (Ref: {$payment->reference}): " . $e->getMessage());
        }
    }
}
}
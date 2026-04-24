<?php

namespace App\Console\Commands;

use App\Models\BalanceHistory;
use DB;
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

                            DB::transaction(function () use ($payment) {
                                // 2. Verrouillage de l'utilisateur pour éviter les conflits de lecture/écriture
                                $user = $payment->user;

                                $previousBalance = $user->balance;

                                // 3. Mise à jour du solde de l'utilisateur
                                // Utilisation de increment pour la sécurité SQL atomique
                                $user->increment('balance', $payment->amount);

                                // Rafraîchir l'instance pour avoir le nouveau solde
                                $user->refresh();

                                // 4. Création de l'historique (Audit Trail)
                                BalanceHistory::create([
                                    'user_id' => $user->id,
                                    'amount' => $payment->amount,
                                    'previous_balance' => $previousBalance,
                                    'new_balance' => $user->balance,
                                    'type' => 'deposit',
                                    'provider_reference' => $payment->reference,
                                    'description' => 'Credit via NowPayments',
                                    'metadata' => [

                                        'processed_at' => now()->toDateTimeString()
                                    ]
                                ]);


                            });
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
<?php

namespace App\Console\Commands;

use App\Notifications\DepositUssdProcessed;
use Illuminate\Console\Command;
use App\Models\Deposit;
use App\Models\DepositUssd;
use App\Notifications\DepositProcessed;
use Illuminate\Support\Facades\Notification;

class TestTelegram extends Command
{
    /**
     * Le nom et la signature de la commande.
     * types: classic, ussd
     */
    protected $signature = 'test:telegram {type=classic} {--status=completed}';

    protected $description = 'Envoie une notification de test (classic ou ussd) au groupe Telegram';

    public function handle()
    {
        $type = $this->argument('type');
        $status = $this->option('status');
        $groupId = config('services.telegram-bot-api.group_id');

        if (!$groupId) {
            $this->error("❌ Le GROUP_ID n'est pas configuré dans services.php ou .env");
            return;
        }

        $this->info("🚀 Préparation du test pour le type: {$type} (Statut: {$status})");

        try {
            if ($type === 'classic') {
                $this->testClassicDeposit($groupId);
            } elseif ($type === 'ussd') {
                $this->testUssdDeposit($groupId, $status);
            } else {
                $this->error("Type inconnu. Utilisez 'classic' ou 'ussd'.");
            }
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de l'envoi : " . $e->getMessage());
        }
    }

    private function testClassicDeposit($groupId)
    {
        $deposit = Deposit::with(['user.country', 'operator'])->latest()->first();

        if (!$deposit) {
            $this->warn("⚠️ Aucun dépôt classique trouvé en base. Création d'un faux dépôt...");
            // Optionnel : Créer un modèle factice avec Factory si disponible
            return;
        }

        Notification::route('telegram', $groupId)->notify(new DepositProcessed($deposit));
        $this->info("✅ Notification de dépôt classique envoyée !");
    }

    private function testUssdDeposit($groupId, $status)
    {
        $deposit = DepositUssd::with('user')->latest()->first();

        if (!$deposit) {
            $this->warn("⚠️ Aucun dépôt USSD trouvé. Assurez-vous d'en avoir un en base.");
            return;
        }

        // On simule le changement de statut pour le test
        $deposit->status = $status;
        $deposit->admin_note = "Ceci est un test de notification automatique.";

        Notification::route('telegram', $groupId)->notify(new DepositUssdProcessed($deposit));
        $this->info("✅ Notification USSD ({$status}) envoyée !");
    }
}

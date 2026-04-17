<?php

namespace App\Notifications;

use App\Models\Deposit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;
use NotificationChannels\Telegram\TelegramChannel;

class DepositProcessed extends Notification
{
    use Queueable;

    protected $deposit;

    /**
     * On injecte le modèle Deposit
     */
    public function __construct(Deposit $deposit)
    {
        $this->deposit = $deposit;
    }

    public function via($notifiable)
    {
        return [TelegramChannel::class];
    }

    public function toTelegram($notifiable)
    {
        // Chargement des données via les relations du modèle
        $user = $this->deposit->user;
        $operator = $this->deposit->operator;
        $country = $user->country; // On suppose que User a une relation country

        // Formatage des données
        $amount = number_format($this->deposit->amount, 2);
        $currency = $country->currency ?? 'XAF';
        $flag = $this->getFlagEmoji($country->iso ?? '');

        $date = $this->deposit->completed_at
            ? $this->deposit->completed_at->format('d/m/Y H:i')
            : now()->format('d/m/Y H:i');

        return TelegramMessage::create()
            ->to(config('services.telegram-bot-api.group_id'))
            ->content(
                "📥 *CONFIRMATION DE DÉPÔT*\n" .
                "───────────────────\n" .
                "🆔 *Référence:* #`{$this->deposit->reference}`\n" .
                "📅 *Date:* {$date}\n\n" .

                "👤 *INFORMATIONS CLIENT*\n" .
                "├ Nom: *{$user->name}*\n" .
                "├ Tel: `{$user->phone}`\n" .
                "└ Pays: {$flag} {$country->name}\n\n" .

                "💳 *DÉTAILS DU PAIEMENT*\n" .
                "├ Opérateur: *{$operator->name}*\n" .
                "└ Montant: *{$amount} {$currency}*\n" .
                "───────────────────\n" .
                "✅ *Statut:* Terminé avec succès"
            )
            ->button('🔍 Voir la transaction', env('FRONTEND_URL') . "/admin/deposits/{$this->deposit->id}");
    }

    /**
     * Utilitaire pour les drapeaux (Optionnel mais très pro)
     */
    private function getFlagEmoji(string $code): string
    {
        if (strlen($code) !== 2) return '🌍';
        return collect(str_split(strtoupper($code)))
            ->map(fn($char) => mb_chr(ord($char) + 127397))
            ->implode('');
    }
}

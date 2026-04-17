<?php

namespace App\Notifications;

use App\Models\DepositUssd;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;
use NotificationChannels\Telegram\TelegramChannel;

class DepositUssdProcessed extends Notification
{
    use Queueable;

    protected $deposit;

    public function __construct(DepositUssd $deposit)
    {
        $this->deposit = $deposit;
    }

    public function via($notifiable)
    {
        return [TelegramChannel::class];
    }

    public function toTelegram($notifiable)
    {
        $status = $this->deposit->status;

        // Configuration dynamique selon le statut
        $statusConfig = [
            'pending'   => ['emoji' => '⏳', 'label' => 'EN ATTENTE', 'color' => 'gris'],
            'completed' => ['emoji' => '✅', 'label' => 'COMPLÉTÉ', 'color' => 'vert'],
            'rejected'  => ['emoji' => '❌', 'label' => 'REJETÉ', 'color' => 'rouge'],
            'cancelled' => ['emoji' => '🚫', 'label' => 'ANNULÉ', 'color' => 'noir'],
        ];

        $config = $statusConfig[$status] ?? $statusConfig['pending'];

        $user = $this->deposit->user;
        $amount = number_format($this->deposit->amount, 2);
        $currency = $this->deposit->currency ?? 'XAF';

        return TelegramMessage::create()
            ->to(config('services.telegram-bot-api.group_id'))
            ->content(
                "{$config['emoji']} *MISE À JOUR DÉPÔT USSD*\n" .
                "───────────────────\n" .
                "🆔 *Réf:* #`{$this->deposit->reference}`\n" .
                "📊 *Statut:* *{$config['label']}*\n\n" .

                "👤 *CLIENT*\n" .
                "└ Nom: *{$user->name}*\n" .
                "└ Tel: `{$user->phone}`\n\n" .

                "🔢 *DÉTAILS*\n" .
                "├ Code: `{$this->deposit->ussd_code}`\n" .
                "└ Montant: *{$amount} {$currency}*\n\n" .

                "📝 *NOTE/RAISON*\n" .
                "└ " . ($this->deposit->admin_note ?? '_Aucune information supplémentaire_') . "\n" .
                "───────────────────\n" .
                "📅 Mise à jour le: " . now()->format('d/m/Y H:i')
            )
            ->button('📂 Voir la fiche', env('FRONTEND_URL') . "/admin/deposits-ussd/{$this->deposit->id}");

    }

    private function getFlagEmoji(string $code): string
    {
        if (strlen($code) !== 2) return '🌍';
        return collect(str_split(strtoupper($code)))
            ->map(fn($char) => mb_chr(ord($char) + 127397))
            ->implode('');
    }
}

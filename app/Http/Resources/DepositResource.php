<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'reference' => $this->reference,
            'status' => $this->status,

            // Formatage des dates pour le frontend
            'completed_at' => $this->completed_at ? $this->completed_at->format('d/m/Y H:i') : null,
            'created_at' => $this->created_at->format('d/m/Y H:i'),

            // Relations chargées uniquement si présentes (Eager Loading)
            'user' => new UserResource($this->whenLoaded('user')),
            'operator' => new OperatorResource($this->whenLoaded('operator')),

            // Exemple de badge de statut personnalisé pour le frontend
            'status_label' => $this->getStatusLabel(),
        ];
    }

    /**
     * Helper optionnel pour formater le statut côté serveur si besoin
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
        'completed' => 'Terminé',
            'pending'   => 'En attente',
            'failed'    => 'Échoué',
            default     => 'Inconnu',
        };
    }
}

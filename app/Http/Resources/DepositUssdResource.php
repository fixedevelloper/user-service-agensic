<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositUssdResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'amount'       => $this->amount,
            'currency'     => $this->currency,
            'country_code' => $this->country_code,
            'ussd_code'    => $this->ussd_code,
            'reference'    => $this->reference,
            'status'       => $this->status,
            'admin_note'   => $this->admin_note,

            // Gestion de la preuve (URL complète pour l'affichage)
            'proof_url'    => $this->proof ? asset($this->proof) : null,

            // Formatage des dates
            'completed_at' => $this->completed_at ? $this->completed_at->format('d/m/Y H:i') : null,
            'created_at'   => $this->created_at->format('d/m/Y H:i'),

            // Relation avec l'utilisateur
            'user' => new UserResource($this->whenLoaded('user')),

            // Helpers pour le Dashboard Next.js
            'ui_metadata' => [
                'status_color' => $this->getStatusColor(),
                'can_approve'  => $this->status === 'pending',
                'full_amount'  => number_format($this->amount, 2) . ' ' . $this->currency,
            ],
        ];
    }

    /**
     * Retourne une couleur adaptée au statut pour vos badges Tailwind
     */
    private function getStatusColor(): string
    {
        return match($this->status) {
        'completed' => 'text-emerald-600 bg-emerald-50',
            'pending'   => 'text-amber-600 bg-amber-50',
            'failed'    => 'text-rose-600 bg-rose-50',
            'rejected'  => 'text-slate-600 bg-slate-50',
            default     => 'text-blue-600 bg-blue-50',
        };
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'iso'        => $this->iso,
            'iso3'       => $this->iso3,
            'phone_code' => $this->phonecode,
            'currency'   => $this->currency,
            'status'     => $this->status,

            // URL complète du drapeau via l'accessor
            'flag'       => $this->flag_url,

            // Relations (chargées à la demande)
            'operators' => OperatorResource::collection($this->whenLoaded('operators')),

            // Statistiques utiles pour le dashboard
            'stats' => [
                'operators_count' => $this->whenCounted('operators'),
                'deposits_count'  => $this->whenCounted('deposits'),
            ],

            // Métadonnées pour l'affichage Next.js
            'display_label' => "({$this->iso}) {$this->name}",
            'is_active'     => $this->status === 'active',
        ];
    }
}

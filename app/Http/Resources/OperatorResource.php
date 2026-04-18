<?php


namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperatorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'code'   => $this->code,
            'status' => $this->status,

            // On utilise l'attribut calculé (Accessor) pour l'URL du logo
            'logo'   => $this->logo_url,

            // Relation avec le pays, chargée uniquement si nécessaire
            'country' => new CountryResource($this->whenLoaded('country')),

            // Optionnel : On peut aussi compter les dépôts sans charger toute la collection
            'deposits_count' => $this->whenCounted('deposits'),

            // Pour le style dans votre Dashboard
            'status_metadata' => [
                'is_active' => $this->status === 'active',
                'label'     => $this->status === 'active' ? 'Opérationnel' : 'Hors-ligne',
                'color'     => $this->status === 'active' ? 'text-emerald-500' : 'text-slate-400',
            ],
        ];
    }
}

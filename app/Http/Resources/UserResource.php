<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
public function toArray($request)
{
    return [
        'id'      => $this->id,
        'name'    => $this->name,
        'phone'   => $this->phone,
        'email'   => $this->email,
        'balance' => $this->balance ?? 0, // Valeur par défaut
        'role'    => $this->role,

        // Utilisation de l'opérateur null-safe (?->) pour éviter les crashs
        'country' => [
            'id'   => $this->country?->id,
            'name' => $this->country?->name,
            'code' => $this->country?->iso,
        ],

        // KYC : Mappage sécurisé
        'identification' => [
            'number'  => $this->kyc?->doc_reference,
            'type'    => $this->kyc?->doc_type,
            'expired' => $this->kyc?->proof_address, // Attention au nommage ici si c'est vraiment l'expiration
        ],

        'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
    ];
}
}

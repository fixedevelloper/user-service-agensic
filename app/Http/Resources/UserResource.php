<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'balance' => $this->balance,

            'role' => $this->role,

            'country_id' => $this->country->id,
            'country_name' => $this->country->name,
            'country_code' => $this->country->iso,
              'identification_number' => $this->kyc->doc_reference,
                        'identification_type' =>  $this->kyc->doc_type,
                        'identification_expired' =>  $this->kyc->proof_address,
            'created_at' => $this->created_at,
        ];
    }
}

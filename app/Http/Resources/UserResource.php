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

            'country_id' => $this->country_id,
            'country_name' => $this->country_name,
            'country_code' => $this->country_code,

            'created_at' => $this->created_at,
        ];
    }
}

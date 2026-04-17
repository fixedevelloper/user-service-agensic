<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'identifier',
        'code',
        'used',
        'expires_at',
    ];

    /**
     * Vérifie si le code est valide (non utilisé et non expiré)
     */
    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }
}

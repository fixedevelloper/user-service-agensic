<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'phone', 'password', 'role', 'is_active', 'last_login_at','name','country_id','email','balance'
    ];

    protected $hidden = [
        'password',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
  public function kyc()
{
    // HasOne si un utilisateur n'a qu'un seul dossier KYC
    // HasMany si vous gardez l'historique de tous ses envois
    return $this->hasOne(KycDocument::class, 'user_id');
}
}

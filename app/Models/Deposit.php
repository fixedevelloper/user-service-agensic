<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory;

    protected $table = 'deposits';

    protected $fillable = [
        'amount', 'operator_id', 'user_id', 'reference', 'status', 'completed_at','provider_token'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    // Relation : un dépôt appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation : un dépôt appartient à un opérateur
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}

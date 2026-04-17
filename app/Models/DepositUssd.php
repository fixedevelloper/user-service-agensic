<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositUssd extends Model
{
    protected $fillable = [
        'amount',
        'currency',
        'country_code',
        'ussd_code',
        'user_id',
        'reference',
        'proof',
        'status',
        'admin_note',
        'completed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
        'is_default' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

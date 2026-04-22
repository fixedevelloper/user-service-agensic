<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocument extends Model
{
    use HasFactory;

    // Pas de timestamps standards (created_at/updated_at) car tu utilises submitted_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'doc_type',
        'doc_reference',
        'front_image',
        'back_image',
        'selfie_image',
        'proof_address',
        'status',
        'notes',
        'submitted_at',
        'verified_at',
        'reviewer_id',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'reviewer_id' => 'integer',
    ];

    /**
     * Relation avec l'utilisateur propriétaire du document
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec l'administrateur qui a vérifié le document
     */
    public function reviewer(): BelongsTo
    {
        // On suppose que tes admins sont aussi dans la table users
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // =========================================================================
    // SCOPES (Pour faciliter les requêtes)
    // =========================================================================

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'REJECTED');
    }

    // =========================================================================
    // ACCESSORS (Pour les URLs des images)
    // =========================================================================

    /**
     * Récupère l'URL complète du recto
     */
    public function getFrontImageUrlAttribute(): ?string
    {
        return $this->front_image ? asset('storage/' . $this->front_image) : null;
    }
}
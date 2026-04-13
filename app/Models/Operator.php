<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    use HasFactory;

    protected $table = 'operators';

    protected $fillable = [
        'name', 'logo', 'status', 'country_id','code'
    ];
    protected $appends = ['logo_url'];
    // Relation : un opérateur appartient à un pays
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    // Relation : un opérateur peut avoir plusieurs dépôts
    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }
    public function getLogoUrlAttribute()
    {
        return $this->logo
            ? asset('storage/' . $this->logo)
            : null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $table = 'countries';

    protected $fillable = [
        'name', 'iso', 'iso3', 'phonecode', 'currency', 'status','flag'
    ];
    protected $appends = ['flag_url'];
    // Relation : Un pays a plusieurs opérateurs
    public function operators()
    {
        return $this->hasMany(Operator::class);
    }

    // Relation : Un pays peut avoir plusieurs dépôts via opérateurs
    public function deposits()
    {
        return $this->hasManyThrough(Deposit::class, Operator::class);
    }
    public function getFlagUrlAttribute()
    {
        return $this->flag
            ? asset('storage/' . $this->flag)
            : null;
    }
}

<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'route',
        'is_active',
        'position',
        'category'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

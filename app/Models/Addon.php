<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'price',
        'sale_price',
        'price_calculation',
        'active_status',
    ];

    protected $casts = [
        'active_status' => 'boolean',
    ];
}

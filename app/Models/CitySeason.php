<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CitySeason extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id', 'name', 'months', 'weather', 'activities'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}

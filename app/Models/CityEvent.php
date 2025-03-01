<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id', 'name', 'type', 'date_time', 'location', 'description'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}

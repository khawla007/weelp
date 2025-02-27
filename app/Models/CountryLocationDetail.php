<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryLocationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id', 'latitude', 'longitude', 'capital_city',
        'population', 'currency', 'timezone', 'language', 'local_cuisine'
    ];

    public function country() {
        return $this->belongsTo(Country::class);
    }
}

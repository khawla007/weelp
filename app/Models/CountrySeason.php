<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountrySeason extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'name',
        'months',
        'weather',
        'activities',
    ];

    protected $casts = [
        'activities' => 'array',
    ];

    public function country() {
        return $this->belongsTo(Country::class);
    }
}

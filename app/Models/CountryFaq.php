<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'question_number',
        'question',
        'answer',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}

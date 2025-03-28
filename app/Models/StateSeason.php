<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateSeason extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'name',
        'months',
        'weather',
        'activities',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}

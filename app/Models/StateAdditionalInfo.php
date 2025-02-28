<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateAdditionalInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'title',
        'content',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}

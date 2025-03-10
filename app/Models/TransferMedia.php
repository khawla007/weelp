<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'media_type', // 'image' or 'video'
        'media_url',
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}

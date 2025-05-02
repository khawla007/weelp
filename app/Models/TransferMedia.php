<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferMedia extends Model
{
    use HasFactory;
    protected $fillable = [
        'transfer_id',
        'media_type',
        'media_id',
    ];

    // Relationship with Transfer
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
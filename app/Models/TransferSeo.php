<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferSeo extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'meta_title',
        'meta_description',
        'keywords',
        'og_image_url',
        'canonical_url',
        'schema_type',
        'schema_data',
    ];

    protected $casts = [
        'keywords' => 'array', // Converts comma-separated keywords into an array
        'schema_data' => 'json', // Stores JSON-LD data
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}

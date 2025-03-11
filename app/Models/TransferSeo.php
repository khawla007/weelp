<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferSeo extends Model
{
    use HasFactory;

    protected $table = 'transfer_seo';
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

    // protected $casts = [
    //     'keywords' => 'array', // Converts comma-separated keywords into an array
    //     'schema_data' => 'json', // Stores JSON-LD data
    // ];

    public function setSchemaDataAttribute($value)
    {
        $this->attributes['schema_data'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    // Retrieve JSON as array
    public function getSchemaDataAttribute($value)
    {
        return json_decode($value, true);
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}

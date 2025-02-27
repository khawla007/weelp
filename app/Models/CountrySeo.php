<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountrySeo extends Model
{
    use HasFactory;

    protected $table = 'country_seo';
    
    protected $fillable = [
        'country_id',
        'meta_title',
        'meta_description',
        'keywords',
        'og_image_url',
        'canonical_url',
        'schema_type',
        'schema_data',
    ];

    // protected $casts = [
    //     'schema_data' => 'array',
    // ];

    public function country() {
        return $this->belongsTo(Country::class);
    }

}

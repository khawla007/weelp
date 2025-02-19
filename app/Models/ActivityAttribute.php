<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'values',
        'default_value',
        'taxonomy',
        'post_type',
    ];

    protected $casts = [
        'values' => 'array', // Automatically handles JSON conversion
    ];

    /**
     * Automatically set the slug and taxonomy before saving.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($attribute) {
            $slug = Str::slug($attribute->name, '-');
            $attribute->slug = $slug;
            $attribute->taxonomy = 'act_' . $slug;
        });

        static::updating(function ($attribute) {
            $slug = Str::slug($attribute->name, '-');
            $attribute->slug = $slug;
            $attribute->taxonomy = 'act_' . $slug;
        });
    }
}

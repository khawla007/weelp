<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityTag extends Model
{
    protected $fillable = ['name', 'slug', 'taxonomy', 'post_type'];

    // Automatically generate slug when creating or updating
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($tag) {
            $tag->slug = Str::slug(str_replace(' ', '_', strtolower($tag->name)), '_');
        });
    }
}

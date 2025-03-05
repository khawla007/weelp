<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'taxonomy', 'post_type', 'parent_id'];

    // Automatically generate slug when creating or updating
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($category) {
            $category->slug = Str::slug(str_replace(' ', '_', strtolower($category->name)), '_');
        });
    }
}

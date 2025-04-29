<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'content', 'publish', 'featured_image', 'category_id', 'tag_id', 'excerpt', 'activity_id',
    ];

    protected $casts = [
        'publish' => 'boolean',
    ];

    public function media()
    {
        return $this->belongsTo(Media::class, 'featured_image');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['name', 'alt_text', 'url'];

    public function blogs()
    {
        return $this->hasMany(Blog::class, 'featured_image');
    }
    
}

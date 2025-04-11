<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityMediaGallery extends Model
{
    protected $table = 'activity_media_gallery';

    protected $fillable = [
        'activity_id', 'url'
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}

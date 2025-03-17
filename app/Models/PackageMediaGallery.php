<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageMediaGallery extends Model
{

    protected $table = 'package_media_gallery';

    protected $fillable = [
        'package_id', 'url'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}

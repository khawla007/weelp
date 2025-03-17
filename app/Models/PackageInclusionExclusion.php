<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageInclusionExclusion extends Model
{

    protected $table = 'package_inclusions_exclusions';

    protected $fillable = [
        'package_id', 'type', 'title', 
        'description', 'include_exclude'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}

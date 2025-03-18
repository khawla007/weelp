<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'item_type',
        'featured',
        'private',
    ];

    public function locations() {

        return $this->hasMany(PackageLocation::class);
    }

    public function information()
    {
        return $this->hasMany(PackageInformation::class);
    }

    public function schedules()
    {
        return $this->hasMany(PackageSchedule::class);
    }

    public function basePricing()
    {
        return $this->hasOne(PackageBasePricing::class, 'package_id');
    }

    public function inclusionsExclusions()
    {
        return $this->hasMany(PackageInclusionExclusion::class);
    }

    // Category relation
    public function categories()
    {
        return $this->hasMany(PackageCategory::class);
    }

    // Attribute relation
    public function attributes()
    {
        return $this->hasMany(PackageAttribute::class);
    }

    // Tag relation
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'package_tags');
    }

    public function faqs()
    {
        return $this->hasMany(PackageFaq::class);
    }

    public function seo()
    {
        return $this->hasOne(PackageSeo::class);
    }

    public function availability()
    {
        return $this->hasOne(PackageAvailability::class);
    }

    public function mediaGallery()
    {
        return $this->hasMany(PackageMediaGallery::class);
    }
}

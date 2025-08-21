<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['name', 'alt_text', 'url'];

    public function countryMedia()
    {
        return $this->hasMany(CountryMediaGallery::class, 'media_id');
    }

    public function stateMedia()
    {
        return $this->hasMany(StateMediaGallery::class, 'media_id');
    }

    public function cityMedia()
    {
        return $this->hasMany(CityMediaGallery::class, 'media_id');
    }
    
    public function blogs()
    {
        return $this->hasMany(Blog::class, 'featured_image');
    }

    public function itineraryMedia()
    {
        return $this->hasMany(ItineraryMediaGallery::class, 'media_id');
    }

    public function packageMedia()
    {
        return $this->hasMany(PackageMediaGallery::class, 'media_id');
    }

    public function activityMedia()
    {
        return $this->hasMany(ActivityMediaGallery::class, 'media_id');
    }

    public function transferMedia()
    {
        return $this->hasMany(TransferyMediaGallery::class, 'media_id');
    }
    
}

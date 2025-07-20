<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Photographer;

class PhotographerSocialNetwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', // Name of the social network
        'url', // URL of the social network profile
        'photographer_id', // Foreign key to the photographer
    ];

    public function photographer()
    {
        return $this->belongsTo(Photographer::class);
    }
}

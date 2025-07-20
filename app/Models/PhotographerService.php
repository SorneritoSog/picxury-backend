<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Photographer;
use App\Models\Service;
use App\Models\PhotoSessionPhotographerService;

class PhotographerService extends Model
{
    use HasFactory;

    protected $fillable = [
        'photographer_id',
        'service_id',
        'price',
    ];

    public function photographer()
    {
        return $this->belongsTo(Photographer::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function photoSessionPhotographerServices()
    {
        return $this->hasMany(PhotoSessionPhotographerService::class);
    }
}

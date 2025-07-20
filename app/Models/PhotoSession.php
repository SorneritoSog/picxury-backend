<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Photographer;
use App\Models\Client;
use App\Models\PhotoSessionType;
use App\Models\Notification;
use App\Models\Album;
use App\Models\PhotoSessionPhotographerService;

class PhotoSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'photographer_id',
        'client_id',
        'status',
        'total_price',
        'payment_status',
        'title',
        'date',
        'start_time',
        'end_time',
        'department',
        'city',
        'address',
        'place_description',
        'photo_session_type_id',
    ];

    public function photographer()
    {
        return $this->belongsTo(Photographer::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function type()
    {
        return $this->belongsTo(PhotoSessionType::class, 'photo_session_type_id');
    }

    public function albums()
    {
        return $this->hasMany(Album::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function photoSessionPhotographerServices()
    {
        return $this->hasMany(PhotoSessionPhotographerService::class);
    }
}

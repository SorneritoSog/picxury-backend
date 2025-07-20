<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PhotoSession;
use App\Models\PhotographerService;

class PhotoSessionPhotographerService extends Model
{
    use HasFactory;

    protected $fillable = [
        'photo_session_id',
        'photographer_service_id',
        'quantity',
        'unit_price',
    ];

    public function photoSession()
    {
        return $this->belongsTo(PhotoSession::class);
    }

    public function photographerService()
    {
        return $this->belongsTo(PhotographerService::class, 'photographer_service_id');
    }
}

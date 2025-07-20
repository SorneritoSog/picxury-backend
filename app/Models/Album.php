<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PhotoSession;

class Album extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'code',
        'photo_session_id',
    ];

    public function photoSession()
    {
        return $this->belongsTo(PhotoSession::class);
    }

    public function albumPhotos()
    {
        return $this->hasMany(AlbumPhoto::class);
    }
}

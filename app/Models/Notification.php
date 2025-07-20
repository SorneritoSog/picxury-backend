<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PhotoSession;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'photo_session_id',
        'title',
        'is_read',
    ];

    public function photoSession()
    {
        return $this->belongsTo(PhotoSession::class);
    }
}

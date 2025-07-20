<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PhotoSession;

class PhotoSessionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function photoSessions()
    {
        return $this->hasMany(PhotoSession::class);
    }
}

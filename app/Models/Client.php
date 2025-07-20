<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PhotoSession;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'last_name',
        'phone_number',
        'email',
    ];

    public function photoSessions()
    {
        return $this->hasMany(PhotoSession::class);
    }
}

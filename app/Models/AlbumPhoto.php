<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Album;

class AlbumPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'album_id',
        'url',
        'is_selected',
        'edition_type',
    ];

    public function album()
    {
        return $this->belongsTo(Album::class);
    }
}

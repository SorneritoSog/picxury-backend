<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Photographer;

class FinancialMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'photographer_id',
        'type',
        'category',
        'amount',
        'detail',
        'photo_session_id'
    ];

    public function photographer()
    {
        return $this->belongsTo(Photographer::class);
    }
}

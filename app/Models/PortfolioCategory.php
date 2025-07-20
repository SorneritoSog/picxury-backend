<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Photographer;
use App\Models\PortfolioPhoto;

class PortfolioCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'photographer_id',
    ];

    public function photographer()
    {
        return $this->belongsTo(Photographer::class);
    }

    public function portfolioItems()
    {
        return $this->hasMany(PortfolioPhoto::class, 'portfolio_category_id');
    }
}

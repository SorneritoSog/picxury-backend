<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PortfolioCategory;

class PortfolioPhoto extends Model
{
    use HasFactory;

    protected $table = 'portfolio_photo';

    protected $fillable = [
        'url',
        'portfolio_category_id',
    ];

    public function portfolioCategory()
    {
        return $this->belongsTo(PortfolioCategory::class);
    }
}

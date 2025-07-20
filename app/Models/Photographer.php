<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\PhotoSession;
use App\Models\PortfolioCategory;
use App\Models\FinancialMovement;
use App\Models\PhotographerService;
use App\Models\PhotographerSocialNetwork;

class Photographer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'last_name',
        'phone_number',
        'department',
        'city',
        'personal_description',
        'start_time_of_attention',
        'end_time_of_attention',
        'price_per_hour',
        'email',
        'password',
        'profile_picture',
        'active',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function photoSessions()
    {
        return $this->hasMany(PhotoSession::class);
    }

    public function portfolioCategories()
    {
        return $this->hasMany(PortfolioCategory::class);
    }

    public function financialMovements()
    {
        return $this->hasMany(FinancialMovement::class);
    }

    public function photographerServices()
    {
        return $this->hasMany(PhotographerService::class);
    }

    public function socialNetworks()
    {
        return $this->hasMany(PhotographerSocialNetwork::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'name',
        'phone',
        'email',
        'address',
        'loyalty_card_number',
        'total_purchases',
        'total_due',
        'is_active'
    ];

    protected $casts = [
        'total_purchases' => 'decimal:2',
        'total_due' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function getLoyaltyLevelAttribute()
    {
        if ($this->total_purchases >= 50000) return 'Platinum';
        if ($this->total_purchases >= 25000) return 'Gold';
        if ($this->total_purchases >= 10000) return 'Silver';
        return 'Regular';
    }
}
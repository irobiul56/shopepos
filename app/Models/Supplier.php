<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'name',
        'company_name',
        'phone',
        'email',
        'address',
        'total_due',
        'is_active'
    ];

    protected $casts = [
        'total_due' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
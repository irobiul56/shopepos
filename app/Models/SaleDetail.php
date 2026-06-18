<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the sale that owns the detail
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the product for this sale detail
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate profit for this sale detail
     */
    public function getProfitAttribute()
    {
        $product = $this->product;
        if ($product) {
            $costPrice = $product->purchase_price * $this->quantity;
            return $this->total_price - $costPrice;
        }
        return 0;
    }

    /**
     * Get the margin percentage
     */
    public function getMarginPercentageAttribute()
    {
        if ($this->total_price > 0) {
            $profit = $this->profit;
            return ($profit / $this->total_price) * 100;
        }
        return 0;
    }

    /**
     * Boot method to auto-calculate total price
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate total price before saving
        static::saving(function ($saleDetail) {
            $saleDetail->total_price = $saleDetail->quantity * $saleDetail->unit_price;
        });
    }
}
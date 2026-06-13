<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'category_id',
        'brand_id',
        'unit_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'purchase_price',
        'selling_price',
        'wholesale_price',
        'selling_price_with_tax',
        'stock_quantity',
        'min_stock_alert',
        'tax_rate',
        'is_taxable',
        'description',
        'product_image',
        'gallery_images',
        'is_active'
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'selling_price_with_tax' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock_alert' => 'integer',
        'tax_rate' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
        'gallery_images' => 'array'
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getMainImageUrlAttribute()
    {
        if ($this->product_image) {
            return Storage::url($this->product_image);
        }
        return null;
    }

    public function getGalleryImagesUrlAttribute()
    {
        if ($this->gallery_images) {
            return collect($this->gallery_images)->map(function ($image) {
                return Storage::url($image);
            })->toArray();
        }
        return [];
    }
}
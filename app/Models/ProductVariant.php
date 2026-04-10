<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'color', 'color_hex', 'size', 'sku',
        'price', 'sale_price', 'stock', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function images() {
        return $this->hasMany(ProductVariantImage::class, 'variant_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Đơn giá tính theo cùng quy tắc với OrderService / CartItemResource (không có cột line_total trong DB).
     */
    public function getComputedUnitPrice(): float
    {
        $this->loadMissing(['product', 'variant']);

        $product = $this->product;
        $variant = $this->variant;

        if (!$product) {
            return 0.0;
        }

        $variantPrice = 0.0;
        if ($variant) {
            $variantPrice = $variant->sale_price && (float) $variant->sale_price > 0
                ? (float) $variant->sale_price
                : (float) $variant->price;
        }

        $productBasePrice = $product->base_sale_price && (float) $product->base_sale_price > 0
            ? (float) $product->base_sale_price
            : (float) $product->base_price;

        $unitPrice = $variantPrice > 0 ? $variantPrice : $productBasePrice;

        return max(0.0, $unitPrice);
    }

    public function getComputedLineTotal(): float
    {
        return round($this->getComputedUnitPrice() * (int) $this->quantity, 2);
    }
}
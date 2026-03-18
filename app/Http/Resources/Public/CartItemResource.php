<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');
        $variant = $this->whenLoaded('variant');

        // giá ưu tiên variant trước, fallback product
        $unitPrice = null;

        if ($variant) {
            $unitPrice = $variant->sale_price ?? $variant->price ?? null;
        }
        if ($unitPrice === null && $product) {
            $unitPrice = $product->base_sale_price ?? $product->base_price ?? 0;
        }

        $qty = (int) $this->quantity;

        return [
            'id' => $this->id,

            'product_id' => $this->product_id,
            'variant_id' => $this->product_variant_id,

            'quantity' => $qty,

            'unit_price' => (int) $unitPrice,
            'line_total' => (int) $unitPrice * $qty,

            'product' => $product ? [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'thumbnail' => $product->thumbnail,
            ] : null,

            'variant' => $variant ? [
                'id' => $variant->id,
                'color' => $variant->color,
                'size' => $variant->size,
                'price' => (int) ($variant->price ?? 0),
                'sale_price' => $variant->sale_price !== null ? (int) $variant->sale_price : null,
                'is_active' => (bool) ($variant->is_active ?? true),
                'stock' => $variant->stock !== null ? (int) $variant->stock : null,
            ] : null,
        ];
    }
}
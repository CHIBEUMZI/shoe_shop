<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'base_price' => $this->base_price !== null ? (int) $this->base_price : null,
            'base_sale_price' => $this->base_sale_price !== null ? (int) $this->base_sale_price : null,
            'thumbnail' => $this->thumbnail,
            'status' => (int) $this->status,
            'is_featured' => (bool) $this->is_featured,
            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories
                    ->sortBy('name')
                    ->values()
                    ->map(fn ($c) => [
                        'id' => (int) $c->id,
                        'name' => $c->name,
                        'slug' => $c->slug,
                    ]);
            }),
            'category_ids' => $this->whenLoaded('categories', function () {
                return $this->categories->pluck('id')->map(fn ($id) => (int) $id)->values();
            }),

            'variants' => $this->whenLoaded('variants', function () {
                return $this->variants->values()->map(function ($v) {
                    return [
                        'id' => (int) $v->id,
                        'color' => $v->color,
                        'color_hex' => $v->color_hex,
                        'size' => $v->size,
                        'sku' => $v->sku,

                        'price' => $v->price !== null ? (int) $v->price : null,
                        'sale_price' => $v->sale_price !== null ? (int) $v->sale_price : null,
                        'stock' => $v->stock !== null ? (int) $v->stock : 0,
                        'is_active' => (bool) $v->is_active,

                        'images' => $v->relationLoaded('images')
                            ? $v->images->sortBy('sort_order')->values()->map(fn ($img) => [
                                'id' => (int) $img->id,
                                'url' => $img->url,
                                'sort_order' => (int) ($img->sort_order ?? 0),
                            ])
                            : [],
                    ];
                });
            }),
        ];
    }
}
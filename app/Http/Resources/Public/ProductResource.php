<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null),

            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,

            'short_description' => $this->short_description,
            'description' => $this->description,

            'base_price' => $this->base_price,
            'base_sale_price' => $this->base_sale_price,
            'thumbnail' => $this->thumbnail,
            'categories' => $this->whenLoaded('categories', fn () =>
                $this->categories->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                ])->values()
            ),
            'variants' => $this->whenLoaded('variants', fn () =>
                $this->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'color' => $v->color,
                    'color_hex' => $v->color_hex,
                    'size' => $v->size,
                    'price' => $v->price,
                    'sale_price' => $v->sale_price,
                    'stock' => $v->stock,
                    'images' => $v->relationLoaded('images')
                        ? $v->images->sortBy('sort_order')->values()->map(fn ($img) => [
                            'id' => $img->id,
                            'url' => $img->url,
                            'sort_order' => (int) $img->sort_order,
                        ])
                        : [],
                ])->values()
            ),
        ];
    }
}
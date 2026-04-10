<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // tuỳ bạn gắn policy
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],

            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],

            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string', 'max:255'],

            'status' => ['required', 'integer', 'in:0,1'],
            'is_featured' => ['sometimes', 'boolean'],

            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'variants' => ['required', 'array', 'min:1'],

            'variants.*.color' => ['required', 'string', 'max:100'],
            'variants.*.color_hex' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'variants.*.size' => ['required', 'string', 'max:50'],
            'variants.*.sku' => ['nullable', 'string', 'max:255', 'distinct'], 
            'variants.*.price' => ['required', 'integer', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'integer', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.is_active' => ['sometimes', 'boolean'],

            'variants.*.images' => ['sometimes', 'array'],
            'variants.*.images.*.url' => ['required_with:variants.*.images', 'string', 'max:255'],
            'variants.*.images.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
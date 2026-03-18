<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $productId = $product?->id;

        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($productId),
            ],

            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string', 'max:255'],

            'status' => ['required', 'integer', 'in:0,1'],
            'is_featured' => ['sometimes', 'boolean'],

            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'variants' => ['sometimes', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer'],

            'variants.*.color' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.size'  => ['required_with:variants', 'string', 'max:50'],
            'variants.*.sku' => ['nullable', 'string', 'max:255', 'distinct'],

            'variants.*.price' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'integer', 'min:0'],
            'variants.*.stock' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.is_active' => ['sometimes', 'boolean'],

            'variants.*.images' => ['sometimes', 'array'],
            'variants.*.images.*.url' => ['required_with:variants.*.images', 'string', 'max:255'],
            'variants.*.images.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $variants = $this->input('variants', null);
            if (!is_array($variants)) return;
            $seen = [];
            foreach ($variants as $idx => $row) {
                $color = strtolower(trim((string)($row['color'] ?? '')));
                $size  = strtolower(trim((string)($row['size'] ?? '')));
                if ($color === '' || $size === '') continue;

                $key = $color . '|' . $size;
                if (isset($seen[$key])) {
                    $v->errors()->add('variants', "Trùng biến thể color+size tại dòng ".($seen[$key] + 1)." và ".($idx + 1));
                    break;
                }
                $seen[$key] = $idx;
            }
        });
    }
}
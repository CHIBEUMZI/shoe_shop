<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'type_label' => $this->type === 'percentage' ? 'Phần trăm' : 'Số tiền cố định',
            'value' => (float) $this->value,
            'value_formatted' => $this->type === 'percentage'
                ? $this->value . '%'
                : number_format($this->value, 0, ',', '.') . 'đ',
            'max_discount' => $this->max_discount ? (float) $this->max_discount : null,
            'max_discount_formatted' => $this->max_discount
                ? number_format($this->max_discount, 0, ',', '.') . 'đ'
                : null,
            'min_order_amount' => $this->min_order_amount ? (float) $this->min_order_amount : null,
            'min_order_amount_formatted' => $this->min_order_amount
                ? number_format($this->min_order_amount, 0, ',', '.') . 'đ'
                : null,
            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,
            'usage_remaining' => $this->usage_limit !== null
                ? $this->usage_limit - $this->used_count
                : null,
            'per_user_limit' => $this->per_user_limit,
            'starts_at' => $this->starts_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'is_active' => (bool) $this->is_active,
            'status' => $this->getStatusLabel(),
            'applicable_to' => $this->applicable_to,
            'applicable_to_label' => $this->getApplicableToLabel(),
            'applicable_ids' => $this->applicable_ids ?? [],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getStatusLabel(): string
    {
        if (!$this->is_active) {
            return 'Vô hiệu hóa';
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return 'Chưa bắt đầu';
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return 'Đã hết hạn';
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return 'Đã dùng hết';
        }

        return 'Hoạt động';
    }

    protected function getApplicableToLabel(): string
    {
        return match ($this->applicable_to) {
            'all' => 'Tất cả sản phẩm',
            'specific_products' => 'Sản phẩm cụ thể',
            'specific_categories' => 'Danh mục cụ thể',
            'specific_brands' => 'Thương hiệu cụ thể',
            default => 'Tất cả sản phẩm',
        };
    }
}

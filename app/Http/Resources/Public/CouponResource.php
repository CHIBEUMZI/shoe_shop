<?php

namespace App\Http\Resources\Public;

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
            'type_label' => $this->type === 'percentage' ? 'Giảm %' : 'Giảm tiền',
            'value' => (float) $this->value,
            'value_formatted' => $this->type === 'percentage'
                ? $this->value . '%'
                : number_format($this->value, 0, ',', '.') . 'đ',
            'max_discount' => $this->max_discount ? (float) $this->max_discount : null,
            'min_order_amount' => $this->min_order_amount ? (float) $this->min_order_amount : null,
            'expires_at' => $this->expires_at?->toISOString(),
            'expires_at_formatted' => $this->expires_at?->format('d/m/Y'),
            'applicable_to' => $this->applicable_to,
        ];
    }
}

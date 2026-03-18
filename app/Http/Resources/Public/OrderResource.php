<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'user_id' => $this->user_id,

            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,

            'province' => $this->province,
            'district' => $this->district,
            'ward' => $this->ward,
            'address_line' => $this->address_line,
            'note' => $this->note,

            'shipping_method' => $this->shipping_method,
            'shipping_fee' => $this->shipping_fee,

            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,

            'status' => $this->status,

            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'grand_total' => $this->grand_total,

            'paid_at' => optional($this->paid_at)?->toDateTimeString(),
            'stock_deducted_at' => optional($this->stock_deducted_at)?->toDateTimeString(),

            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),

            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}
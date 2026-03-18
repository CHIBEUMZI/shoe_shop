<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = CartItemResource::collection($this->whenLoaded('items'))->resolve();

        $itemsCount = 0;
        $quantitySum = 0;
        $subtotal = 0;

        foreach ($items as $it) {
            $itemsCount++;
            $quantitySum += (int)($it['quantity'] ?? 0);
            $subtotal += (int)($it['line_total'] ?? 0);
        }

        $discountTotal = 0;
        $shippingFee = 0;
        $grandTotal = $subtotal - $discountTotal + $shippingFee;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,

            'items' => $items,

            'summary' => [
                'items_count' => (int) $itemsCount,
                'quantity_sum' => (int) $quantitySum,

                'subtotal' => (int) $subtotal,
                'discount_total' => (int) $discountTotal,
                'shipping_fee' => (int) $shippingFee,
                'grand_total' => (int) $grandTotal,
            ],

            'updated_at' => optional($this->updated_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
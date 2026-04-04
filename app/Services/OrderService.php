<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\UserCoupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    public function createFromCart($user, array $data): Order
    {
        $cart = Cart::query()
            ->with([
                'items.product',
                'items.product.categories',
                'items.variant',
            ])
            ->where('user_id', $user->id)
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            throw new RuntimeException('Giỏ hàng đang trống.');
        }

        $coupon = null;
        $discountTotal = 0;
        $couponCode = $data['coupon_code'] ?? null;

        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon) {
                $couponService = app(CouponService::class);
                $validation = $couponService->validate($couponCode, $user->id);

                if (!$validation['valid']) {
                    throw new RuntimeException($validation['message']);
                }

                $discountResult = $couponService->calculateDiscountForCart($coupon, $cart);
                $discountTotal = $discountResult['discount'];
            }
        }

        return DB::transaction(function () use ($user, $data, $cart, $coupon, $discountTotal) {
            $subtotal = 0;

            $shippingFee = ($data['shipping_method'] ?? 'standard') === 'express' ? 30000 : 15000;

            $preparedItems = [];

            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;
                $variant = $cartItem->variant;

                if (!$product) {
                    throw new RuntimeException('Sản phẩm không tồn tại.');
                }

                if ($product->trashed()) {
                    throw new RuntimeException("Sản phẩm {$product->name} đã ngừng kinh doanh.");
                }

                if ((int) $product->status !== 1) {
                    throw new RuntimeException("Sản phẩm {$product->name} hiện không khả dụng.");
                }

                if (!$variant) {
                    throw new RuntimeException("Biến thể của sản phẩm {$product->name} không tồn tại.");
                }

                if ((int) $variant->product_id !== (int) $product->id) {
                    throw new RuntimeException("Biến thể của sản phẩm {$product->name} không hợp lệ.");
                }

                if (!$variant->is_active) {
                    throw new RuntimeException("Biến thể của sản phẩm {$product->name} hiện không khả dụng.");
                }

                if ((int) $variant->stock < (int) $cartItem->quantity) {
                    throw new RuntimeException("Sản phẩm {$product->name} không đủ tồn kho.");
                }

                $variantPrice = $variant->sale_price && (float) $variant->sale_price > 0
                    ? (float) $variant->sale_price
                    : (float) $variant->price;

                $productBasePrice = $product->base_sale_price && (float) $product->base_sale_price > 0
                    ? (float) $product->base_sale_price
                    : (float) $product->base_price;

                $unitPrice = $variantPrice > 0 ? $variantPrice : $productBasePrice;
                $lineTotal = $unitPrice * (int) $cartItem->quantity;

                $subtotal += $lineTotal;

                $preparedItems[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'product_sku' => $product->sku,
                    'variant_sku' => $variant->sku,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'thumbnail' => $product->thumbnail,
                    'unit_price' => $unitPrice,
                    'quantity' => (int) $cartItem->quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $grandTotal = $subtotal + $shippingFee - $discountTotal;

            if ($grandTotal < 0) {
                $grandTotal = 0;
            }

            $paymentMethod = $data['payment_method'];
            $paymentStatus = $paymentMethod === 'cod' ? 'unpaid' : 'pending';

            $orderData = [
                'user_id' => $user->id,
                'code' => $this->generateOrderCode(),

                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,

                'province' => $data['province'] ?? null,
                'district' => $data['district'] ?? null,
                'ward' => $data['ward'] ?? null,
                'address_line' => $data['address_line'],

                'note' => $data['note'] ?? null,

                'shipping_method' => $data['shipping_method'],
                'shipping_fee' => $shippingFee,

                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'status' => 'pending',

                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'grand_total' => $grandTotal,
            ];

            if ($coupon) {
                $orderData['coupon_id'] = $coupon->id;
                $orderData['coupon_code'] = $coupon->code;
            }

            $order = Order::create($orderData);

            foreach ($preparedItems as $itemData) {
                $order->items()->create($itemData);
            }

            Payment::create([
                'order_id' => $order->id,
                'method' => $paymentMethod,
                'provider' => $paymentMethod,
                'status' => 'pending',
                'amount' => $grandTotal,
            ]);

            if ($coupon) {
                $coupon->increment('used_count');

                UserCoupon::where('user_id', $user->id)
                    ->where('coupon_id', $coupon->id)
                    ->whereNull('used_at')
                    ->update(['used_at' => now()]);
            }

            $cart->items()->delete();

            return $order->load(['items', 'payments', 'coupon']);
        });
    }

    protected function generateOrderCode(): string
    {
        return 'ORD' . now()->format('YmdHis') . strtoupper(Str::random(4));
    }
}
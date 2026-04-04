<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;

class CouponService
{
    public function validate(string $code, ?int $userId = null): array
    {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return [
                'valid' => false,
                'message' => 'Mã giảm giá không tồn tại.',
            ];
        }

        if (!$coupon->is_active) {
            return [
                'valid' => false,
                'message' => 'Mã giảm giá đã bị vô hiệu hóa.',
            ];
        }

        $now = now();

        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            return [
                'valid' => false,
                'message' => 'Mã giảm giá chưa có hiệu lực.',
            ];
        }

        if ($coupon->expires_at && $now->gt($coupon->expires_at)) {
            return [
                'valid' => false,
                'message' => 'Mã giảm giá đã hết hạn.',
            ];
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return [
                'valid' => false,
                'message' => 'Mã giảm giá đã được sử dụng hết.',
            ];
        }

        if ($userId !== null) {
            $userUsageCount = $coupon->orders()
                ->where('user_id', $userId)
                ->whereNotIn('status', ['cancelled'])
                ->count();

            if ($userUsageCount >= $coupon->per_user_limit) {
                return [
                    'valid' => false,
                    'message' => 'Bạn đã sử dụng mã giảm giá này rồi.',
                ];
            }
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'message' => 'Mã giảm giá hợp lệ.',
        ];
    }

    /**
     * Tính giảm giá từ giỏ hàng (dùng getComputedLineTotal — cart_items không có cột line_total).
     */
    public function calculateDiscountForCart(Coupon $coupon, ?Cart $cart): array
    {
        if (!$cart || $cart->items->isEmpty()) {
            return [
                'discount' => 0,
                'applicable_subtotal' => 0,
                'message' => 'Giỏ hàng trống.',
            ];
        }

        $cart->loadMissing([
            'items.product',
            'items.product.categories',
            'items.variant',
        ]);

        $subtotal = 0.0;
        foreach ($cart->items as $item) {
            $subtotal += $item->getComputedLineTotal();
        }

        if ($coupon->min_order_amount !== null && $subtotal < (float) $coupon->min_order_amount) {
            return [
                'discount' => 0,
                'applicable_subtotal' => 0,
                'message' => 'Đơn hàng tối thiểu ' . number_format((float) $coupon->min_order_amount, 0, ',', '.') . 'đ để áp dụng mã giảm giá.',
            ];
        }

        $applicableSubtotal = $subtotal;
        if ($coupon->applicable_to !== 'all') {
            $applicableSubtotal = 0.0;
            foreach ($cart->items as $item) {
                if ($this->cartItemMatchesCoupon($coupon, $item)) {
                    $applicableSubtotal += $item->getComputedLineTotal();
                }
            }
        }

        if ($applicableSubtotal <= 0 && $coupon->applicable_to !== 'all') {
            return [
                'discount' => 0,
                'applicable_subtotal' => 0,
                'message' => 'Mã giảm giá không áp dụng cho sản phẩm trong giỏ hàng.',
            ];
        }

        $discount = 0.0;
        if ($coupon->type === 'percentage') {
            $discount = $applicableSubtotal * ((float) $coupon->value / 100);
            if ($coupon->max_discount !== null) {
                $discount = min($discount, (float) $coupon->max_discount);
            }
        } else {
            $discount = (float) $coupon->value;
        }

        $discount = min($discount, $applicableSubtotal);

        return [
            'discount' => round($discount, 2),
            'applicable_subtotal' => round($applicableSubtotal, 2),
            'message' => $discount > 0
                ? 'Đã áp dụng giảm giá thành công.'
                : 'Mã giảm giá không áp dụng cho đơn hàng này.',
        ];
    }

    /**
     * @deprecated Dùng calculateDiscountForCart — itemProductIds + subtotal cũ sai vì cart không có line_total.
     */
    public function calculateDiscount(Coupon $coupon, float $subtotal, array $itemProductIds = []): array
    {
        $applicableSubtotal = $subtotal;

        if ($coupon->applicable_to !== 'all' && !empty($itemProductIds)) {
            $applicableSubtotal = $this->calculateApplicableSubtotal($coupon, $subtotal, $itemProductIds);
        }

        if ($coupon->min_order_amount !== null && $subtotal < (float) $coupon->min_order_amount) {
            return [
                'discount' => 0,
                'applicable_subtotal' => 0,
                'message' => 'Đơn hàng tối thiểu ' . number_format((float) $coupon->min_order_amount, 0, ',', '.') . 'đ để áp dụng mã giảm giá.',
            ];
        }

        $discount = 0.0;

        if ($coupon->type === 'percentage') {
            $discount = $applicableSubtotal * ((float) $coupon->value / 100);

            if ($coupon->max_discount !== null) {
                $discount = min($discount, (float) $coupon->max_discount);
            }
        } else {
            $discount = (float) $coupon->value;
        }

        $discount = min($discount, $applicableSubtotal);

        return [
            'discount' => round($discount, 2),
            'applicable_subtotal' => round($applicableSubtotal, 2),
            'message' => 'Đã áp dụng giảm giá thành công.',
        ];
    }

    protected function cartItemMatchesCoupon(Coupon $coupon, CartItem $item): bool
    {
        $ids = $this->normalizeApplicableIds($coupon->applicable_ids ?? []);

        return match ($coupon->applicable_to) {
            'all' => true,
            'specific_products' => in_array((int) $item->product_id, $ids, true),
            'specific_categories' => $this->productHasApplicableCategory($item, $ids),
            'specific_brands' => in_array((int) ($item->product?->brand_id ?? 0), $ids, true),
            default => true,
        };
    }

    protected function productHasApplicableCategory(CartItem $item, array $categoryIds): bool
    {
        if ($categoryIds === []) {
            return false;
        }

        $item->loadMissing('product.categories');
        $product = $item->product;
        if (!$product) {
            return false;
        }

        $productCategoryIds = $product->categories->pluck('id')->map(fn ($id) => (int) $id)->all();

        return count(array_intersect($productCategoryIds, $categoryIds)) > 0;
    }

    /**
     * @return int[]
     */
    protected function normalizeApplicableIds(array|string $ids): array
    {
        if (is_string($ids)) {
            try {
                $ids = json_decode($ids, true) ?? [];
            } catch (\Throwable) {
                $ids = [];
            }
        }

        if (!is_array($ids)) {
            $ids = [];
        }

        return array_values(array_map('intval', $ids));
    }

    protected function calculateApplicableSubtotal(Coupon $coupon, float $totalSubtotal, array $itemProductIds): float
    {
        if (empty($itemProductIds)) {
            return $totalSubtotal;
        }

        $applicableIds = $this->normalizeApplicableIds($coupon->applicable_ids ?? []);

        if ($applicableIds === []) {
            return $totalSubtotal;
        }

        $normalizedLineIds = array_map('intval', $itemProductIds);
        $matchingIds = array_intersect($normalizedLineIds, $applicableIds);

        if (empty($matchingIds)) {
            return 0;
        }

        $matchingCount = count($matchingIds);
        $totalCount = count($itemProductIds);

        return ($totalSubtotal / $totalCount) * $matchingCount;
    }

    public function applyToOrder(Coupon $coupon): void
    {
        $coupon->increment('used_count');
    }

    public function removeFromOrder(Coupon $coupon): void
    {
        if ($coupon->used_count > 0) {
            $coupon->decrement('used_count');
        }
    }
}

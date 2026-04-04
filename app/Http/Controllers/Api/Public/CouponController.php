<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\CouponResource;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\UserCoupon;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        protected CouponService $couponService
    ) {}

    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ], [
            'code.required' => 'Vui lòng nhập mã giảm giá.',
        ]);

        $user = $request->user();
        $userId = $user?->id;

        $result = $this->couponService->validate($request->input('code'), $userId);

        if (!$result['valid']) {
            return response()->json([
                'valid' => false,
                'message' => $result['message'],
            ], 422);
        }

        $coupon = $result['coupon'];

        $userId = $request->user()->id;
        $cart = Cart::query()
            ->where('user_id', $userId)
            ->with([
                'items.product',
                'items.product.categories',
                'items.variant',
            ])
            ->first();

        $discountResult = $this->couponService->calculateDiscountForCart($coupon, $cart);

        return response()->json([
            'valid' => true,
            'coupon' => new CouponResource($coupon),
            'discount' => $discountResult['discount'],
            'message' => $discountResult['message'],
        ]);
    }

    public function available(): JsonResponse
    {
        $user = request()->user();
        $userCouponIds = $user
            ? UserCoupon::where('user_id', $user->id)->pluck('coupon_id')->toArray()
            : [];

        $coupons = Coupon::active()
            ->valid()
            ->hasUsageRemaining()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $couponsWithClaimed = $coupons->map(function ($coupon) use ($userCouponIds) {
            $coupon->is_claimed = in_array($coupon->id, $userCouponIds);
            return $coupon;
        });

        return response()->json([
            'data' => CouponResource::collection($couponsWithClaimed),
        ]);
    }

    public function claim(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ], [
            'code.required' => 'Vui lòng nhập mã giảm giá.',
        ]);

        $code = $request->input('code');
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để nhận mã giảm giá.',
            ], 401);
        }

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không tồn tại.',
            ], 404);
        }

        if (!$coupon->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá đã bị vô hiệu hóa.',
            ], 422);
        }

        $now = now();
        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá chưa có hiệu lực.',
            ], 422);
        }

        if ($coupon->expires_at && $now->gt($coupon->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá đã hết hạn.',
            ], 422);
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá đã được sử dụng hết.',
            ], 422);
        }

        $alreadyClaimed = UserCoupon::where('user_id', $user->id)
            ->where('coupon_id', $coupon->id)
            ->exists();

        if ($alreadyClaimed) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã nhận mã giảm giá này rồi.',
            ], 422);
        }

        UserCoupon::create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'claimed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Nhận mã giảm giá thành công!',
            'data' => new CouponResource($coupon),
        ]);
    }

    public function myCoupons(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập.',
            ], 401);
        }

        $userCoupons = UserCoupon::with('coupon')
            ->where('user_id', $user->id)
            ->orderBy('claimed_at', 'desc')
            ->get()
            ->map(function ($userCoupon) {
                $coupon = $userCoupon->coupon;
                $now = now();

                $isExpired = $coupon->expires_at && $now->gt($coupon->expires_at);
                $isNotStarted = $coupon->starts_at && $now->lt($coupon->starts_at);
                $isUsable = !$isExpired && !$isNotStarted && $coupon->is_active;

                return [
                    'id' => $userCoupon->id,
                    'coupon' => new CouponResource($coupon),
                    'claimed_at' => $userCoupon->claimed_at->toIso8601String(),
                    'is_used' => $userCoupon->used_at !== null,
                    'is_expired' => $isExpired,
                    'is_not_started' => $isNotStarted,
                    'is_usable' => $isUsable,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $userCoupons,
        ]);
    }
}

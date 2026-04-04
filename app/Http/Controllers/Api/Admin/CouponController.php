<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CouponStoreRequest;
use App\Http\Requests\CouponUpdateRequest;
use App\Http\Resources\Admin\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Coupon::query()->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = $request->integer('per_page', 15);
        $coupons = $query->paginate($perPage);

        return response()->json([
            'data' => CouponResource::collection($coupons),
            'meta' => [
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
            ],
        ]);
    }

    public function store(CouponStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['applicable_ids']) && is_array($data['applicable_ids'])) {
            $data['applicable_ids'] = json_encode($data['applicable_ids']);
        }

        $coupon = Coupon::create($data);

        return response()->json([
            'message' => 'Tạo mã giảm giá thành công.',
            'data' => new CouponResource($coupon),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        return response()->json([
            'data' => new CouponResource($coupon),
        ]);
    }

    public function update(CouponUpdateRequest $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $data = $request->validated();

        unset($data['_method'], $data['_token']);

        if (isset($data['applicable_ids']) && is_array($data['applicable_ids'])) {
            $data['applicable_ids'] = json_encode($data['applicable_ids']);
        }

        $coupon->update($data);

        return response()->json([
            'message' => 'Cập nhật mã giảm giá thành công.',
            'data' => new CouponResource($coupon),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        if ($coupon->used_count > 0) {
            return response()->json([
                'message' => 'Không thể xóa mã giảm giá đã được sử dụng.',
            ], 422);
        }

        $coupon->delete();

        return response()->json([
            'message' => 'Xóa mã giảm giá thành công.',
        ]);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update(['is_active' => !$coupon->is_active]);

        return response()->json([
            'message' => $coupon->is_active ? 'Đã kích hoạt mã giảm giá.' : 'Đã vô hiệu hóa mã giảm giá.',
            'data' => new CouponResource($coupon),
        ]);
    }
}

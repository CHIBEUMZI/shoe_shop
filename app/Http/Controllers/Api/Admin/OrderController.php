<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateOrderStatusRequest;
use App\Http\Resources\Public\OrderResource;
use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class OrderController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        $orders = Order::query()
            ->with(['items', 'payments', 'user'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $kw = trim($request->search);

                $q->where(function ($sub) use ($kw) {
                    $sub->where('code', 'like', "%{$kw}%")
                        ->orWhere('customer_name', 'like', "%{$kw}%")
                        ->orWhere('customer_phone', 'like', "%{$kw}%")
                        ->orWhere('customer_email', 'like', "%{$kw}%");
                });
            })
            ->when($request->filled('user_id'), function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('payment_status'), function ($q) use ($request) {
                $q->where('payment_status', $request->payment_status);
            })
            ->when($request->filled('sort_by'), function ($q) use ($request) {
                $sortBy = $request->string('sort_by')->toString();
                $sortDir = strtolower($request->string('sort_dir')->toString() ?: 'desc');

                $allowedSorts = ['created_at', 'grand_total', 'status', 'payment_status', 'code'];
                if (in_array($sortBy, $allowedSorts, true)) {
                    $q->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
                }
            }, function ($q) {
                $q->latest();
            })
            ->paginate($perPage);

        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        $order->load(['items', 'payments', 'user']);

        return new OrderResource($order);
    }

    public function updateStatus(AdminUpdateOrderStatusRequest $request, Order $order)
    {
        $nextStatus = $request->validated()['status'];
        $currentStatus = (string) $order->status;

        if ($currentStatus === $nextStatus) {
            return response()->json([
                'message' => 'Trạng thái đơn hàng không thay đổi.',
                'data' => new OrderResource($order->fresh(['items', 'payments', 'user'])),
            ]);
        }

        if (!$this->canTransition($currentStatus, $nextStatus)) {
            return response()->json([
                'message' => "Không thể chuyển trạng thái từ {$currentStatus} sang {$nextStatus}.",
            ], 422);
        }

        if (
            $order->payment_method === 'cod' &&
            $nextStatus === 'confirmed' &&
            !$order->stock_deducted_at
        ) {
            try {
                $order->update([
                    'status' => 'confirmed',
                ]);

                $this->inventoryService->deductStockForOrder(
                    $order->fresh(['items.variant'])
                );

                $this->sendStatusMail(
                    $order->fresh(['items', 'payments', 'user']),
                    'confirmed'
                );

                return response()->json([
                    'message' => 'Cập nhật trạng thái đơn hàng thành công.',
                    'data' => new OrderResource($order->fresh(['items', 'payments', 'user'])),
                ]);
            } catch (RuntimeException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'message' => 'Không thể cập nhật trạng thái đơn hàng.',
                ], 500);
            }
        }

        $updateData = [
            'status' => $nextStatus,
        ];

        if ($nextStatus === 'completed' && $order->payment_status !== 'paid') {
            $updateData['payment_status'] = 'paid';

            if (empty($order->paid_at)) {
                $updateData['paid_at'] = now();
            }
        }

        $order->update($updateData);

        $this->sendStatusMail(
            $order->fresh(['items', 'payments', 'user']),
            $nextStatus
        );

        return response()->json([
            'message' => 'Cập nhật trạng thái đơn hàng thành công.',
            'data' => new OrderResource($order->fresh(['items', 'payments', 'user'])),
        ]);
    }

    protected function canTransition(string $current, string $next): bool
    {
        $map = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['processing', 'cancelled'],
            'processing' => ['shipping', 'cancelled'],
            'shipping' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ];

        return in_array($next, $map[$current] ?? [], true);
    }

    protected function sendStatusMail(Order $order, string $status): void
    {
        if (empty($order->customer_email)) {
            return;
        }

        $allowedStatuses = [
            'confirmed',
            'shipping',
            'completed',
            'cancelled',
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            return;
        }

        Mail::to($order->customer_email)->queue(
            new OrderStatusMail(
                $order->fresh(['items', 'payments']),
                $status
            )
        );
    }
}
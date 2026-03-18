<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\OrderResource;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {
    }

    public function create(Request $request, Order $order)
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            abort(403, 'Bạn không có quyền thanh toán đơn hàng này.');
        }

        $order->load(['items', 'payments']);

        if ($order->payment_method === 'cod') {
            return response()->json([
                'message' => 'Đơn hàng COD không cần tạo link thanh toán.',
                'data' => new OrderResource($order),
            ]);
        }

        $payment = $order->payments()->latest()->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Không tìm thấy giao dịch thanh toán.',
            ], 404);
        }

        if ($payment->status === 'paid') {
            return response()->json([
                'message' => 'Đơn hàng đã được thanh toán.',
                'data' => new OrderResource($order),
            ]);
        }

        return response()->json([
            'message' => 'Tạo yêu cầu thanh toán thành công.',
            'data' => [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'payment_method' => $order->payment_method,
                'amount' => $order->grand_total,
                'payment_url' => route('payments.mock-success', ['order' => $order->id]),
            ],
        ]);
    }

    public function mockSuccess(Request $request, Order $order)
    {
        $order->load(['items', 'payments']);

        $payment = $order->payments()->latest()->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Không tìm thấy giao dịch thanh toán.',
            ], 404);
        }

        if ($payment->status === 'paid' && $order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Đơn hàng đã được thanh toán trước đó.',
                'data' => new OrderResource($order->fresh(['items', 'payments'])),
            ]);
        }

        try {
            DB::transaction(function () use ($payment, $order, $request) {
                $payment->update([
                    'status' => 'paid',
                    'transaction_code' => $payment->transaction_code ?: 'TXN' . now()->format('YmdHis') . strtoupper(Str::random(6)),
                    'response_payload' => json_encode([
                        'mock' => true,
                        'ip' => $request->ip(),
                        'time' => now()->toDateTimeString(),
                    ], JSON_UNESCAPED_UNICODE),
                    'paid_at' => now(),
                ]);

                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'status' => 'confirmed',
                ]);
            });

            $this->inventoryService->deductStockForOrder($order->fresh(['items.variant']));
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Không thể xác nhận thanh toán.',
            ], 500);
        }

        return response()->json([
            'message' => 'Thanh toán thành công.',
            'data' => new OrderResource($order->fresh(['items', 'payments'])),
        ]);
    }
}
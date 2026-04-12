<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function deductStockForOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->refresh();

            if ($order->stock_deducted_at) {
                return;
            }

            $order->loadMissing(['items.variant']);

            foreach ($order->items as $item) {
                $variant = ProductVariant::query()
                    ->lockForUpdate()
                    ->find($item->product_variant_id);

                if (!$variant) {
                    throw new RuntimeException("Biến thể sản phẩm không tồn tại.");
                }

                $qty = (int) $item->quantity;
                $stock = (int) $variant->stock;

                if ($stock < $qty) {
                    throw new RuntimeException("Sản phẩm {$variant->sku} không đủ tồn kho.");
                }

                $variant->decrement('stock', $qty);
            }

            $order->update([
                'stock_deducted_at' => now(),
            ]);
        });
    }

    /**
     * Khôi phục tồn kho khi đơn hàng bị hủy.
     * Chỉ khôi phục nếu đơn hàng đã từng bị khấu trừ stock (stock_deducted_at không null).
     */
    public function restoreStockForCancelledOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->refresh();

            if (!$order->stock_deducted_at) {
                return;
            }

            $order->loadMissing(['items.variant']);

            foreach ($order->items as $item) {
                $variant = ProductVariant::query()
                    ->lockForUpdate()
                    ->find($item->product_variant_id);

                if (!$variant) {
                    logger()->warning("RestoreStock: Biến thể ID {$item->product_variant_id} không tồn tại.");
                    continue;
                }

                $variant->increment('stock', (int) $item->quantity);
            }

            $order->update([
                'stock_deducted_at' => null,
            ]);
        });
    }
}
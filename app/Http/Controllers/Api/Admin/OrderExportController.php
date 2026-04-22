<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OrderExportController extends Controller
{
    public function excel(Request $request)
    {
        $filters = $request->only(['search', 'status', 'payment_status']);

        return Excel::download(
            new OrdersExport($filters),
            'danh-sach-don-hang-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }

    public function pdf(Request $request)
    {
        $filters = $request->only(['search', 'status', 'payment_status']);

        $query = Order::query()
            ->with(['items.variant'])
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $kw = trim($filters['search']);
                $q->where(function ($sub) use ($kw) {
                    $sub->where('code', 'like', "%{$kw}%")
                        ->orWhere('customer_name', 'like', "%{$kw}%")
                        ->orWhere('customer_phone', 'like', "%{$kw}%")
                        ->orWhere('customer_email', 'like', "%{$kw}%");
                });
            })
            ->when(!empty($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(!empty($filters['payment_status']), function ($q) use ($filters) {
                $q->where('payment_status', $filters['payment_status']);
            })
            ->orderBy('created_at', 'desc');

        $orders = $query->get();

        $stats = [
            'total' => $orders->count(),
            'totalRevenue' => $orders->sum('grand_total'),
            'totalSubtotal' => $orders->sum('subtotal'),
            'totalDiscount' => $orders->sum('discount_total'),
            'totalShipping' => $orders->sum('shipping_fee'),
            'totalItems' => $orders->sum(fn ($order) => $order->items->count()),
            'byStatus' => $orders->groupBy('status')->map->count(),
            'byPaymentStatus' => $orders->groupBy('payment_status')->map->count(),
        ];

        $data = [
            'orders' => $orders,
            'date' => now()->format('d/m/Y H:i:s'),
            'stats' => $stats,
            'filters' => $filters,
        ];

        $pdf = Pdf::loadView('exports.orders', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('danh-sach-don-hang-' . now()->format('Y-m-d-His') . '.pdf');
    }
}

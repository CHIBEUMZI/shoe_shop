<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    /**
     * Trạng thái đơn hàng được tính vào doanh thu (chỉ completed).
     */
    protected array $revenueOrderStatuses = [
        'completed',
    ];

    /**
     * Trạng thái đơn hàng thành công (dùng cho top sản phẩm bán chạy).
     */
    protected array $successOrderStatuses = [
        'paid',
        'processing',
        'shipping',
        'completed',
    ];

    public function index(Request $request)
    {
        $filters = $this->resolveDashboardFilters($request);
        $rangeKey = $filters['cache_key'];
        $startDate = $filters['startDate'];
        $endDate = $filters['endDate'];
        $chartMode = $filters['chartMode'];
        $previousStartDate = $filters['previousStartDate'];
        $previousEndDate = $filters['previousEndDate'];

        $cacheKey = sprintf(
            'admin_dashboard:%s:%s:%s:%s:%s',
            $rangeKey,
            $startDate->format('YmdHis'),
            $endDate->format('YmdHis'),
            $previousStartDate->format('YmdHis'),
            $previousEndDate->format('YmdHis')
        );

        $data = Cache::remember($cacheKey, now()->addSeconds(10), function () use (
            $startDate,
            $endDate,
            $previousStartDate,
            $previousEndDate,
            $chartMode
        ) {
            return [
                'overview' => $this->getOverview($startDate, $endDate, $previousStartDate, $previousEndDate),
                'chart' => $this->getRevenueChart($startDate, $endDate, $chartMode),
                'top_products' => $this->getTopProducts($startDate, $endDate),
                'recent_orders' => $this->getRecentOrders(),
                'order_status' => $this->getOrderStatus(),
                'new_customers' => $this->getNewCustomers($startDate, $endDate),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $filters = $this->resolveDashboardFilters($request);
        $payload = $this->buildExportPayload($filters['startDate'], $filters['endDate'], $filters['previousStartDate'], $filters['previousEndDate'], $filters['chartMode']);

        return Excel::download(
            new \App\Exports\DashboardExport($payload),
            sprintf('dashboard-%s.xlsx', now()->format('Y-m-d-His'))
        );
    }

    public function exportPdf(Request $request): Response
    {
        $filters = $this->resolveDashboardFilters($request);
        $payload = $this->buildExportPayload($filters['startDate'], $filters['endDate'], $filters['previousStartDate'], $filters['previousEndDate'], $filters['chartMode']);

        $pdf = Pdf::loadView('exports.admin.dashboard', [
            'payload' => $payload,
            'rangeLabel' => $payload['period']['label'],
            'generatedAt' => $payload['generated_at'],
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, sprintf('dashboard-%s.pdf', now()->format('Y-m-d-His')));
    }

    protected function resolveDashboardFilters(Request $request): array
    {
        $mode = $request->get('filter_mode', 'range');

        if ($mode === 'custom') {
            $startDate = $this->parseDate($request->get('start_date'))?->startOfDay();
            $endDate = $this->parseDate($request->get('end_date'))?->endOfDay();

            if (!$startDate || !$endDate) {
                abort(422, 'Vui lòng chọn ngày bắt đầu và ngày kết thúc hợp lệ.');
            }

            if ($startDate->gt($endDate)) {
                abort(422, 'Ngày bắt đầu không được lớn hơn ngày kết thúc.');
            }

            $days = max(1, $startDate->diffInDays($endDate) + 1);
            $chartMode = $days > 31 ? 'month' : 'day';
            $previousStartDate = $this->shiftDateRangeBackwardForExport($startDate, $days, $chartMode)->startOfDay();
            $previousEndDate = $this->shiftDateRangeBackwardForExport($endDate, $days, $chartMode)->endOfDay();

            return [
                'cache_key' => 'custom:' . $startDate->format('Ymd') . ':' . $endDate->format('Ymd'),
                'startDate' => $startDate,
                'endDate' => $endDate,
                'chartMode' => $chartMode,
                'previousStartDate' => $previousStartDate,
                'previousEndDate' => $previousEndDate,
            ];
        }

        $range = $request->get('range', '30days');
        [$startDate, $endDate, $chartMode] = $this->resolveRange($range);
        [$previousStartDate, $previousEndDate] = $this->resolvePreviousRange($startDate, $endDate, $chartMode);

        return [
            'cache_key' => $range,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'chartMode' => $chartMode,
            'previousStartDate' => $previousStartDate,
            'previousEndDate' => $previousEndDate,
        ];
    }

    protected function resolveRange(string $range): array
    {
        $now = now();

        return match ($range) {
            '7days' => [
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
                'day',
            ],
            '12months' => [
                $now->copy()->subMonths(11)->startOfMonth(),
                $now->copy()->endOfMonth(),
                'month',
            ],
            default => [
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
                'day',
            ],
        };
    }

    protected function resolvePreviousRange($startDate, $endDate, string $chartMode): array
    {
        if ($chartMode === 'day') {
            $days = $startDate->diffInDays($endDate) + 1;

            return [
                $startDate->copy()->subDays($days)->startOfDay(),
                $startDate->copy()->subDay()->endOfDay(),
            ];
        }

        $months = $startDate->diffInMonths($endDate) + 1;

        return [
            $startDate->copy()->subMonths($months)->startOfMonth(),
            $startDate->copy()->subDay()->endOfDay(),
        ];
    }

    protected function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function getOverview($startDate, $endDate, $previousStartDate, $previousEndDate): array
    {
        $revenueStatusPlaceholders = implode(',', array_fill(0, count($this->revenueOrderStatuses), '?'));

        $orderStats = DB::selectOne(
            "
            SELECT
                COALESCE(SUM(
                    CASE
                        WHEN updated_at BETWEEN ? AND ?
                             AND status IN ($revenueStatusPlaceholders)
                        THEN grand_total
                        ELSE 0
                    END
                ), 0) AS current_revenue,

                COALESCE(SUM(
                    CASE
                        WHEN updated_at BETWEEN ? AND ?
                             AND status IN ($revenueStatusPlaceholders)
                        THEN grand_total
                        ELSE 0
                    END
                ), 0) AS previous_revenue,

                SUM(
                    CASE
                        WHEN updated_at BETWEEN ? AND ?
                        THEN 1 ELSE 0
                    END
                ) AS current_orders,

                SUM(
                    CASE
                        WHEN updated_at BETWEEN ? AND ?
                        THEN 1 ELSE 0
                    END
                ) AS previous_orders
            FROM orders
            ",
            [
                $startDate,
                $endDate,
                ...$this->revenueOrderStatuses,

                $previousStartDate,
                $previousEndDate,
                ...$this->revenueOrderStatuses,

                $startDate,
                $endDate,

                $previousStartDate,
                $previousEndDate,
            ]
        );

        $customerStats = DB::selectOne(
            "
            SELECT
                SUM(
                    CASE
                        WHEN role = 'customer'
                             AND created_at BETWEEN ? AND ?
                        THEN 1 ELSE 0
                    END
                ) AS current_customers,

                SUM(
                    CASE
                        WHEN role = 'customer'
                             AND created_at BETWEEN ? AND ?
                        THEN 1 ELSE 0
                    END
                ) AS previous_customers
            FROM users
            ",
            [
                $startDate,
                $endDate,
                $previousStartDate,
                $previousEndDate,
            ]
        );

        $productStats = DB::selectOne(
            "
            SELECT
                COUNT(*) AS current_products,
                SUM(
                    CASE
                        WHEN created_at < ?
                        THEN 1 ELSE 0
                    END
                ) AS previous_products
            FROM products
            ",
            [$startDate]
        );

        $currentRevenue = (float) ($orderStats->current_revenue ?? 0);
        $previousRevenue = (float) ($orderStats->previous_revenue ?? 0);

        $currentOrders = (int) ($orderStats->current_orders ?? 0);
        $previousOrders = (int) ($orderStats->previous_orders ?? 0);

        $currentCustomers = (int) ($customerStats->current_customers ?? 0);
        $previousCustomers = (int) ($customerStats->previous_customers ?? 0);

        $currentProducts = (int) ($productStats->current_products ?? 0);
        $previousProducts = (int) ($productStats->previous_products ?? 0);

        return [
            'revenue' => $currentRevenue,
            'orders' => $currentOrders,
            'customers' => $currentCustomers,
            'products' => $currentProducts,
            'revenue_growth' => $this->growthPercent($currentRevenue, $previousRevenue),
            'orders_growth' => $this->growthPercent($currentOrders, $previousOrders),
            'customers_growth' => $this->growthPercent($currentCustomers, $previousCustomers),
            'products_growth' => $this->growthPercent($currentProducts, $previousProducts),
        ];
    }

    protected function getRevenueChart($startDate, $endDate, string $mode): array
    {
        if ($mode === 'month') {
            $rows = Order::query()
                ->selectRaw('YEAR(updated_at) as year_num, MONTH(updated_at) as month_num, SUM(grand_total) as total')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->whereIn('status', $this->revenueOrderStatuses)
                ->groupByRaw('YEAR(updated_at), MONTH(updated_at)')
                ->orderByRaw('YEAR(updated_at), MONTH(updated_at)')
                ->get()
                ->keyBy(fn ($item) => $item->year_num . '-' . $item->month_num);

            $chart = [];
            $cursor = $startDate->copy()->startOfMonth();

            while ($cursor <= $endDate) {
                $key = $cursor->year . '-' . $cursor->month;

                $chart[] = [
                    'label' => 'T' . $cursor->month,
                    'value' => (float) ($rows[$key]->total ?? 0),
                ];

                $cursor->addMonth();
            }

            return $chart;
        }

        $rows = Order::query()
            ->selectRaw('DATE(updated_at) as order_date, SUM(grand_total) as total')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->whereIn('status', $this->revenueOrderStatuses)
            ->groupByRaw('DATE(updated_at)')
            ->orderByRaw('DATE(updated_at)')
            ->get()
            ->keyBy('order_date');

        $chart = [];
        $cursor = $startDate->copy()->startOfDay();

        while ($cursor <= $endDate) {
            $dateKey = $cursor->format('Y-m-d');

            $chart[] = [
                'label' => $cursor->format('d/m'),
                'value' => (float) ($rows[$dateKey]->total ?? 0),
            ];

            $cursor->addDay();
        }

        return $chart;
    }

    /**
     * Lấy danh sách top sản phẩm bán chạy kèm theo size và màu phổ biến nhất.
     * Tối ưu: Gộp thành 3 queries thay vì N+1.
     *
     * @param Carbon $startDate Ngày bắt đầu thống kê
     * @param Carbon $endDate Ngày kết thúc thống kê
     * @return array Danh sách sản phẩm với thông tin size và màu bán chạy
     */
    protected function getTopProducts($startDate, $endDate): array
    {
        // Query 1: Lấy top 5 sản phẩm bán chạy
        $productRows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereBetween('orders.updated_at', [$startDate, $endDate])
            ->whereIn('orders.status', $this->successOrderStatuses)
            ->groupBy('products.id', 'products.name', 'products.thumbnail')
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->limit(5)
            ->get([
                'products.id',
                'products.name',
                'products.thumbnail',
                DB::raw('SUM(order_items.quantity) as sold'),
            ]);

        if ($productRows->isEmpty()) {
            return [];
        }

        $productIds = $productRows->pluck('id')->toArray();

        // Query 2: Lấy top 3 sizes cho TẤT CẢ sản phẩm (thay vì query riêng cho từng sản phẩm)
        $allTopSizes = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $productIds)
            ->whereBetween('orders.updated_at', [$startDate, $endDate])
            ->whereIn('orders.status', $this->successOrderStatuses)
            ->whereNotNull('order_items.size')
            ->groupBy('order_items.product_id', 'order_items.size')
            ->select(
                'order_items.product_id',
                'order_items.size',
                DB::raw('SUM(order_items.quantity) as total_sold')
            )
            ->orderByDesc('total_sold')
            ->get()
            ->groupBy('product_id')
            ->map(fn($items) => $items->take(3)->values())
            ->all();

        // Query 3: Lấy top 3 colors cho TẤT CẢ sản phẩm
        $allTopColors = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $productIds)
            ->whereBetween('orders.updated_at', [$startDate, $endDate])
            ->whereIn('orders.status', $this->successOrderStatuses)
            ->whereNotNull('order_items.color')
            ->groupBy('order_items.product_id', 'order_items.color')
            ->select(
                'order_items.product_id',
                'order_items.color',
                DB::raw('SUM(order_items.quantity) as total_sold')
            )
            ->orderByDesc('total_sold')
            ->get()
            ->groupBy('product_id')
            ->map(fn($items) => $items->take(3)->values())
            ->all();

        // Ghép dữ liệu
        return $productRows->map(function ($product) use ($allTopSizes, $allTopColors) {
            return [
                'id' => (int) $product->id,
                'name' => $product->name,
                'sold' => (int) $product->sold,
                'thumbnail' => $product->thumbnail,
                'top_sizes' => collect($allTopSizes[$product->id] ?? [])->map(fn($item) => [
                    'size' => $item->size,
                    'sold' => (int) $item->total_sold,
                ])->values()->all(),
                'top_colors' => collect($allTopColors[$product->id] ?? [])->map(fn($item) => [
                    'color' => $item->color,
                    'sold' => (int) $item->total_sold,
                    'hex' => $this->colorHex($item->color),
                ])->values()->all(),
            ];
        })->values()->all();
    }

    protected function getRecentOrders(): array
    {
        return Order::query()
            ->latest('id')
            ->limit(5)
            ->get([
                'id',
                'code',
                'customer_name',
                'grand_total',
                'status',
                'created_at',
            ])
            ->map(function ($order) {
                return [
                    'id' => (int) $order->id,
                    'code' => $order->code,
                    'customer_name' => $order->customer_name,
                    'total_amount' => (float) $order->grand_total,
                    'status' => $order->status,
                    'status_label' => $this->statusLabel($order->status),
                    'created_at' => optional($order->created_at)->format('d/m/Y H:i'),
                ];
            })
            ->values()
            ->all();
    }

    protected function getOrderStatus(): array
    {
        $rows = Order::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $configs = [
            'pending' => [
                'label' => 'Chờ xử lý',
                'dot' => 'bg-amber-400',
                'bar' => 'bg-amber-400',
            ],
            'confirmed' => [
                'label' => 'Đã xác nhận',
                'dot' => 'bg-blue-500',
                'bar' => 'bg-blue-500',
            ],
            'paid' => [
                'label' => 'Đã thanh toán',
                'dot' => 'bg-sky-500',
                'bar' => 'bg-sky-500',
            ],
            'processing' => [
                'label' => 'Đang xử lý',
                'dot' => 'bg-indigo-500',
                'bar' => 'bg-indigo-500',
            ],
            'shipping' => [
                'label' => 'Đang giao',
                'dot' => 'bg-violet-500',
                'bar' => 'bg-violet-500',
            ],
            'completed' => [
                'label' => 'Hoàn thành',
                'dot' => 'bg-emerald-500',
                'bar' => 'bg-emerald-500',
            ],
            'cancelled' => [
                'label' => 'Đã hủy',
                'dot' => 'bg-rose-500',
                'bar' => 'bg-rose-500',
            ],
        ];

        $result = [];

        foreach ($configs as $key => $cfg) {
            $result[] = [
                'key' => $key,
                'label' => $cfg['label'],
                'count' => (int) ($rows[$key] ?? 0),
                'dot' => $cfg['dot'],
                'bar' => $cfg['bar'],
            ];
        }

        return $result;
    }

    protected function getNewCustomers($startDate, $endDate): array
    {
        return User::query()
            ->where('role', 'customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest('id')
            ->limit(5)
            ->get([
                'id',
                'name',
                'email',
                'avatar',
                'created_at',
            ])
            ->map(function ($user) {
                return [
                    'id' => (int) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'created_at' => optional($user->created_at)->format('d/m'),
                ];
            })
            ->values()
            ->all();
    }

    protected function buildExportPayload($startDate, $endDate, $previousStartDate, $previousEndDate, string $chartMode): array
    {
        return [
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y'),
                'label' => $this->rangeLabel($chartMode === 'month' ? '12months' : '30days'),
            ],
            'generated_at' => now()->format('d/m/Y H:i'),
            'sections' => [
                'overview' => $this->getOverview($startDate, $endDate, $previousStartDate, $previousEndDate),
                'chart' => $this->getRevenueChart($startDate, $endDate, $chartMode),
                'top_products' => $this->getTopProducts($startDate, $endDate),
                'recent_orders' => $this->getRecentOrders(),
                'order_status' => $this->getOrderStatus(),
                'new_customers' => $this->getNewCustomers($startDate, $endDate),
            ],
        ];
    }

    protected function rangeLabel(string $range): string
    {
        return match ($range) {
            '7days' => '7 ngày gần đây',
            '12months' => '12 tháng gần đây',
            default => '30 ngày gần đây',
        };
    }

    protected function shiftDateRangeBackwardForExport(Carbon $date, int $days, string $chartMode): Carbon
    {
        return $chartMode === 'month'
            ? $date->copy()->subMonthsNoOverflow($days)
            : $date->copy()->subDays($days);
    }

    protected function growthPercent($current, $previous): float
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function colorHex(?string $colorName): string
    {
        $color = strtolower(trim((string) $colorName));

        return match ($color) {
            'đen', 'black' => '#1f2937',
            'đỏ', 'red' => '#ef4444',
            'xanh lá', 'green' => '#22c55e',
            'xanh dương', 'blue' => '#3b82f6',
            'vàng', 'yellow' => '#eab308',
            'trắng', 'white' => '#f9fafb',
            'nâu', 'brown' => '#92400e',
            'xám', 'gray', 'grey' => '#6b7280',
            'tím', 'purple' => '#a855f7',
            'cam', 'orange' => '#f97316',
            'hồng', 'pink' => '#ec4899',
            'navy', 'xanh navy' => '#1e3a5f',
            'be' => '#d6c5b3',
            'bạc', 'silver' => '#9ca3af',
            'vàng gold', 'gold' => '#d4af37',
            default => '#9ca3af',
        };
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Chờ xử lý',
            'confirmed' => 'Đã xác nhận',
            'paid' => 'Đã thanh toán',
            'processing' => 'Đang xử lý',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            default => ucfirst((string) $status),
        };
    }
}
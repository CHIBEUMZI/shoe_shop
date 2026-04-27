<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
        $range = $request->get('range', '30days');

        [$startDate, $endDate, $chartMode] = $this->resolveRange($range);

        [$previousStartDate, $previousEndDate] = $this->resolvePreviousRange(
            $startDate,
            $endDate,
            $chartMode
        );

        $cacheKey = sprintf(
            'admin_dashboard:%s:%s:%s:%s:%s',
            $range,
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

    protected function getOverview($startDate, $endDate, $previousStartDate, $previousEndDate): array
    {
        $revenueStatusPlaceholders = implode(',', array_fill(0, count($this->revenueOrderStatuses), '?'));

        $orderStats = DB::selectOne(
            "
            SELECT
                COALESCE(SUM(
                    CASE
                        WHEN created_at BETWEEN ? AND ?
                             AND status IN ($revenueStatusPlaceholders)
                        THEN grand_total
                        ELSE 0
                    END
                ), 0) AS current_revenue,

                COALESCE(SUM(
                    CASE
                        WHEN created_at BETWEEN ? AND ?
                             AND status IN ($revenueStatusPlaceholders)
                        THEN grand_total
                        ELSE 0
                    END
                ), 0) AS previous_revenue,

                SUM(
                    CASE
                        WHEN created_at BETWEEN ? AND ?
                        THEN 1 ELSE 0
                    END
                ) AS current_orders,

                SUM(
                    CASE
                        WHEN created_at BETWEEN ? AND ?
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
                ->selectRaw('YEAR(created_at) as year_num, MONTH(created_at) as month_num, SUM(grand_total) as total')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', $this->revenueOrderStatuses)
                ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                ->orderByRaw('YEAR(created_at), MONTH(created_at)')
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
            ->selectRaw('DATE(created_at) as order_date, SUM(grand_total) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', $this->revenueOrderStatuses)
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
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
            ->whereBetween('orders.created_at', [$startDate, $endDate])
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
            ->whereBetween('orders.created_at', [$startDate, $endDate])
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
            ->whereBetween('orders.created_at', [$startDate, $endDate])
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

    protected function growthPercent($current, $previous): float
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
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
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .header { margin-bottom: 18px; }
        .title { font-size: 22px; font-weight: bold; color: #111827; }
        .meta { color: #6b7280; margin-top: 4px; }
        .section { margin-top: 18px; font-size: 14px; font-weight: bold; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; vertical-align: top; }
        th { background: #1e40af; color: #fff; text-align: left; }
        .summary { display: flex; gap: 10px; flex-wrap: wrap; }
        .card { border: 1px solid #d1d5db; padding: 10px; border-radius: 6px; width: 31%; }
        .card h4 { margin: 0 0 6px 0; font-size: 12px; }
        .card .value { font-size: 16px; font-weight: bold; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Báo cáo Dashboard</div>
        <div class="meta">Kỳ báo cáo: {{ $rangeLabel }}</div>
        <div class="meta">Thời gian xuất: {{ $generatedAt }}</div>
    </div>

    <div class="section">Doanh thu / Đơn hàng / Khách hàng / Sản phẩm</div>
    <div class="summary">
        <div class="card"><h4>Doanh thu</h4><div class="value">{{ number_format(data_get($payload, 'sections.overview.revenue', 0), 0, ',', '.') }}đ</div><div class="muted">Tăng trưởng: {{ data_get($payload, 'sections.overview.revenue_growth', 0) }}%</div></div>
        <div class="card"><h4>Đơn hàng</h4><div class="value">{{ number_format(data_get($payload, 'sections.overview.orders', 0)) }}</div><div class="muted">Tăng trưởng: {{ data_get($payload, 'sections.overview.orders_growth', 0) }}%</div></div>
        <div class="card"><h4>Khách hàng</h4><div class="value">{{ number_format(data_get($payload, 'sections.overview.customers', 0)) }}</div><div class="muted">Tăng trưởng: {{ data_get($payload, 'sections.overview.customers_growth', 0) }}%</div></div>
        <div class="card"><h4>Sản phẩm</h4><div class="value">{{ number_format(data_get($payload, 'sections.overview.products', 0)) }}</div><div class="muted">Tăng trưởng: {{ data_get($payload, 'sections.overview.products_growth', 0) }}%</div></div>
    </div>

    <div class="section">Biểu đồ doanh thu</div>
    <table>
        <thead><tr><th>Mốc thời gian</th><th>Doanh thu</th></tr></thead>
        <tbody>
        @foreach(data_get($payload, 'sections.chart', []) as $item)
            <tr>
                <td>{{ $item['label'] ?? '' }}</td>
                <td>{{ number_format($item['value'] ?? 0, 0, ',', '.') }}đ</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="section">Top sản phẩm bán chạy</div>
    <table>
        <thead><tr><th>Sản phẩm</th><th>Đã bán</th><th>Ghi chú</th></tr></thead>
        <tbody>
        @foreach(data_get($payload, 'sections.top_products', []) as $item)
            <tr>
                <td>{{ $item['name'] ?? '' }}</td>
                <td>{{ $item['sold'] ?? 0 }}</td>
                <td>Size/Màu phổ biến đã được tổng hợp</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="section">Đơn hàng gần đây</div>
    <table>
        <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead>
        <tbody>
        @foreach(data_get($payload, 'sections.recent_orders', []) as $order)
            <tr>
                <td>{{ $order['code'] ?? '' }}</td>
                <td>{{ $order['customer_name'] ?? '' }}</td>
                <td>{{ number_format($order['total_amount'] ?? 0, 0, ',', '.') }}đ</td>
                <td>{{ $order['status_label'] ?? ($order['status'] ?? '') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="section">Khách hàng mới</div>
    <table>
        <thead><tr><th>Tên</th><th>Email</th><th>Ngày tạo</th></tr></thead>
        <tbody>
        @foreach(data_get($payload, 'sections.new_customers', []) as $customer)
            <tr>
                <td>{{ $customer['name'] ?? '' }}</td>
                <td>{{ $customer['email'] ?? '' }}</td>
                <td>{{ $customer['created_at'] ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>

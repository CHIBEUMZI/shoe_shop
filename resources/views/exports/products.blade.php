<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách sản phẩm - Chi tiết kho</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1f2937;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3b82f6;
        }
        .header h1 {
            font-size: 16px;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .header .meta {
            font-size: 9px;
            color: #6b7280;
        }
        .summary {
            margin-bottom: 12px;
            padding: 8px 12px;
            background: #f3f4f6;
            border-radius: 5px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .summary-item {
            display: flex;
            gap: 5px;
        }
        .summary-item strong {
            color: #1e40af;
        }
        .low-stock {
            color: #dc2626;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 5px 4px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background: #1e40af;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .money {
            text-align: right;
            font-family: monospace;
            white-space: nowrap;
        }
        .color-box {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .color-swatch {
            width: 16px;
            height: 16px;
            border-radius: 3px;
            border: 1px solid #d1d5db;
        }
        .status-active {
            color: #059669;
            font-weight: bold;
        }
        .status-inactive {
            color: #dc2626;
        }
        .featured {
            color: #d97706;
            font-weight: bold;
        }
        .product-header {
            background: #e0e7ff !important;
            font-weight: bold;
        }
        .stock-low {
            background: #fee2e2 !important;
            color: #dc2626;
        }
        .stock-out {
            background: #fecaca !important;
            color: #991b1b;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 BÁO CÁO TỒN KHO SẢN PHẨM</h1>
        <div class="meta">Xuất ngày: {{ $date }}</div>
    </div>

    <div class="summary">
        <div class="summary-item">
            <strong>Tổng sản phẩm:</strong> {{ $totalProducts }}
        </div>
        <div class="summary-item">
            <strong>Tổng biến thể:</strong> {{ $totalVariants }}
        </div>
        <div class="summary-item">
            <strong>Tổng tồn kho:</strong> {{ $totalStock }}
        </div>
        @if($lowStockVariants > 0)
        <div class="summary-item">
            <strong class="low-stock">Sắp hết hàng:</strong> {{ $lowStockVariants }}
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">ID</th>
                <th>Tên sản phẩm</th>
                <th style="width: 60px;">SKU</th>
                <th style="width: 70px;">Màu sắc</th>
                <th style="width: 40px;">Size</th>
                <th style="width: 55px;">SKU VT</th>
                <th class="text-right">Giá</th>
                <th class="text-right">Giá KM</th>
                <th class="text-center" style="width: 50px;">Tồn kho</th>
                <th class="text-center" style="width: 60px;">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inventory as $item)
                <tr class="{{ $item['stock_class'] }}">
                    <td class="text-center">{{ $item['product_id'] }}</td>
                    <td>
                        <strong>{{ $item['product_name'] }}</strong>
                        @if($item['is_first_variant'])
                            <br><small style="color: #6b7280;">{{ $item['categories'] }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item['product_sku'] }}</td>
                    <td class="text-center">
                        <div class="color-box">
                            @if($item['color_hex'])
                                <span class="color-swatch" style="background-color: {{ $item['color_hex'] }};"></span>
                            @endif
                            {{ $item['color'] }}
                        </div>
                    </td>
                    <td class="text-center"><strong>{{ $item['size'] }}</strong></td>
                    <td class="text-center">{{ $item['variant_sku'] }}</td>
                    <td class="money">{{ number_format($item['variant_price'], 0, ',', '.') }} ₫</td>
                    <td class="money">
                        @if($item['variant_sale_price'])
                            {{ number_format($item['variant_sale_price'], 0, ',', '.') }} ₫
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        <strong>{{ $item['stock'] }}</strong>
                    </td>
                    <td class="text-center">
                        <span class="{{ $item['is_active'] ? 'status-active' : 'status-inactive' }}">
                            {{ $item['is_active'] ? 'Còn hàng' : 'Hết hàng' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; color: #6b7280;">
                        Không có dữ liệu
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Shoe Shop - Hệ thống quản lý kho hàng
    </div>
</body>
</html>

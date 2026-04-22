<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách đơn hàng</title>
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
        .stat-box {
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            padding: 6px 10px;
            text-align: center;
            min-width: 100px;
        }
        .stat-box .label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
        }
        .stat-box .value {
            font-size: 13px;
            font-weight: bold;
            color: #1e40af;
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
        .order-code {
            font-weight: bold;
            color: #1e40af;
            font-size: 11px;
        }
        .status-pending {
            color: #D97706;
            font-weight: bold;
        }
        .status-confirmed {
            color: #2563EB;
            font-weight: bold;
        }
        .status-processing {
            color: #4F46E5;
            font-weight: bold;
        }
        .status-shipping {
            color: #0284C7;
            font-weight: bold;
        }
        .status-completed {
            color: #059669;
            font-weight: bold;
        }
        .status-cancelled {
            color: #DC2626;
        }
        .payment-unpaid {
            color: #6B7280;
        }
        .payment-pending {
            color: #D97706;
        }
        .payment-paid {
            color: #059669;
            font-weight: bold;
        }
        .payment-failed {
            color: #DC2626;
        }
        .payment-refunded {
            color: #7C3AED;
        }
        .highlight-revenue {
            color: #DC2626;
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
        .items-list {
            font-size: 8px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 BÁO CÁO DANH SÁCH ĐƠN HÀNG</h1>
        <div class="meta">Xuất ngày: {{ $date }}</div>
    </div>

    <div class="summary">
        <div class="stat-box">
            <div class="label">Tổng đơn</div>
            <div class="value">{{ $stats['total'] }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Tổng sản phẩm</div>
            <div class="value">{{ $stats['totalItems'] }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Tạm tính</div>
            <div class="value">{{ number_format($stats['totalSubtotal'], 0, ',', '.') }} đ</div>
        </div>
        <div class="stat-box">
            <div class="label">Giảm giá</div>
            <div class="value">{{ number_format($stats['totalDiscount'], 0, ',', '.') }} đ</div>
        </div>
        <div class="stat-box">
            <div class="label">Phí ship</div>
            <div class="value">{{ number_format($stats['totalShipping'], 0, ',', '.') }} đ</div>
        </div>
        <div class="stat-box">
            <div class="label highlight-revenue">Tổng doanh thu</div>
            <div class="value highlight-revenue">{{ number_format($stats['totalRevenue'], 0, ',', '.') }} đ</div>
        </div>
    </div>

    @if(count($stats['byStatus']) > 0)
    <div style="margin-bottom: 12px; display: flex; gap: 10px; flex-wrap: wrap;">
        @foreach($stats['byStatus'] as $status => $count)
            <span style="background: #e0e7ff; color: #3730a3; padding: 3px 8px; border-radius: 10px; font-size: 9px; font-weight: bold;">
                @if($status === 'pending') Chờ xử lý @elseif($status === 'confirmed') Đã xác nhận @elseif($status === 'processing') Đang chuẩn bị @elseif($status === 'shipping') Đang giao @elseif($status === 'completed') Hoàn thành @elseif($status === 'cancelled') Đã hủy @else {{ $status }} @endif: {{ $count }}
            </span>
        @endforeach
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">#</th>
                <th style="width: 60px;">Mã đơn</th>
                <th>Khách hàng</th>
                <th style="width: 100px;">Địa chỉ</th>
                <th class="text-center" style="width: 45px;">PTTT</th>
                <th class="text-center" style="width: 60px;">Thanh toán</th>
                <th class="text-center" style="width: 65px;">Trạng thái</th>
                <th>Tên sản phẩm</th>
                <th class="text-center" style="width: 60px;">Phân loại</th>
                <th class="text-center" style="width: 25px;">SL</th>
                <th class="text-right" style="width: 70px;">Thành tiền</th>
                <th class="text-right" style="width: 75px;">Tổng cộng</th>
                <th class="text-center" style="width: 60px;">Ngày tạo</th>
            </tr>
        </thead>
        <tbody>
            @php $rowNum = 0; @endphp
            @forelse($orders as $order)
                @php $firstItem = true; @endphp
                @foreach($order->items as $item)
                    @php $rowNum++; @endphp
                    <tr @if($firstItem) style="background: #eff6ff;" @endif>
                        <td class="text-center" style="font-size: 8px; color: #9ca3af;">{{ $rowNum }}</td>
                        <td>
                            @if($firstItem)
                                <span class="order-code">{{ $order->code }}</span>
                            @endif
                        </td>
                        <td>
                            @if($firstItem)
                                <strong style="font-size: 10px;">{{ $order->customer_name }}</strong>
                                @if($order->customer_phone)
                                    <br><small style="color: #6b7280; font-size: 7px;">{{ $order->customer_phone }}</small>
                                @endif
                            @endif
                        </td>
                        <td style="font-size: 8px; color: #6b7280;">
                            @if($firstItem)
                                {{ implode(', ', array_filter([$order->address_line, $order->ward, $order->district, $order->province])) ?: '-' }}
                            @endif
                        </td>
                        <td class="text-center">
                            @if($firstItem)
                                {{ $order->payment_method === 'cod' ? 'COD' : ($order->payment_method === 'vnpay' ? 'VNPay' : ($order->payment_method === 'momo' ? 'MoMo' : '-')) }}
                            @endif
                        </td>
                        <td class="text-center">
                            @if($firstItem)
                                <span class="{{ $order->payment_status === 'paid' ? 'payment-paid' : ($order->payment_status === 'pending' ? 'payment-pending' : ($order->payment_status === 'failed' ? 'payment-failed' : ($order->payment_status === 'refunded' ? 'payment-refunded' : 'payment-unpaid'))) }}">
                                    @if($order->payment_status === 'unpaid') Chưa TT @elseif($order->payment_status === 'pending') Chờ TT @elseif($order->payment_status === 'paid') Đã TT @elseif($order->payment_status === 'failed') Thất bại @else Hoàn tiền @endif
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($firstItem)
                                <span class="{{ 'status-' . $order->status }}">
                                    @if($order->status === 'pending') Chờ xử lý @elseif($order->status === 'confirmed') Đã xác nhận @elseif($order->status === 'processing') Đang chuẩn bị @elseif($order->status === 'shipping') Đang giao @elseif($order->status === 'completed') Hoàn thành @elseif($order->status === 'cancelled') Đã hủy @else {{ $order->status }} @endif
                                </span>
                            @endif
                        </td>
                        <td style="font-size: 9px;">
                            <strong>{{ $item->product_name }}</strong>
                        </td>
                        <td class="text-center">
                            @php
                                $parts = [];
                                if ($item->color && $item->color !== '-') $parts[] = $item->color;
                                if ($item->size && $item->size !== '-') $parts[] = 'Size ' . $item->size;
                                $variantLabel = implode(' / ', $parts);
                            @endphp
                            @if($variantLabel)
                                <strong style="font-size: 9px;">{{ $variantLabel }}</strong>
                            @else
                                <span style="color: #9ca3af; font-size: 8px;">-</span>
                            @endif
                        </td>
                        <td class="text-center" style="font-weight: bold; font-size: 9px;">{{ $item->quantity }}</td>
                        <td class="money" style="font-size: 9px;">{{ number_format($item->line_total, 0, ',', '.') }}</td>
                        <td class="money highlight-revenue" style="font-size: 9px;">
                            @if($firstItem) {{ number_format($order->grand_total, 0, ',', '.') }} @endif
                        </td>
                        <td class="text-center" style="font-size: 8px;">
                            @if($firstItem) {{ $order->created_at->format('d/m/Y') }} @endif
                        </td>
                    </tr>
                    @php $firstItem = false; @endphp
                @endforeach
                @if($order->items->isEmpty())
                    @php $rowNum++; @endphp
                    <tr style="background: #eff6ff;">
                        <td class="text-center">{{ $rowNum }}</td>
                        <td><span class="order-code">{{ $order->code }}</span></td>
                        <td>
                            <strong style="font-size: 10px;">{{ $order->customer_name }}</strong>
                            @if($order->customer_phone)
                                <br><small style="color: #6b7280; font-size: 7px;">{{ $order->customer_phone }}</small>
                            @endif
                        </td>
                        <td style="font-size: 8px; color: #6b7280;">{{ implode(', ', array_filter([$order->address_line, $order->ward, $order->district, $order->province])) ?: '-' }}</td>
                        <td class="text-center">
                            {{ $order->payment_method === 'cod' ? 'COD' : ($order->payment_method === 'vnpay' ? 'VNPay' : ($order->payment_method === 'momo' ? 'MoMo' : '-')) }}
                        </td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td style="color: #9ca3af; font-size: 9px;">Không có sản phẩm</td>
                        <td colspan="2"></td>
                        <td colspan="2" class="money highlight-revenue" style="font-size: 9px;">{{ number_format($order->grand_total, 0, ',', '.') }}</td>
                        <td class="text-center" style="font-size: 8px;">{{ $order->created_at->format('d/m/Y') }}</td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="13" class="text-center" style="padding: 20px; color: #6b7280;">
                        Không có dữ liệu
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Shoe Shop - Hệ thống quản lý đơn hàng
    </div>
</body>
</html>

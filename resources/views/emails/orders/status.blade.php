<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông báo đơn hàng</title>
</head>
<body style="margin:0; padding:20px; background:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#334155;">
    <div style="max-width:720px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
        <div style="background:#0f172a; color:#ffffff; padding:20px 24px;">
            <h1 style="margin:0; font-size:24px;">Shoe Shop</h1>
            <p style="margin:8px 0 0; font-size:14px; opacity:.9;">
                @if($type === 'paid')
                    Thanh toán đơn hàng thành công
                @elseif($type === 'confirmed')
                    Đơn hàng đã được xác nhận
                @elseif($type === 'shipping')
                    Đơn hàng đang được giao
                @elseif($type === 'completed')
                    Đơn hàng đã giao thành công
                @elseif($type === 'cancelled')
                    Đơn hàng đã bị hủy
                @else
                    Xác nhận đặt hàng thành công
                @endif
            </p>
        </div>

        <div style="padding:24px;">
            <p style="margin-top:0;">
                Xin chào <strong>{{ $order->customer_name }}</strong>,
            </p>

            @if($type === 'paid')
                <p style="margin:0 0 16px;">
                    Chúng tôi đã nhận được thanh toán cho đơn hàng của bạn. Đơn hàng đang được xử lý.
                </p>
            @elseif($type === 'confirmed')
                <p style="margin:0 0 16px;">
                    Đơn hàng của bạn đã được cửa hàng xác nhận và sẽ sớm được xử lý.
                </p>
            @elseif($type === 'shipping')
                <p style="margin:0 0 16px;">
                    Đơn hàng của bạn đang được giao tới địa chỉ nhận hàng.
                </p>
            @elseif($type === 'completed')
                <p style="margin:0 0 16px;">
                    Đơn hàng của bạn đã được giao thành công. Cảm ơn bạn đã mua sắm tại cửa hàng của chúng tôi.
                </p>
            @elseif($type === 'cancelled')
                <p style="margin:0 0 16px;">
                    Đơn hàng của bạn đã bị hủy. Nếu cần hỗ trợ, vui lòng liên hệ cửa hàng.
                </p>
            @else
                <p style="margin:0 0 16px;">
                    Đơn hàng của bạn đã được tạo thành công. Chúng tôi sẽ sớm xử lý đơn hàng này.
                </p>
            @endif

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin:20px 0;">
                <p style="margin:0 0 8px;">
                    <strong>Mã đơn hàng:</strong> {{ $order->code }}
                </p>

                <p style="margin:0 0 8px;">
                    <strong>Khách hàng:</strong> {{ $order->customer_name }}
                </p>

                <p style="margin:0 0 8px;">
                    <strong>Email:</strong> {{ $order->customer_email }}
                </p>

                <p style="margin:0 0 8px;">
                    <strong>Số điện thoại:</strong> {{ $order->customer_phone }}
                </p>

                <p style="margin:0 0 8px;">
                    <strong>Phương thức thanh toán:</strong>
                    @if($order->payment_method === 'cod')
                        Thanh toán khi nhận hàng (COD)
                    @elseif($order->payment_method === 'vnpay')
                        Thanh toán qua VNPAY
                    @elseif($order->payment_method === 'momo')
                        Thanh toán qua MOMO
                    @else
                        {{ strtoupper((string) $order->payment_method) }}
                    @endif
                </p>

                <p style="margin:0 0 8px;">
                    <strong>Trạng thái thanh toán:</strong>
                    @if($order->payment_status === 'paid' || $order->status === 'completed')
                        Đã thanh toán
                    @elseif($order->payment_status === 'failed')
                        Thanh toán thất bại
                    @else
                        Chưa thanh toán
                    @endif
                </p>

                <p style="margin:0 0 8px;">
                    <strong>Trạng thái đơn hàng:</strong>
                    @if($order->status === 'pending')
                        Chờ xác nhận
                    @elseif($order->status === 'confirmed')
                        Đã xác nhận
                    @elseif($order->status === 'processing')
                        Đang xử lý
                    @elseif($order->status === 'shipping')
                        Đang giao hàng
                    @elseif($order->status === 'completed')
                        Hoàn thành
                    @elseif($order->status === 'cancelled')
                        Đã hủy
                    @else
                        {{ $order->status }}
                    @endif
                </p>

                <p style="margin:0;">
                    <strong>Tổng tiền:</strong> {{ number_format((float) $order->grand_total, 0, ',', '.') }}đ
                </p>
            </div>

            <h3 style="margin:24px 0 12px; color:#0f172a;">Chi tiết sản phẩm</h3>

            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr>
                        <th align="left" style="padding:12px; border:1px solid #e2e8f0; background:#f1f5f9;">Sản phẩm</th>
                        <th align="left" style="padding:15px; border:1px solid #e2e8f0; background:#f1f5f9;">Size / Màu</th>
                        <th align="center" style="padding:12px; border:1px solid #e2e8f0; background:#f1f5f9;">SL</th>
                        <th align="right" style="padding:12px; border:1px solid #e2e8f0; background:#f1f5f9;">Giá</th>
                        <th align="right" style="padding:12px; border:1px solid #e2e8f0; background:#f1f5f9;">Thành tiền</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($order->items as $item)
                        <tr>
                            <td style="padding:12px; border:1px solid #e2e8f0; vertical-align:top;">
                                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                    <tr>
                                        <td style="vertical-align:top;">
                                            <div style="font-weight:600; color:#0f172a; margin-bottom:4px;">
                                                {{ $item->product_name }}
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>

                            <td style="padding:15px; border:1px solid #e2e8f0; vertical-align:top;">
                                <div>Size: {{ $item->size ?: '-' }}</div>
                                <div style="margin-top:4px;">Màu: {{ $item->color ?: '-' }}</div>
                            </td>

                            <td align="center" style="padding:12px; border:1px solid #e2e8f0; vertical-align:top;">
                                {{ (int) $item->quantity }}
                            </td>

                            <td align="right" style="padding:12px; border:1px solid #e2e8f0; vertical-align:top;">
                                {{ number_format((float) $item->unit_price, 0, ',', '.') }}đ
                            </td>

                            <td align="right" style="padding:12px; border:1px solid #e2e8f0; vertical-align:top;">
                                {{ number_format((float) $item->line_total, 0, ',', '.') }}đ
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:16px; border:1px solid #e2e8f0; text-align:center; color:#64748b;">
                                Không có sản phẩm trong đơn hàng.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="margin-top:20px;">
                @if(!empty($order->shipping_address))
                    <p style="margin:0 0 8px;">
                        <strong>Địa chỉ nhận hàng:</strong> {{ $order->shipping_address }}
                    </p>
                @endif

                @if(!empty($order->note))
                    <p style="margin:0 0 8px;">
                        <strong>Ghi chú:</strong> {{ $order->note }}
                    </p>
                @endif
            </div>

            <p style="margin-top:24px;">
                Cảm ơn bạn đã mua sắm tại cửa hàng của chúng tôi.
            </p>
        </div>
    </div>
</body>
</html>
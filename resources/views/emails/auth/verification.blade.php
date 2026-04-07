<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác nhận email đăng ký</title>
</head>
<body style="margin:0; padding:20px; background:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#334155;">
    <div style="max-width:600px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
        <div style="background:#0f172a; color:#ffffff; padding:20px 24px;">
            <h1 style="margin:0; font-size:24px;">BMC Shoes</h1>
            <p style="margin:8px 0 0; font-size:14px; opacity:.9;">
                Xác nhận email đăng ký
            </p>
        </div>

        <div style="padding:24px;">
            <p style="margin-top:0;">
                Xin chào,
            </p>

            <p style="margin:0 0 16px;">
                Cảm ơn bạn đã đăng ký tài khoản <strong>{{ $email }}</strong>.
                Dưới đây là mã xác nhận của bạn:
            </p>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin:20px 0; text-align:center;">
                <p style="margin:0 0 8px; font-size:14px; color:#64748b;">Mã xác nhận</p>
                <p style="margin:0; font-size:36px; font-weight:bold; letter-spacing:8px; color:#0f172a;">
                    {{ $code }}
                </p>
            </div>

            <p style="margin:0 0 16px; font-size:13px; color:#64748b;">
                Mã này sẽ hết hạn sau <strong>15 phút</strong>. Vui lòng không chia sẻ mã này với bất kỳ ai.
            </p>

            <p style="margin:0 0 16px; font-size:13px; color:#64748b;">
                Nếu bạn không thực hiện đăng ký, vui lòng bỏ qua email này.
            </p>

            <hr style="border:none; border-top:1px solid #e2e8f0; margin:20px 0;">

            <p style="margin:0; font-size:12px; color:#94a3b8;">
                BMC Shoes &bull; Cảm ơn bạn đã tin tưởng
            </p>
        </div>
    </div>
</body>
</html>

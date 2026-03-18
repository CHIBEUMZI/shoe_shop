<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStoreRequest;
use App\Http\Resources\Public\OrderResource;
use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Models\Payment;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected InventoryService $inventoryService
    ) {
    }

    public function index(Request $request)
    {
        $orders = Order::query()
            ->with(['items', 'payments'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            abort(403, 'Bạn không có quyền xem đơn hàng này.');
        }

        $order->load(['items', 'payments']);

        return new OrderResource($order);
    }

    public function store(OrderStoreRequest $request)
    {
        try {
            $order = $this->orderService->createFromCart(
                $request->user(),
                $request->validated()
            );

            $order->load(['items', 'payments', 'items.variant']);

            if ($order->payment_method === 'cod') {
                $this->sendOrderMailIfNeeded($order, 'created');
            }

            return response()->json([
                'message' => 'Tạo đơn hàng thành công.',
                'data' => new OrderResource($order),
            ], 201);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Không thể tạo đơn hàng.',
            ], 500);
        }
    }

    public function createPayment(Request $request, Order $order)
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            abort(403, 'Bạn không có quyền thanh toán đơn hàng này.');
        }

        $order->load('payments');

        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Đơn hàng đã thanh toán.',
                'data' => new OrderResource($order->fresh(['items', 'payments'])),
            ]);
        }

        if ($order->payment_method === 'cod') {
            return response()->json([
                'message' => 'Đơn COD không cần tạo thanh toán online.',
            ], 422);
        }

        if ($order->payment_method === 'vnpay') {
            return $this->createVnpayPayment($request, $order);
        }

        if ($order->payment_method === 'momo') {
            return $this->createMomoPayment($request, $order);
        }

        return response()->json([
            'message' => 'Phương thức thanh toán chưa được hỗ trợ.',
        ], 422);
    }

    protected function createVnpayPayment(Request $request, Order $order)
    {
        $tmnCode = (string) config('services.vnpay.tmn_code');
        $hashSecret = (string) config('services.vnpay.hash_secret');
        $vnpUrl = (string) config('services.vnpay.url');
        $returnUrl = (string) config('services.vnpay.return_url');

        if ($tmnCode === '' || $hashSecret === '' || $vnpUrl === '' || $returnUrl === '') {
            return response()->json([
                'message' => 'Thiếu cấu hình VNPAY.',
            ], 500);
        }

        $now = now('Asia/Ho_Chi_Minh');
        $txnRef = $order->code . '-' . $now->format('YmdHis');
        $amount = (int) round(((float) $order->grand_total) * 100);

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'vnpay',
            'provider' => 'vnpay',
            'status' => 'pending',
            'amount' => $order->grand_total,
            'transaction_code' => $txnRef,
        ]);

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => $amount,
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $txnRef,
            'vnp_OrderInfo' => 'Thanh toan don hang ' . $order->code,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_IpAddr' => $request->ip(),
            'vnp_CreateDate' => $now->format('YmdHis'),
            'vnp_ExpireDate' => $now->copy()->addMinutes(15)->format('YmdHis'),
        ];

        if ($request->filled('bank_code')) {
            $inputData['vnp_BankCode'] = $request->string('bank_code')->toString();
        }

        ksort($inputData);

        $query = http_build_query($inputData);
        $hashData = urldecode($query);
        $secureHash = hash_hmac('sha512', $hashData, $hashSecret);

        $paymentUrl = $vnpUrl
            . '?'
            . $query
            . '&vnp_SecureHashType=SHA512'
            . '&vnp_SecureHash='
            . $secureHash;

        logger()->info('VNPAY_CREATE_PAYMENT', [
            'order_id' => $order->id,
            'txn_ref' => $txnRef,
            'amount' => $amount,
            'return_url' => $returnUrl,
            'hash_data' => $hashData,
            'secure_hash' => $secureHash,
        ]);

        return response()->json([
            'message' => 'Tạo URL thanh toán thành công.',
            'data' => [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'amount' => $order->grand_total,
                'payment_url' => $paymentUrl,
                'payment_id' => $payment->id,
            ],
        ]);
    }

    protected function createMomoPayment(Request $request, Order $order)
    {
        $partnerCode = (string) config('services.momo.partner_code');
        $accessKey = (string) config('services.momo.access_key');
        $secretKey = (string) config('services.momo.secret_key');
        $endpoint = (string) config('services.momo.endpoint');
        $redirectUrl = (string) config('services.momo.redirect_url');
        $ipnUrl = (string) config('services.momo.ipn_url');
        $requestType = (string) config('services.momo.request_type', 'captureWallet');

        if (
            $partnerCode === '' ||
            $accessKey === '' ||
            $secretKey === '' ||
            $endpoint === '' ||
            $redirectUrl === '' ||
            $ipnUrl === ''
        ) {
            return response()->json([
                'message' => 'Thiếu cấu hình MOMO.',
            ], 500);
        }

        $amount = (string) ((int) round((float) $order->grand_total));

        if ((int) $amount < 1000 || (int) $amount > 50000000) {
            return response()->json([
                'message' => 'Số tiền thanh toán MoMo phải từ 1.000đ đến 50.000.000đ.',
            ], 422);
        }

        $orderInfo = 'Thanh toán đơn hàng #' . $order->id;
        $orderId = $order->id . '_' . now()->timestamp;
        $requestId = 'REQ_' . $order->id . '_' . now()->timestamp;

        $extraData = base64_encode(json_encode([
            'order_id' => $order->id,
            'order_code' => $order->code,
            'user_id' => $order->user_id,
        ], JSON_UNESCAPED_UNICODE));

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'momo',
            'provider' => 'momo',
            'status' => 'pending',
            'amount' => $order->grand_total,
            'transaction_code' => $orderId,
        ]);

        $rawHash =
            "accessKey={$accessKey}" .
            "&amount={$amount}" .
            "&extraData={$extraData}" .
            "&ipnUrl={$ipnUrl}" .
            "&orderId={$orderId}" .
            "&orderInfo={$orderInfo}" .
            "&partnerCode={$partnerCode}" .
            "&redirectUrl={$redirectUrl}" .
            "&requestId={$requestId}" .
            "&requestType={$requestType}";

        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        $payload = [
            'partnerCode' => $partnerCode,
            'requestType' => $requestType,
            'ipnUrl' => $ipnUrl,
            'redirectUrl' => $redirectUrl,
            'orderId' => $orderId,
            'amount' => $amount,
            'orderInfo' => $orderInfo,
            'requestId' => $requestId,
            'extraData' => $extraData,
            'signature' => $signature,
            'lang' => 'vi',
            'autoCapture' => true,
            'userInfo' => [
                'name' => (string) ($order->customer_name ?? ''),
                'phoneNumber' => (string) ($order->customer_phone ?? ''),
                'email' => (string) ($order->customer_email ?? ''),
            ],
        ];

        try {
            $response = Http::timeout(30)->post($endpoint, $payload);
            $result = $response->json();

            logger()->info('MOMO_CREATE_PAYMENT', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'payload' => $payload,
                'response_status' => $response->status(),
                'response_body' => $result,
            ]);

            if (!$response->successful()) {
                $payment->update([
                    'status' => 'failed',
                    'response_payload' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ]);

                return response()->json([
                    'message' => 'Không gọi được API MOMO.',
                    'data' => $result,
                ], 422);
            }

            if ((int) ($result['resultCode'] ?? -1) !== 0 || empty($result['payUrl'])) {
                $payment->update([
                    'status' => 'failed',
                    'response_payload' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ]);

                return response()->json([
                    'message' => $result['message'] ?? 'Không lấy được payUrl từ MOMO.',
                    'data' => $result,
                ], 422);
            }

            $payment->update([
                'response_payload' => json_encode($result, JSON_UNESCAPED_UNICODE),
            ]);

            return response()->json([
                'message' => 'Tạo URL thanh toán thành công.',
                'data' => [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                    'amount' => $order->grand_total,
                    'payment_url' => $result['payUrl'],
                    'deeplink' => $result['deeplink'] ?? null,
                    'qr_code_url' => $result['qrCodeUrl'] ?? null,
                    'payment_id' => $payment->id,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            $payment->update([
                'status' => 'failed',
                'response_payload' => json_encode([
                    'exception' => $e->getMessage(),
                ], JSON_UNESCAPED_UNICODE),
            ]);

            return response()->json([
                'message' => 'Không thể tạo thanh toán MOMO.',
            ], 500);
        }
    }

    public function vnpayReturn(Request $request)
    {
        $params = $request->query();

        logger()->info('VNPAY_RETURN_QUERY', $params);

        if (!$this->verifyVnpaySignature($params)) {
            return redirect(
                config('services.vnpay.frontend_fail_url') . '?message=' . urlencode('Sai chữ ký VNPAY')
            );
        }

        $txnRef = $params['vnp_TxnRef'] ?? null;
        $responseCode = $params['vnp_ResponseCode'] ?? null;
        $transactionStatus = $params['vnp_TransactionStatus'] ?? null;

        $payment = Payment::query()
            ->where('transaction_code', $txnRef)
            ->latest()
            ->first();

        if (!$payment) {
            return redirect(
                config('services.vnpay.frontend_fail_url') . '?message=' . urlencode('Không tìm thấy giao dịch')
            );
        }

        $order = Order::query()
            ->with(['payments', 'items', 'items.variant'])
            ->find($payment->order_id);

        if (!$order) {
            return redirect(
                config('services.vnpay.frontend_fail_url') . '?message=' . urlencode('Không tìm thấy đơn hàng')
            );
        }

        try {
            if ($responseCode === '00' && ($transactionStatus === '00' || $transactionStatus === null)) {
                $this->markOrderAsPaid($order, $params, 'vnpay');

                return redirect(
                    rtrim((string) config('services.vnpay.frontend_success_url'), '/') . '/' . $order->id
                );
            }

            $this->markOrderAsFailed($order, $params, 'vnpay');

            return redirect(
                config('services.vnpay.frontend_fail_url')
                . '?order_id=' . $order->id
                . '&message=' . urlencode('Thanh toán thất bại')
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect(
                config('services.vnpay.frontend_fail_url')
                . '?order_id=' . $order->id
                . '&message=' . urlencode('Lỗi xử lý thanh toán')
            );
        }
    }

    public function vnpayIpn(Request $request)
    {
        $params = $request->query();

        logger()->info('VNPAY_IPN_QUERY', $params);

        if (!$this->verifyVnpaySignature($params)) {
            return response()->json([
                'RspCode' => '97',
                'Message' => 'Invalid signature',
            ]);
        }

        $txnRef = $params['vnp_TxnRef'] ?? null;
        $responseCode = $params['vnp_ResponseCode'] ?? null;
        $transactionStatus = $params['vnp_TransactionStatus'] ?? null;

        $payment = Payment::query()
            ->where('transaction_code', $txnRef)
            ->latest()
            ->first();

        if (!$payment) {
            return response()->json([
                'RspCode' => '01',
                'Message' => 'Transaction not found',
            ]);
        }

        $order = Order::query()
            ->with(['payments', 'items', 'items.variant'])
            ->find($payment->order_id);

        if (!$order) {
            return response()->json([
                'RspCode' => '01',
                'Message' => 'Order not found',
            ]);
        }

        try {
            if ($responseCode === '00' && ($transactionStatus === '00' || $transactionStatus === null)) {
                $this->markOrderAsPaid($order, $params, 'vnpay');
            } else {
                $this->markOrderAsFailed($order, $params, 'vnpay');
            }

            return response()->json([
                'RspCode' => '00',
                'Message' => 'Confirm Success',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'RspCode' => '99',
                'Message' => 'Unknown error',
            ]);
        }
    }

    public function momoReturn(Request $request)
    {
        $params = $request->query();

        logger()->info('MOMO_RETURN_QUERY', $params);

        if (!$this->verifyMomoSignature($params)) {
            return redirect(
                config('services.momo.frontend_fail_url') . '?message=' . urlencode('Sai chữ ký MOMO')
            );
        }

        $orderIdRaw = $params['orderId'] ?? null;
        $resultCode = (string) ($params['resultCode'] ?? '');

        if (!$orderIdRaw) {
            return redirect(
                config('services.momo.frontend_fail_url') . '?message=' . urlencode('Thiếu mã đơn hàng MoMo')
            );
        }

        $payment = Payment::query()
            ->where('transaction_code', $orderIdRaw)
            ->latest()
            ->first();

        if (!$payment) {
            return redirect(
                config('services.momo.frontend_fail_url') . '?message=' . urlencode('Không tìm thấy giao dịch')
            );
        }

        $order = Order::query()
            ->with(['payments', 'items', 'items.variant'])
            ->find($payment->order_id);

        if (!$order) {
            return redirect(
                config('services.momo.frontend_fail_url') . '?message=' . urlencode('Không tìm thấy đơn hàng')
            );
        }

        try {
            if ($resultCode === '0') {
                $this->markOrderAsPaid($order, $params, 'momo');

                return redirect(
                    rtrim((string) config('services.momo.frontend_success_url'), '/') . '/' . $order->id
                );
            }

            $this->markOrderAsFailed($order, $params, 'momo');

            return redirect(
                config('services.momo.frontend_fail_url')
                . '?order_id=' . $order->id
                . '&message=' . urlencode($params['message'] ?? 'Thanh toán thất bại')
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect(
                config('services.momo.frontend_fail_url')
                . '?order_id=' . $order->id
                . '&message=' . urlencode('Lỗi xử lý thanh toán')
            );
        }
    }

    public function momoIpn(Request $request)
    {
        $params = $request->all();

        logger()->info('MOMO_IPN_BODY', $params);

        if (!$this->verifyMomoSignature($params)) {
            return response()->json([
                'resultCode' => 97,
                'message' => 'Invalid signature',
            ]);
        }

        $orderIdRaw = $params['orderId'] ?? null;
        $resultCode = (int) ($params['resultCode'] ?? -1);

        $payment = Payment::query()
            ->where('transaction_code', $orderIdRaw)
            ->latest()
            ->first();

        if (!$payment) {
            return response()->json([
                'resultCode' => 1,
                'message' => 'Transaction not found',
            ]);
        }

        $order = Order::query()
            ->with(['payments', 'items', 'items.variant'])
            ->find($payment->order_id);

        if (!$order) {
            return response()->json([
                'resultCode' => 1,
                'message' => 'Order not found',
            ]);
        }

        try {
            if ($resultCode === 0) {
                $this->markOrderAsPaid($order, $params, 'momo');
            } else {
                $this->markOrderAsFailed($order, $params, 'momo');
            }

            return response()->json([
                'resultCode' => 0,
                'message' => 'Confirm Success',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'resultCode' => 99,
                'message' => 'Unknown error',
            ]);
        }
    }

    protected function verifyVnpaySignature(array $params): bool
    {
        $hashSecret = (string) config('services.vnpay.hash_secret');
        $receivedHash = (string) ($params['vnp_SecureHash'] ?? '');

        if ($hashSecret === '' || $receivedHash === '') {
            logger()->warning('VNPAY_VERIFY_MISSING_DATA', [
                'has_secret' => $hashSecret !== '',
                'has_received_hash' => $receivedHash !== '',
            ]);

            return false;
        }

        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);

        $filtered = [];
        foreach ($params as $key => $value) {
            if (str_starts_with((string) $key, 'vnp_')) {
                $filtered[$key] = $value;
            }
        }

        ksort($filtered);

        $hashData = urldecode(http_build_query($filtered));
        $computedHash = hash_hmac('sha512', $hashData, $hashSecret);

        logger()->info('VNPAY_VERIFY_SIGNATURE', [
            'hash_data' => $hashData,
            'computed_hash' => $computedHash,
            'received_hash' => $receivedHash,
            'is_match' => hash_equals(strtolower($computedHash), strtolower($receivedHash)),
        ]);

        return hash_equals(strtolower($computedHash), strtolower($receivedHash));
    }

    protected function verifyMomoSignature(array $params): bool
    {
        $secretKey = (string) config('services.momo.secret_key');
        $accessKey = (string) config('services.momo.access_key');
        $receivedSignature = (string) ($params['signature'] ?? '');

        if ($secretKey === '' || $accessKey === '' || $receivedSignature === '') {
            logger()->warning('MOMO_VERIFY_MISSING_DATA', [
                'has_secret' => $secretKey !== '',
                'has_access_key' => $accessKey !== '',
                'has_received_signature' => $receivedSignature !== '',
            ]);

            return false;
        }

        $rawHash =
            "accessKey={$accessKey}" .
            "&amount=" . ($params['amount'] ?? '') .
            "&extraData=" . ($params['extraData'] ?? '') .
            "&message=" . ($params['message'] ?? '') .
            "&orderId=" . ($params['orderId'] ?? '') .
            "&orderInfo=" . ($params['orderInfo'] ?? '') .
            "&orderType=" . ($params['orderType'] ?? '') .
            "&partnerCode=" . ($params['partnerCode'] ?? '') .
            "&payType=" . ($params['payType'] ?? '') .
            "&requestId=" . ($params['requestId'] ?? '') .
            "&responseTime=" . ($params['responseTime'] ?? '') .
            "&resultCode=" . ($params['resultCode'] ?? '') .
            "&transId=" . ($params['transId'] ?? '');

        $computedSignature = hash_hmac('sha256', $rawHash, $secretKey);

        logger()->info('MOMO_VERIFY_SIGNATURE', [
            'raw_hash' => $rawHash,
            'computed_signature' => $computedSignature,
            'received_signature' => $receivedSignature,
            'is_match' => hash_equals(strtolower($computedSignature), strtolower($receivedSignature)),
        ]);

        return hash_equals(strtolower($computedSignature), strtolower($receivedSignature));
    }

    protected function markOrderAsPaid(Order $order, array $payload = [], string $provider = 'vnpay'): void
    {
        DB::transaction(function () use ($order, $payload, $provider) {
            $payment = $order->payments()
                ->when($provider === 'vnpay' && isset($payload['vnp_TxnRef']), function ($query) use ($payload) {
                    $query->where('transaction_code', $payload['vnp_TxnRef']);
                })
                ->when($provider === 'momo' && isset($payload['orderId']), function ($query) use ($payload) {
                    $query->where('transaction_code', $payload['orderId']);
                })
                ->latest()
                ->first();

            if (!$payment) {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'method' => $provider,
                    'provider' => $provider,
                    'status' => 'pending',
                    'amount' => $order->grand_total,
                    'transaction_code' => $provider === 'vnpay'
                        ? ($payload['vnp_TxnRef'] ?? $order->code)
                        : ($payload['orderId'] ?? $order->code),
                ]);
            }

            if ($payment->status !== 'paid') {
                $payment->update([
                    'status' => 'paid',
                    'transaction_code' => $provider === 'vnpay'
                        ? ($payload['vnp_TxnRef'] ?? ($payment->transaction_code ?: $order->code))
                        : ($payload['orderId'] ?? ($payment->transaction_code ?: $order->code)),
                    'response_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'paid_at' => now(),
                ]);
            }

            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'status' => in_array($order->status, ['pending', 'confirmed'], true)
                    ? 'confirmed'
                    : $order->status,
            ]);
        });

        $freshOrder = $order->fresh(['items', 'payments', 'items.variant']);

        if (!$freshOrder->stock_deducted_at) {
            $this->inventoryService->deductStockForOrder($freshOrder);
            $freshOrder = $freshOrder->fresh(['items', 'payments', 'items.variant']);
        }

        $this->sendOrderMailIfNeeded($freshOrder, 'paid');
    }

    protected function markOrderAsFailed(Order $order, array $payload = [], string $provider = 'vnpay'): void
    {
        $payment = $order->payments()
            ->when($provider === 'vnpay' && isset($payload['vnp_TxnRef']), function ($query) use ($payload) {
                $query->where('transaction_code', $payload['vnp_TxnRef']);
            })
            ->when($provider === 'momo' && isset($payload['orderId']), function ($query) use ($payload) {
                $query->where('transaction_code', $payload['orderId']);
            })
            ->latest()
            ->first();

        if ($payment) {
            $payment->update([
                'status' => 'failed',
                'transaction_code' => $provider === 'vnpay'
                    ? ($payload['vnp_TxnRef'] ?? $payment->transaction_code)
                    : ($payload['orderId'] ?? $payment->transaction_code),
                'response_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
        }

        $order->update([
            'payment_status' => 'failed',
        ]);
    }

    protected function sendOrderMailIfNeeded(Order $order, string $type = 'created'): void
    {
        $freshOrder = $order->fresh(['items', 'payments', 'items.variant']);

        if (!$freshOrder) {
            return;
        }

        if (empty($freshOrder->customer_email)) {
            return;
        }

        if ($type === 'paid' && !empty($freshOrder->mail_sent_at)) {
            return;
        }

        Mail::to($freshOrder->customer_email)->queue(
            new OrderStatusMail($freshOrder, $type)
        );

        if ($type === 'paid' && empty($freshOrder->mail_sent_at)) {
            $freshOrder->update([
                'mail_sent_at' => now(),
            ]);
        }
    }
}
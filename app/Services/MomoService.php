<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MomoService
{
    public function createPayment(array $data): array
    {
        $partnerCode = config('services.momo.partner_code');
        $accessKey = config('services.momo.access_key');
        $secretKey = config('services.momo.secret_key');
        $endpoint = config('services.momo.endpoint');
        $returnUrl = config('services.momo.return_url');
        $notifyUrl = config('services.momo.notify_url');
        $requestType = config('services.momo.request_type', 'captureWallet');

        $orderId = $data['order_id'];
        $requestId = $data['request_id'] ?? $orderId . '_' . now()->timestamp;
        $amount = (string) ((int) $data['amount']);
        $orderInfo = $data['order_info'] ?? ('Thanh toán đơn hàng #' . $orderId);
        $extraData = $data['extra_data'] ?? '';
        $lang = $data['lang'] ?? 'vi';
        $redirectUrl = $data['redirect_url'] ?? $returnUrl;
        $ipnUrl = $data['ipn_url'] ?? $notifyUrl;

        $rawSignature =
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

        $signature = hash_hmac('sha256', $rawSignature, $secretKey);

        $payload = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'Shoe Shop',
            'storeId' => 'ShoeShopStore',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => $lang,
            'requestType' => $requestType,
            'autoCapture' => true,
            'extraData' => $extraData,
            'signature' => $signature,
        ];

        $response = Http::timeout(30)->post($endpoint, $payload);

        if (!$response->successful()) {
            throw new RuntimeException('Không gọi được MoMo API.');
        }

        $result = $response->json();

        if (!isset($result['resultCode']) || (int) $result['resultCode'] !== 0) {
            throw new RuntimeException($result['message'] ?? 'MoMo trả về lỗi.');
        }

        return $result;
    }

    public function verifySignature(array $data): bool
    {
        $secretKey = config('services.momo.secret_key');
        $receivedSignature = $data['signature'] ?? '';

        unset($data['signature']);

        ksort($data);

        $rawSignature = urldecode(http_build_query($data));

        $calculated = hash_hmac('sha256', $rawSignature, $secretKey);

        return hash_equals($calculated, $receivedSignature);
    }
}
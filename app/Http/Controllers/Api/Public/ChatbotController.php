<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function message(Request $request)
    {
        $text = trim((string) $request->input('message'));

        if ($text === '') {
            return response()->json([
                'error' => 'message is required',
            ], 422);
        }

        // Respond directly to clearly off-topic questions so the bot stays on-brand.
        if ($this->isOutOfScope($text)) {
            return response()->json([
                [
                    'text' => 'Mình chỉ hỗ trợ tư vấn giày và phụ kiện của BMC Shoes. Nếu bạn đang tìm giày, hãy cho mình biết mục đích sử dụng, size, thương hiệu hoặc tầm giá nhé.',
                ],
            ]);
        }

        // Use conversation_id if provided (from frontend), otherwise fallback
        // Using a unique ID per page load ensures fresh Rasa conversation context
        $sender = $request->input('conversation_id')
            ?? optional($request->user())->id
            ?? $request->ip()
            ?? 'anonymous';

        $rasaUrl = env('RASA_URL', 'http://rasa:5005/webhooks/rest/webhook');

        try {
            $response = Http::post($rasaUrl, [
                'sender'  => (string) $sender,
                'message' => $text,
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Cannot connect to chatbot server',
            ], 500);
        }
    }

    private function isOutOfScope(string $text): bool
    {
        $normalized = mb_strtolower($text, 'UTF-8');

        $shoeKeywords = [
            'giày', 'shoe', 'shoes', 'sneaker', 'sneakers', 'boot', 'boots',
            'sandal', 'sandals', 'dép', 'dep', 'running', 'chạy bộ', 'đá bóng',
            'bóng rổ', 'gym', 'tập gym', 'đi làm', 'đi học', 'đi chơi', 'du lịch',
            'size', 'màu', 'mau', 'giá', 'gia', 'khuyến mãi', 'sale',
        ];

        foreach ($shoeKeywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return false;
            }
        }

        return true;
    }
}

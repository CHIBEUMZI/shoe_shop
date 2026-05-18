<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // Use conversation_id if provided (from frontend), otherwise fallback.
        // A unique ID per page load ensures a fresh Rasa conversation context.
        $sender = (string) (
            $request->input('conversation_id')
            ?? optional($request->user())->id
            ?? $request->ip()
            ?? 'anonymous'
        );

        $contextKey = "chatbot_context:{$sender}";
        $context = Cache::get($contextKey, [
            'shoe_turns' => 0,
            'off_topic_turns' => 0,
            'last_topic' => null,
        ]);

        $decision = $this->detectContextDecision($text, $context);

        if ($decision === 'off_topic_reply') {
            $context['off_topic_turns']++;
            $context['last_topic'] = 'off_topic';
            Cache::put($contextKey, $context, now()->addHours(6));

            return response()->json([
                [
                    'text' => 'Mình chỉ hỗ trợ tư vấn giày và phụ kiện của BMC Shoes. Nếu bạn đang tìm giày, hãy cho mình biết mục đích sử dụng, size, thương hiệu hoặc tầm giá nhé.',
                ],
            ]);
        }

        if ($decision === 'shoe_followup') {
            $context['shoe_turns']++;
            $context['last_topic'] = 'shoe';
        } elseif ($decision === 'off_topic_soft') {
            $context['off_topic_turns']++;
            $context['last_topic'] = 'off_topic';
        }

        Cache::put($contextKey, $context, now()->addHours(6));

        $rasaUrl = env('RASA_URL', 'http://rasa:5005/webhooks/rest/webhook');

        try {
            $response = Http::post($rasaUrl, [
                'sender'  => (string) $sender,
                'message' => $text,
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Throwable) {
            return response()->json([
                'error' => 'Cannot connect to chatbot server',
            ], 500);
        }
    }

    private function detectContextDecision(string $text, array $context): string
    {
        $normalized = mb_strtolower($text, 'UTF-8');

        $shoeKeywords = [
            'giày', 'shoe', 'shoes', 'sneaker', 'sneakers', 'boot', 'boots',
            'sandal', 'sandals', 'dép', 'dep', 'running', 'chạy bộ', 'đá bóng',
            'bóng rổ', 'gym', 'tập gym', 'đi làm', 'đi học', 'đi chơi', 'du lịch',
            'size', 'màu', 'mau', 'giá', 'gia', 'khuyến mãi', 'sale',
        ];

        $shoeIntentKeywords = [
            'mua', 'tìm', 'chọn', 'tư vấn', 'gợi ý', 'đặt', 'order', 'cần', 'muốn',
            'tặng', 'quà', 'người yêu', 'bạn gái', 'bạn trai', 'sinh nhật', 'kỷ niệm',
            'valentine', 'noel', '8/3', '20/10', '20-10', '14/2', '14-2',
        ];

        $isShoeRelated = false;

        foreach ($shoeKeywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                $isShoeRelated = true;
                break;
            }
        }

        if (! $isShoeRelated) {
            foreach ($shoeIntentKeywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    $isShoeRelated = true;
                    break;
                }
            }
        }

        if ($isShoeRelated) {
            return 'shoe_followup';
        }

        if (($context['shoe_turns'] ?? 0) > 0 || ($context['last_topic'] ?? null) === 'shoe') {
            return 'off_topic_soft';
        }

        return 'off_topic_reply';
    }
}

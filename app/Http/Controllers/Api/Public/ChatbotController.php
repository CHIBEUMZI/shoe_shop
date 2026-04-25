<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function message(Request $request)
    {
        $text = $request->input('message');

        if (!$text) {
            return response()->json([
                'error' => 'message is required',
            ], 422);
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
}
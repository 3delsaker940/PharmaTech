<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class LLMService
{
    /**
     * إرسال نص أو بيانات لنموذج Gemini والحصول على الإجابة
     */
    public function generateContent(string $prompt): string
    {
        // 1. تجهيز الـ Payload
        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ];

        // 2. قراءة المفتاح من الـ Config أو .env
        $apiKey = config('services.gemini.key', env('GEMINI_API_KEY'));

        // 3. إرسال الطلب لـ Gemini API
        $response = Http::withHeaders([
            'Content-Type'   => 'application/json',
            'X-goog-api-key' => $apiKey,
        ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent', $payload);

        // 4. معالجة الفشل
        if ($response->failed()) {
            throw new Exception("فشل الاتصال بـ Gemini API: " . $response->body());
        }

        // 5. استخراج النص المُولد
        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'لم يتم توليد نص';
    }
}

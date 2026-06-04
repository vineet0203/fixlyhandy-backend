<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class IntentAnalyzerService
{
    /**
     * Analyze a raw job description and extract structured intent.
     *
     * @param  string $jobDescription
     * @return array  { service_type, scope_hints[], urgency, missing_info[], confidence }
     * @throws Exception
     */
    public function analyze(string $jobDescription): array
    {
        $apiKey = config('gemini.api_key');
        $model  = config('gemini.model', 'gemini-1.5-flash');

        if (!$apiKey) {
            throw new Exception('Gemini API key is not configured.');
        }

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $prompt = "Analyze this job description and return ONLY a raw valid JSON object (no markdown, no backticks):
{
  \"service_type\": \"string (e.g. cleaning, plumbing, electrical, landscaping, ios_development, painting, roofing, hvac)\",
  \"scope_hints\": [\"array of concrete scope details found in text e.g. 3 floors, urgent, 200 sqft\"],
  \"urgency\": \"urgent | normal | scheduled\",
  \"missing_info\": [\"array of what info is NOT provided e.g. location_size, materials, number_of_units\"],
  \"confidence\": 0.0
}

Job Description: \"{$jobDescription}\"

Rules:
- service_type must be a single lowercase slug, never null
- urgency: 'urgent' if words like urgent/emergency/ASAP found, 'scheduled' if date/time mentioned, else 'normal'
- missing_info: list what would help generate an accurate quote (max 4 items)
- confidence: 0.0-1.0 based on how much detail was provided
- Return ONLY the JSON object, nothing else";

        $payload = [
            'contents' => [
                [
                    'parts' => [['text' => $prompt]]
                ]
            ]
        ];

        $response = Http::post($endpoint, $payload);

        if ($response->failed()) {
            Log::error('IntentAnalyzer Gemini Error', ['response' => $response->body()]);
            throw new Exception('Failed to analyze job description intent.');
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            throw new Exception('Invalid response structure from Gemini API.');
        }

        // Strip markdown fences if model disobeyed
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/i', '', $text);
        $text = trim($text);

        $parsed = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('IntentAnalyzer JSON Parse Error', ['raw' => $text]);
            // Return a safe fallback so the pipeline never hard-crashes
            return [
                'service_type' => 'general',
                'scope_hints'  => [],
                'urgency'      => 'normal',
                'missing_info' => ['scope', 'location_size', 'materials'],
                'confidence'   => 0.2,
            ];
        }

        // Ensure required keys always exist
        return [
            'service_type' => $parsed['service_type'] ?? 'general',
            'scope_hints'  => $parsed['scope_hints']  ?? [],
            'urgency'      => $parsed['urgency']      ?? 'normal',
            'missing_info' => $parsed['missing_info'] ?? [],
            'confidence'   => (float) ($parsed['confidence'] ?? 0.3),
        ];
    }
}

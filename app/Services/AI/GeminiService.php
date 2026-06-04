<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Quote;
use App\Models\QuoteItem;

class GeminiService
{
    private function fetchContext(?int $clientId, string $jobType): array
    {
        $clientQuotes = collect();
        if ($clientId) {
            $clientQuotes = Quote::with('items')
                ->where('client_id', $clientId)
                ->whereIn('status', ['accepted', 'approved'])
                ->latest()
                ->take(5)
                ->get();
        }
        $similarQuotes = Quote::with('items')
            ->where('title', 'LIKE', "%{$jobType}%")
            ->whereIn('status', ['accepted', 'approved'])
            ->latest()
            ->take(5)
            ->get();
        $allQuotes = $clientQuotes->merge($similarQuotes)->unique('id');
        $contextLines = [];
        foreach ($allQuotes as $quote) {
            $clientType = $quote->client_type ?? 'unknown';
            $contextLines[] = "Quote: {$quote->title} | Total: {$quote->total_amount} | Client Type: {$clientType}";
            foreach ($quote->items as $item) {
                $contextLines[] = "  - Item: {$item->item_name} | Qty: {$item->quantity} | Unit Price: {$item->unit_price} | Tax: {$item->tax_rate}%";
            }
        }
        return $contextLines;
    }

    public function generateQuote(string $clientName, string $clientType, string $jobType, ?int $clientId = null): array
    {
        $apiKey = config('gemini.api_key');
        $model = config('gemini.model', 'gemini-1.5-flash');
        if (!$apiKey) throw new Exception("Gemini API key is not configured.");
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $contextData = $this->fetchContext($clientId, $jobType);
        $contextString = empty($contextData) ? "No past quote data available. Use reasonable market rates." : "Past quotes for reference:\n" . implode("\n", $contextData);
        $prompt = "You are a professional quote generator for a field service management company.\n\n{$contextString}\n\nGenerate a new quote based on:\n- Client: {$clientName} ({$clientType})\n- Job Type: {$jobType}\n\nReturn ONLY a raw valid JSON object, no markdown, no backticks, no explanation.\nExact structure:\n{\n  \"title\": \"Quote title string\",\n  \"notes\": \"Professional notes and payment terms\",\n  \"line_items\": [\n    {\n      \"item_name\": \"string\",\n      \"description\": \"string\",\n      \"quantity\": 1,\n      \"unit_price\": 100.00,\n      \"tax_rate\": 0\n    }\n  ]\n}";
        $payload = ['contents' => [['parts' => [['text' => $prompt]]]]];
        $response = Http::post($endpoint, $payload);
        if ($response->failed()) { Log::error('Gemini API Error', ['response' => $response->body()]); throw new Exception("Failed to generate quote from AI."); }
        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$text) throw new Exception("Invalid response structure from Gemini API.");
        $text = str_replace(['```json', '```'], '', $text);
        $text = trim($text);
        $parsed = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) { Log::error('Gemini API JSON Parse Error', ['raw_text' => $text]); throw new Exception("Failed to parse generated quote as JSON."); }
        return $parsed;
    }

    public function generateQuoteWithContext(array $richContext): array
    {
        $apiKey = config('gemini.api_key');
        $model  = config('gemini.model', 'gemini-1.5-flash');
        if (!$apiKey) throw new Exception('Gemini API key is not configured.');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $client          = $richContext['client'];
        $contextString   = $richContext['context_string'];
        $confidenceScore = $richContext['confidence_score'];
        $dataSource      = $richContext['data_source'];
        $urgency         = $richContext['urgency'];
        $serviceType     = $richContext['service_type'];
        $taxRate         = $client['is_tax_applicable'] ? $client['tax_percentage'] : 0;
        $currency        = $client['currency'];
        $prompt = "You are an expert quote generator.\n\n{$contextString}\n\nReturn ONLY a raw valid JSON object (no markdown, no backticks):\n{\n  \"title\": \"string\",\n  \"notes\": \"string\",\n  \"data_source\": \"{$dataSource}\",\n  \"confidence_score\": {$confidenceScore},\n  \"line_items\": [\n    {\n      \"item_name\": \"string\",\n      \"description\": \"string\",\n      \"quantity\": 1,\n      \"unit_price\": 100.00,\n      \"tax_rate\": {$taxRate}\n    }\n  ]\n}";
        $payload = ['contents' => [['parts' => [['text' => $prompt]]]]];
        $response = Http::post($endpoint, $payload);
        if ($response->failed()) { Log::error('Gemini generateQuoteWithContext Error', ['response' => $response->body()]); throw new Exception('Failed to generate quote from AI.'); }
        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$text) throw new Exception('Invalid response structure from Gemini API.');
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/i', '', $text);
        $text = trim($text);
        $parsed = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) { Log::error('Gemini generateQuoteWithContext JSON Parse Error', ['raw_text' => $text]); throw new Exception('Failed to parse AI-generated quote as JSON.'); }
        $parsed['confidence_score'] = $parsed['confidence_score'] ?? $confidenceScore;
        $parsed['data_source']      = $parsed['data_source']      ?? $dataSource;
        return $parsed;
    }

    public function generateLineItemsOnly(string $jobDescription, ?int $clientId = null): array
    {
        $apiKey = config('gemini.api_key');
        $model  = config('gemini.model', 'gemini-1.5-flash');
        if (!$apiKey) throw new Exception('Gemini API key is not configured.');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $contextData = $this->fetchContext($clientId, $jobDescription);
        $contextString = empty($contextData) ? 'No past quote data available. Use reasonable market rates.' : 'Past quotes for reference:\n' . implode('\n', $contextData);
        
        $prompt = "You are a professional quote/estimate line-item generator.

Context data:
{$contextString}

Task: Generate line items for this job request: \"{$jobDescription}\"

IMPORTANT RULE:
If the job request is completely unrelated to any kind of billable service, labor, goods, construction, software, or standard work (for example: random gibberish, casual greetings, or non-work related questions), you MUST return this exact JSON error:
{
  \"error\": \"This request does not seem to be a valid job description. Please provide relevant details for a quote.\"
}

Otherwise, generate realistic items and return ONLY raw valid JSON (no markdown, no backticks) in this format:
{
  \"line_items\": [
    {
      \"item_name\": \"string\",
      \"description\": \"string\",
      \"quantity\": 1,
      \"unit_price\": 100.00,
      \"tax_rate\": 0
    }
  ]
}";

        $payload = ['contents' => [['parts' => [['text' => $prompt]]]]];
        $response = Http::post($endpoint, $payload);
        if ($response->failed()) throw new Exception('Failed to generate line items from AI.');
        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$text) throw new Exception('Invalid response structure from Gemini API.');
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/i', '', $text);
        $text = trim($text);
        $parsed = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Failed to parse AI-generated line items as JSON.');
        
        if (isset($parsed['error'])) {
            throw new Exception($parsed['error']);
        }
        
        return $parsed;
    }
}

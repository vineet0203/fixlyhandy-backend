<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyGitHubWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::debug('=== GITHUB WEBHOOK VERIFICATION START ===', [
            'timestamp' => now()->toISOString(),
            'ip' => $request->ip(),
            'full_url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
        ]);

        // 1. POST check
        if (!$request->isMethod('post')) {
            return $this->logAndRespond('Non-POST request', 405, [
                'method' => $request->method()
            ]);
        }

        // 2. Get secret with validation
        $secret = config('github.webhook.secret');

        if (empty($secret)) {
            return $this->logAndRespond('Secret not configured', 500);
        }

        // Log secret info (without exposing it)
        Log::debug('Secret info', [
            'secret_length' => strlen($secret),
            'secret_exists' => !empty($secret),
        ]);

        // 3. Get signature
        $signature = $request->header('X-Hub-Signature-256');

        if (empty($signature)) {
            return $this->logAndRespond('Missing signature', 400, [
                'available_headers' => array_keys($request->headers->all()),
            ]);
        }

        // 4. Get raw payload - using multiple methods for reliability
        $payload1 = $request->getContent();
        $payload2 = file_get_contents('php://input');

        // Use the longer one (GitHub sends raw)
        $payload = strlen($payload1) > strlen($payload2) ? $payload1 : $payload2;

        if (empty($payload)) {
            return $this->logAndRespond('Empty payload', 400, [
                'payload1_length' => strlen($payload1),
                'payload2_length' => strlen($payload2),
            ]);
        }

        // 5. Calculate hash
        $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        // 6. Verify with detailed comparison
        if (!hash_equals($hash, $signature)) {
            // Log EVERYTHING for debugging
            Log::error('SIGNATURE MISMATCH DETAILS', [
                'secret_first_5' => substr($secret, 0, 5),
                'secret_last_5' => substr($secret, -5),
                'payload_length' => strlen($payload),
                'payload_md5' => md5($payload),
                'payload_first_50' => substr($payload, 0, 50),
                'payload_last_50' => substr($payload, -50),
                'received_signature' => $signature,
                'calculated_signature' => $hash,
                'signature_match' => $signature === $hash,
                'timing' => now()->format('Y-m-d H:i:s.u'),
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // 7. Verify JSON
        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->logAndRespond('Invalid JSON', 400, [
                'error' => json_last_error_msg(),
                'payload_sample' => substr($payload, 0, 200),
            ]);
        }

        Log::info('GitHub webhook VERIFIED', [
            'event' => $request->header('X-GitHub-Event'),
            'delivery' => $request->header('X-GitHub-Delivery'),
            'signature_valid' => true,
            'payload_size' => strlen($payload),
        ]);

        return $next($request);
    }

    private function logAndRespond(string $message, int $code, array $context = []): Response
    {
        Log::warning("GitHub webhook: $message", $context);
        return response()->json(['error' => strtolower($message)], $code);
    }
}

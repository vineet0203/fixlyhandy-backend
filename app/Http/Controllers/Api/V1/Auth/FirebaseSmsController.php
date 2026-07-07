<?php

namespace App\Http\Controllers\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseSmsController
{
    /**
     * Send SMS OTP via Firebase REST API
     */
    public static function sendSmsOtp($phoneNumber)
    {
        try {
            $apiKey = env('FIREBASE_API_KEY');
            $endpoint = "https://identitytoolkit.googleapis.com/v1/accounts:sendVerificationCode?key={$apiKey}";

            $response = Http::post($endpoint, [
                'phoneNumber' => $phoneNumber,
                'recaptchaToken' => '',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Firebase SMS sent to {$phoneNumber}: Session ID " . ($data['sessionInfo'] ?? 'N/A'));
                return true;
            }

            Log::error("Firebase SMS failed for {$phoneNumber}: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("Firebase SMS exception for {$phoneNumber}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify SMS OTP via Firebase REST API
     */
    public static function verifySmsOtp($phoneNumber, $otp, $sessionInfo)
    {
        try {
            $apiKey = env('FIREBASE_API_KEY');
            $endpoint = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPhoneNumber?key={$apiKey}";

            $response = Http::post($endpoint, [
                'phoneNumber' => $phoneNumber,
                'verificationCode' => $otp,
                'sessionInfo' => $sessionInfo,
            ]);

            if ($response->successful()) {
                Log::info("Firebase SMS verified for {$phoneNumber}");
                return $response->json();
            }

            Log::error("Firebase SMS verification failed for {$phoneNumber}: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Firebase SMS verification exception for {$phoneNumber}: " . $e->getMessage());
            return null;
        }
    }
}

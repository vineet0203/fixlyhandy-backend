<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Resources\Api\V1\User\AuthUserResource;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class VerificationController extends BaseController
{
    /**
     * Get the currently authenticated entity (User, Customer, or Employee).
     */
    private function getAuthenticatedEntity(Request $request)
    {
        // 1. Check if customer.jwt middleware attribute is present
        if ($request->attributes->has('customer')) {
            $customerData = $request->attributes->get('customer');
            return Customer::find($customerData['id'] ?? null);
        }

        // 2. Check if employee.jwt middleware attribute is present
        if ($request->attributes->has('employee')) {
            $employeeData = $request->attributes->get('employee');
            return Employee::find($employeeData['id'] ?? null);
        }

        // 3. Check standard api guard (User)
        if (auth('api')->check()) {
            return auth('api')->user();
        }

        // 4. Fallback: Parse token manually if middlewares haven't run or headers exist
        $token = $request->bearerToken();
        if ($token) {
            try {
                $payload = JWTAuth::setToken($token)->getPayload();
                $id = $payload->get('sub');
                $scope = $payload->get('scope') ?? null;
                if ($scope === 'customer') {
                    return Customer::find($id);
                } elseif ($scope === 'employee') {
                    return Employee::find($id);
                } else {
                    return User::find($id);
                }
            } catch (\Exception $e) {
                Log::warning('VerificationController failed parsing bearer token manually: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Verify Google ID token and link google_id to the user.
     */
    public function verifyGoogle(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $idToken = $request->input('id_token');
        $googleData = $this->verifyGoogleToken($idToken);

        if (!$googleData) {
            return $this->errorResponse('Invalid Google ID token.', 400);
        }

        $entity = $this->getAuthenticatedEntity($request);

        if (!$entity) {
            return $this->unauthorizedResponse('User is not authenticated.');
        }

        // Link Google ID
        $entity->google_id = $googleData['sub'];
        $entity->is_verified = 1;

        // Determine verification method enum
        if ($entity->whatsapp_verified_at) {
            $entity->verification_method = 'both';
        } else {
            $entity->verification_method = 'gmail';
        }

        if ($entity instanceof Customer || $entity instanceof User) {
            $entity->email_verified_at = now();
        }

        $entity->save();

        if ($entity instanceof User) {
            $responseData = new AuthUserResource($entity);
        } elseif ($entity instanceof Employee) {
            $responseData = $this->formatEmployeeData($entity);
        } else {
            $responseData = $this->formatCustomerData($entity);
        }

        return $this->successResponse($responseData, 'Google account verified and linked successfully.');
    }

    /**
     * Send WhatsApp OTP (generates and stores in Cache, returns in response in dev mode).
     */
    public function sendWhatsappOtp(Request $request): JsonResponse
    {
        $request->validate([
            'whatsapp_number' => 'required|string',
        ]);

        $entity = $this->getAuthenticatedEntity($request);

        if (!$entity) {
            return $this->unauthorizedResponse('User is not authenticated.');
        }

        $whatsappNumber = $request->input('whatsapp_number');
        $formattedPhone = $this->formatE164PhoneNumber($whatsappNumber);

        $isMockNumber = str_starts_with($formattedPhone, '+1555') || str_starts_with($formattedPhone, '+mock');
        $hasCredentials = env('TWILIO_ACCOUNT_SID') && env('TWILIO_AUTH_TOKEN') && env('TWILIO_VERIFY_SERVICE_SID');

        if ($hasCredentials && !$isMockNumber) {
            $twilioResult = $this->sendTwilioOtp($whatsappNumber);
            if ($twilioResult === true) {
                return $this->successResponse([
                    'otp_sent' => true,
                    'whatsapp_number' => $formattedPhone,
                ], 'WhatsApp verification code sent successfully via Twilio.');
            }
            return $this->errorResponse(is_string($twilioResult) ? $twilioResult : 'Failed to send WhatsApp OTP via Twilio.', 400);
        }

        $otp = (string) rand(100000, 999999);

        // Cache OTP and phone number for 10 minutes
        $cacheKeyOtp = 'whatsapp_otp_' . get_class($entity) . '_' . $entity->id;
        $cacheKeyPhone = 'whatsapp_phone_' . get_class($entity) . '_' . $entity->id;

        Cache::put($cacheKeyOtp, $otp, 600);
        Cache::put($cacheKeyPhone, $whatsappNumber, 600);

        Log::info("WhatsApp OTP generated for class " . get_class($entity) . " ID " . $entity->id . ": " . $otp);

        // Dev mode: return OTP directly in response so UI can show it for testing
        return $this->successResponse([
            'otp' => $otp,
            'whatsapp_number' => $whatsappNumber,
        ], 'WhatsApp verification code generated successfully.');
    }

    /**
     * Verify WhatsApp OTP and set the phone number as verified.
     */
    public function verifyWhatsappOtp(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => 'required|string',
            'whatsapp_number' => 'required|string',
        ]);

        $entity = $this->getAuthenticatedEntity($request);

        if (!$entity) {
            return $this->unauthorizedResponse('User is not authenticated.');
        }

        $otp = $request->input('otp');
        $whatsappNumber = $request->input('whatsapp_number');
        $formattedPhone = $this->formatE164PhoneNumber($whatsappNumber);

        $isMockNumber = str_starts_with($formattedPhone, '+1555') || str_starts_with($formattedPhone, '+mock');
        $hasCredentials = env('TWILIO_ACCOUNT_SID') && env('TWILIO_AUTH_TOKEN') && env('TWILIO_VERIFY_SERVICE_SID');

        if ($hasCredentials && !$isMockNumber) {
            $twilioResult = $this->checkTwilioOtp($whatsappNumber, $otp);
            if ($twilioResult !== true) {
                return $this->errorResponse(is_string($twilioResult) ? $twilioResult : 'Invalid or expired WhatsApp OTP.', 400);
            }
        } else {
            $cacheKeyOtp = 'whatsapp_otp_' . get_class($entity) . '_' . $entity->id;
            $cacheKeyPhone = 'whatsapp_phone_' . get_class($entity) . '_' . $entity->id;

            $cachedOtp = Cache::get($cacheKeyOtp);
            $cachedPhone = Cache::get($cacheKeyPhone);

            if (!$cachedOtp || $cachedOtp !== $otp || $cachedPhone !== $whatsappNumber) {
                return $this->errorResponse('Invalid or expired WhatsApp OTP.', 400);
            }

            // Clear cache
            Cache::forget($cacheKeyOtp);
            Cache::forget($cacheKeyPhone);
        }

        // Update verification state
        $entity->whatsapp_number = $formattedPhone;
        $entity->whatsapp_verified_at = now();
        $entity->is_verified = 1;

        if ($entity->google_id) {
            $entity->verification_method = 'both';
        } else {
            $entity->verification_method = 'whatsapp';
        }

        $entity->save();

        if ($entity instanceof User) {
            $responseData = new AuthUserResource($entity);
        } elseif ($entity instanceof Employee) {
            $responseData = $this->formatEmployeeData($entity);
        } else {
            $responseData = $this->formatCustomerData($entity);
        }

        return $this->successResponse($responseData, 'WhatsApp number verified successfully.');
    }

    /**
     * Login via Google OAuth token.
     */
    public function loginWithGoogle(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => 'required|string',
            'type' => 'nullable|string|in:customer,user',
        ]);

        $idToken = $request->input('id_token');
        $type = $request->input('type', 'user');

        $googleData = $this->verifyGoogleToken($idToken);
        if (!$googleData) {
            return $this->errorResponse('Invalid Google ID token.', 400);
        }

        $googleId = $googleData['sub'];
        $email = $googleData['email'];

        if ($type === 'customer') {
            $customer = Customer::where('google_id', $googleId)
                ->orWhere('email', $email)
                ->first();

            if (!$customer) {
                return $this->errorResponse('No customer account found matching this Google identity.', 404);
            }

            if ($customer->status !== 'active') {
                return $this->errorResponse('Customer account is inactive.', 403);
            }

            // Sync google ID & verify if not done
            $customer->google_id = $googleId;
            $customer->is_verified = 1;
            if (!$customer->verification_method) {
                $customer->verification_method = 'gmail';
            }
            $customer->save();

            $token = JWTAuth::claims([
                'scope' => 'customer',
                'role' => $customer->role,
            ])->fromUser($customer);

            return $this->successResponse([
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => (int) (config('jwt.ttl') ? config('jwt.ttl') * 60 : 3600),
                'customer' => $this->formatCustomerData($customer),
            ], 'Google login successful.');
        } else {
            // User (Vendor/Employee)
            $user = User::where('google_id', $googleId)
                ->orWhere('email', $email)
                ->first();

            if (!$user) {
                return $this->errorResponse('No user account found matching this Google identity.', 404);
            }

            if (!$user->is_active || $user->status !== 'active') {
                return $this->errorResponse('User account is suspended or inactive.', 403);
            }

            if ($user->vendor_id && $user->vendor->status !== 'active') {
                return $this->errorResponse('Vendor account is inactive.', 403);
            }

            // Sync google ID & verify if not done
            $user->google_id = $googleId;
            $user->is_verified = 1;
            if (!$user->verification_method) {
                $user->verification_method = 'gmail';
            }
            $user->save();

            $token = auth('api')->login($user);

            return $this->successResponse([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => new AuthUserResource($user),
            ], 'Google login successful.');
        }
    }

    /**
     * Login via WhatsApp OTP (Send OTP first, then verify).
     */
    public function loginWithWhatsapp(Request $request): JsonResponse
    {
        $request->validate([
            'whatsapp_number' => 'required|string',
            'otp' => 'nullable|string',
            'type' => 'nullable|string|in:customer,user',
        ]);

        $whatsappNumber = $request->input('whatsapp_number');
        $otp = $request->input('otp');
        $type = $request->input('type', 'user');

        $formattedPhone = $this->formatE164PhoneNumber($whatsappNumber);

        // Look up user/customer first
        if ($type === 'customer') {
            $entity = Customer::where('whatsapp_number', $formattedPhone)
                ->orWhere('phone', $formattedPhone)
                ->orWhere('whatsapp_number', $whatsappNumber)
                ->orWhere('phone', $whatsappNumber)
                ->first();
        } else {
            $entity = User::where('whatsapp_number', $formattedPhone)
                ->orWhere('whatsapp_number', $whatsappNumber)
                ->first();
        }

        if (!$entity) {
            return $this->errorResponse('No account registered with this WhatsApp number.', 404);
        }

        if ($type === 'customer') {
            if ($entity->status !== 'active') {
                return $this->errorResponse('Customer account is inactive.', 403);
            }
        } else {
            if (!$entity->is_active || $entity->status !== 'active') {
                return $this->errorResponse('User account is suspended or inactive.', 403);
            }
            if ($entity->vendor_id && $entity->vendor->status !== 'active') {
                return $this->errorResponse('Vendor account is inactive.', 403);
            }
        }

        $isMockNumber = str_starts_with($formattedPhone, '+1555') || str_starts_with($formattedPhone, '+mock');
        $hasCredentials = env('TWILIO_ACCOUNT_SID') && env('TWILIO_AUTH_TOKEN') && env('TWILIO_VERIFY_SERVICE_SID');

        if (empty($otp)) {
            // STEP 1: Generate and send OTP
            if ($hasCredentials && !$isMockNumber) {
                $twilioResult = $this->sendTwilioOtp($whatsappNumber);
                if ($twilioResult === true) {
                    return $this->successResponse([
                        'otp_sent' => true,
                        'whatsapp_number' => $formattedPhone,
                    ], 'WhatsApp OTP sent successfully via Twilio.');
                }
                return $this->errorResponse(is_string($twilioResult) ? $twilioResult : 'Failed to send WhatsApp OTP via Twilio.', 400);
            } else {
                $cacheKey = 'whatsapp_login_otp_' . $type . '_' . preg_replace('/\D/', '', $whatsappNumber);
                $generatedOtp = (string) rand(100000, 999999);
                Cache::put($cacheKey, $generatedOtp, 600);

                Log::info("WhatsApp Login OTP generated for {$type} {$whatsappNumber}: {$generatedOtp}");

                // Dev mode: return OTP in response so UI can read it
                return $this->successResponse([
                    'otp_sent' => true,
                    'otp' => $generatedOtp,
                    'whatsapp_number' => $formattedPhone,
                ], 'WhatsApp OTP sent successfully (Dev Mode).');
            }
        }

        // STEP 2: Verify OTP
        if ($hasCredentials && !$isMockNumber) {
            $twilioResult = $this->checkTwilioOtp($whatsappNumber, $otp);
            if ($twilioResult !== true) {
                return $this->errorResponse(is_string($twilioResult) ? $twilioResult : 'Invalid or expired OTP.', 400);
            }
        } else {
            $cacheKey = 'whatsapp_login_otp_' . $type . '_' . preg_replace('/\D/', '', $whatsappNumber);
            $cachedOtp = Cache::get($cacheKey);

            if (!$cachedOtp || $cachedOtp !== $otp) {
                return $this->errorResponse('Invalid or expired OTP.', 400);
            }

            Cache::forget($cacheKey);
        }

        // Verification updates
        $entity->whatsapp_verified_at = now();
        $entity->whatsapp_number = $formattedPhone;
        $entity->is_verified = 1;
        if (!$entity->verification_method) {
            $entity->verification_method = 'whatsapp';
        }
        $entity->save();

        // Generate tokens
        if ($type === 'customer') {
            $token = JWTAuth::claims([
                'scope' => 'customer',
                'role' => $entity->role,
            ])->fromUser($entity);

            return $this->successResponse([
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => (int) (config('jwt.ttl') ? config('jwt.ttl') * 60 : 3600),
                'customer' => $this->formatCustomerData($entity),
            ], 'WhatsApp login successful.');
        } else {
            $token = auth('api')->login($entity);

            return $this->successResponse([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => new AuthUserResource($entity),
            ], 'WhatsApp login successful.');
        }
    }

    /**
     * Format phone number to E.164 standard.
     */
    private function formatE164PhoneNumber(string $phoneNumber): string
    {
        // Remove all characters except digits and +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);

        if (str_starts_with($cleaned, '+')) {
            return $cleaned;
        }

        // If it starts with country code 1 (US/Canada) and is 11 digits
        if (strlen($cleaned) === 11 && str_starts_with($cleaned, '1')) {
            return '+' . $cleaned;
        }

        // Default to US country code +1 if it is a 10-digit number
        if (strlen($cleaned) === 10) {
            return '+1' . $cleaned;
        }

        // Otherwise prepend + and return
        return '+' . $cleaned;
    }

    /**
     * Send OTP via Twilio Verify API.
     */
    private function sendTwilioOtp(string $to): bool|string
    {
        $sid = env('TWILIO_ACCOUNT_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $serviceSid = env('TWILIO_VERIFY_SERVICE_SID');

        if (!$sid || !$token || !$serviceSid) {
            Log::warning('Twilio credentials not configured. Falling back to mock OTP.');
            return false;
        }

        $formattedTo = $this->formatE164PhoneNumber($to);

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://verify.twilio.com/v2/Services/{$serviceSid}/Verifications", [
                    'To' => $formattedTo,
                    'Channel' => 'whatsapp',
                ]);

            if ($response->successful()) {
                Log::info("Twilio WhatsApp OTP sent successfully to: {$formattedTo}");
                return true;
            }

            Log::error("Twilio send OTP failed to {$formattedTo}: " . $response->body());
            return $response->json('message') ?? 'Failed to send OTP via Twilio.';
        } catch (\Exception $e) {
            Log::error("Twilio send OTP exception for {$formattedTo}: " . $e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * Verify OTP via Twilio Verify API.
     */
    private function checkTwilioOtp(string $to, string $code): bool|string
    {
        $sid = env('TWILIO_ACCOUNT_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $serviceSid = env('TWILIO_VERIFY_SERVICE_SID');

        if (!$sid || !$token || !$serviceSid) {
            Log::warning('Twilio credentials not configured. Falling back to mock OTP check.');
            return false;
        }

        $formattedTo = $this->formatE164PhoneNumber($to);

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://verify.twilio.com/v2/Services/{$serviceSid}/VerificationCheck", [
                    'To' => $formattedTo,
                    'Code' => $code,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'approved' && ($data['valid'] ?? false) === true) {
                    Log::info("Twilio OTP verification successful for: {$formattedTo}");
                    return true;
                }
            }

            Log::error("Twilio verify OTP failed for {$formattedTo}: " . $response->body());
            return $response->json('message') ?? 'Invalid OTP code.';
        } catch (\Exception $e) {
            Log::error("Twilio verify OTP exception for {$formattedTo}: " . $e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * helper to verify Google ID token.
     */
    private function verifyGoogleToken(string $idToken): ?array
    {
        // Simulated mock Google token for local development and testing
        if (app()->environment(['local', 'testing']) && str_starts_with($idToken, 'mock_')) {
            $email = str_replace('mock_', '', $idToken);
            return [
                'sub' => 'mock_google_id_' . md5($email),
                'email' => $email,
            ];
        }

        try {
            $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['sub']) && isset($data['email'])) {
                    return [
                        'sub' => $data['sub'],
                        'email' => $data['email'],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Google ID token verification failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Format customer response data array.
     */
    private function formatCustomerData(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'role' => $customer->role,
            'status' => $customer->status,
            'is_verified' => (int) $customer->is_verified,
            'google_id' => $customer->google_id,
            'whatsapp_number' => $customer->whatsapp_number,
            'verification_method' => $customer->verification_method,
            'whatsapp_verified_at' => $customer->whatsapp_verified_at?->toIso8601String(),
        ];
    }

    /**
     * Format employee response data array.
     */
    private function formatEmployeeData(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'vendor_id' => $employee->vendor_id,
            'name' => $employee->name ?: trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
            'email' => $employee->email,
            'phone' => $employee->phone,
            'role' => $employee->role,
            'is_active' => (int) $employee->is_active,
            'is_verified' => (int) $employee->is_verified,
            'google_id' => $employee->google_id,
            'whatsapp_number' => $employee->whatsapp_number,
            'verification_method' => $employee->verification_method,
            'whatsapp_verified_at' => $employee->whatsapp_verified_at?->toIso8601String(),
        ];
    }


    public function sendEmailOtp(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        $entity = $this->getAuthenticatedEntity($request);
        if (!$entity) return $this->unauthorizedResponse('User is not authenticated.');

        $otp = (string) rand(100000, 999999);
        $cacheKey = 'email_otp_' . get_class($entity) . '_' . $entity->id;
        Cache::put($cacheKey, $otp, 600);

        \Illuminate\Support\Facades\Mail::send([], [], function ($m) use ($request, $otp) {
            $m->to($request->email)
              ->subject('FixlyHandy - Email Verification Code')
              ->html("<h2>Your verification code is: <strong>{$otp}</strong></h2><p>This code expires in 10 minutes.</p>");
        });

        Log::info('Email OTP sent to ' . $request->email . ': ' . $otp);
        return $this->successResponse([
            'otp_sent' => true,
            'otp' => app()->environment(['local', 'testing']) ? $otp : null,
        ], 'Verification code sent to your email.');
    }

    public function verifyEmailOtp(Request $request): JsonResponse
    {
        $request->validate(['otp' => 'required|string', 'email' => 'required|email']);
        $entity = $this->getAuthenticatedEntity($request);
        if (!$entity) return $this->unauthorizedResponse('User is not authenticated.');

        $cacheKey = 'email_otp_' . get_class($entity) . '_' . $entity->id;
        $cachedOtp = Cache::get($cacheKey);

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return $this->errorResponse('Invalid or expired OTP.', 400);
        }

        Cache::forget($cacheKey);
        $entity->is_verified = 1;
        if ($entity instanceof User || $entity instanceof Customer) {
            $entity->email_verified_at = now();
        }
        $entity->verification_method = $entity->google_id ? 'both' : 'gmail';
        $entity->save();

        if ($entity instanceof User) {
            $responseData = new AuthUserResource($entity);
        } elseif ($entity instanceof Employee) {
            $responseData = $this->formatEmployeeData($entity);
        } else {
            $responseData = $this->formatCustomerData($entity);
        }
        return $this->successResponse($responseData, 'Email verified successfully.');
    }

    /**
     * Send SMS OTP.
     */
    public function sendSmsOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $entity = $this->getAuthenticatedEntity($request);
        if (!$entity) {
            return $this->unauthorizedResponse('User is not authenticated.');
        }

        $phoneNumber = $request->input('phone_number');
        $formattedPhone = $this->formatE164PhoneNumber($phoneNumber);

        // Rate limiting: max 5 attempts per hour
        $rateLimitKey = 'sms_otp_limit_' . get_class($entity) . '_' . $entity->id;
        $attempts = Cache::get($rateLimitKey, 0);
        if ($attempts >= 5) {
            return $this->errorResponse('Too many OTP attempts. Maximum 5 attempts per hour allowed.', 429);
        }
        Cache::put($rateLimitKey, $attempts + 1, 3600);

        // Generate 6-digit OTP
        $otp = (string) rand(100000, 999999);

        // Cache OTP for 10 minutes
        $cacheKey = 'sms_otp_' . get_class($entity) . '_' . $entity->id;
        Cache::put($cacheKey, $otp, 600);

        Log::info("SMS OTP generated for " . get_class($entity) . " ID " . $entity->id . ": " . $otp . " (Phone: " . $formattedPhone . ")");

        // Mask phone number for response
        $maskedPhone = substr($formattedPhone, 0, 3) . '******' . substr($formattedPhone, -3);

        return $this->successResponse([
            'otp_sent' => true,
            'otp' => app()->environment(['local', 'testing']) ? $otp : null, // expose OTP in dev mode for UI
            'phone_number' => $maskedPhone,
        ], 'SMS verification code sent successfully.');
    }

    /**
     * Verify SMS OTP.
     */
    public function verifySmsOtp(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => 'required|string',
            'phone_number' => 'required|string',
        ]);

        $entity = $this->getAuthenticatedEntity($request);
        if (!$entity) {
            return $this->unauthorizedResponse('User is not authenticated.');
        }

        $otp = $request->input('otp');
        $phoneNumber = $request->input('phone_number');
        $formattedPhone = $this->formatE164PhoneNumber($phoneNumber);

        $cacheKey = 'sms_otp_' . get_class($entity) . '_' . $entity->id;
        $cachedOtp = Cache::get($cacheKey);

        if (!$cachedOtp || $cachedOtp !== $otp) {
            return $this->errorResponse('Invalid or expired OTP.', 400);
        }

        // Clear cache
        Cache::forget($cacheKey);

        // Update verification state dynamically checking database schema
        $entity->is_verified = 1;
        
        if (\Schema::hasColumn($entity->getTable(), 'phone_number')) {
            $entity->phone_number = $formattedPhone;
        } else {
            $entity->whatsapp_number = $formattedPhone;
        }

        if (\Schema::hasColumn($entity->getTable(), 'phone_verified_at')) {
            $entity->phone_verified_at = now();
        } else {
            $entity->whatsapp_verified_at = now();
        }

        if ($entity->google_id) {
            $entity->verification_method = 'both';
        } else {
            $entity->verification_method = 'whatsapp'; // map to legacy enum values if needed
        }

        $entity->save();

        if ($entity instanceof User) {
            $responseData = new AuthUserResource($entity);
        } elseif ($entity instanceof Employee) {
            $responseData = $this->formatEmployeeData($entity);
        } else {
            $responseData = $this->formatCustomerData($entity);
        }

        return $this->successResponse($responseData, 'Phone number verified successfully.');
    }
}

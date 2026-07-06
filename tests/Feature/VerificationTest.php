<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class VerificationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test Google verification.
     */
    public function test_google_verification(): void
    {
        $user = User::factory()->create([
            'email' => 'test_user@gmail.com',
            'is_verified' => 0,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/auth/verify/google', [
            'id_token' => 'mock_test_user@gmail.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.google_id', 'mock_google_id_' . md5('test_user@gmail.com'));
        $response->assertJsonPath('data.is_verified', 1);

        $this->assertEquals(1, $user->fresh()->is_verified);
    }

    /**
     * Test WhatsApp verification flow.
     */
    public function test_whatsapp_verification_flow(): void
    {
        $user = User::factory()->create([
            'is_verified' => 0,
        ]);

        $token = auth('api')->login($user);

        // 1. Send OTP
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/auth/verify/whatsapp/send', [
            'whatsapp_number' => '+15551234567',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $otp = $response->json('data.otp');
        $this->assertNotEmpty($otp);

        // 2. Verify OTP
        $verifyResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/auth/verify/whatsapp/verify', [
            'otp' => $otp,
            'whatsapp_number' => '+15551234567',
        ]);

        $verifyResponse->assertStatus(200);
        $verifyResponse->assertJsonPath('success', true);
        $verifyResponse->assertJsonPath('data.whatsapp_number', '+15551234567');
        $verifyResponse->assertJsonPath('data.is_verified', 1);

        $this->assertEquals(1, $user->fresh()->is_verified);
    }

    /**
     * Test Universal Login with Google.
     */
    public function test_login_with_google(): void
    {
        $user = User::factory()->create([
            'email' => 'test_login@gmail.com',
            'google_id' => 'mock_google_id_' . md5('test_login@gmail.com'),
            'is_verified' => 1,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login/google', [
            'id_token' => 'mock_test_login@gmail.com',
            'type' => 'user',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
                'user',
            ]
        ]);
    }

    /**
     * Test Universal Login with WhatsApp.
     */
    public function test_login_with_whatsapp(): void
    {
        $user = User::factory()->create([
            'whatsapp_number' => '+15559876543',
            'is_verified' => 1,
            'status' => 'active',
        ]);

        // 1. Request login OTP
        $response = $this->postJson('/api/v1/auth/login/whatsapp', [
            'whatsapp_number' => '+15559876543',
            'type' => 'user',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $otp = $response->json('data.otp');
        $this->assertNotEmpty($otp);

        // 2. Verify login OTP and get JWT
        $verifyResponse = $this->postJson('/api/v1/auth/login/whatsapp', [
            'whatsapp_number' => '+15559876543',
            'otp' => $otp,
            'type' => 'user',
        ]);

        $verifyResponse->assertStatus(200);
        $verifyResponse->assertJsonPath('success', true);
        $verifyResponse->assertJsonStructure([
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
                'user',
            ]
        ]);
    }

    /**
     * Test employee Google and WhatsApp verification.
     */
    public function test_employee_verification(): void
    {
        $vendor = \App\Models\Vendor::create([
            'business_name' => 'Test Vendor',
            'status' => 'active',
        ]);

        $employee = \App\Models\Employee::create([
            'vendor_id' => $vendor->id,
            'employee_id' => 'EMP' . time(),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'employee_verify@gmail.com',
            'mobile_number' => '1234567890',
            'designation' => 'Technician',
            'department' => 'Plumbing',
            'is_verified' => 0,
        ]);

        $token = JWTAuth::claims([
            'vendor_id' => $employee->vendor_id,
            'scope' => 'employee',
        ])->fromUser($employee);

        // 1. Test Email OTP send
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/auth/verify/email/send', [
            'email' => 'employee_verify@gmail.com',
        ]);
        $response->assertStatus(200);

        // 2. Test Google verification
        $googleResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/auth/verify/google', [
            'id_token' => 'mock_employee_verify@gmail.com',
        ]);

        $googleResponse->assertStatus(200);
        $googleResponse->assertJsonPath('success', true);
        $googleResponse->assertJsonPath('data.google_id', 'mock_google_id_' . md5('employee_verify@gmail.com'));
        $googleResponse->assertJsonPath('data.is_verified', 1);

        $this->assertEquals(1, $employee->fresh()->is_verified);
    }
}

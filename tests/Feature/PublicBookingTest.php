<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\ServiceSubCategory;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test all backend endpoints supporting the Chatbot widget.
     */
    public function test_chatbot_public_endpoints(): void
    {
        // Ensure we have at least one active category and subcategory
        $category = ServiceCategory::firstOrCreate(
            ['slug' => 'home-services'],
            [
                'name' => 'Home Services',
                'description' => 'Home maintenance and repair',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $subCategory = ServiceSubCategory::firstOrCreate(
            ['slug' => 'plumbing'],
            [
                'service_category_id' => $category->id,
                'name' => 'Plumbing',
                'description' => 'Plumbing services',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        // 1. Test Service Categories API (uses basic Controller json output)
        $response = $this->getJson('/api/v1/public/service-categories');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        // 2. Test Service Sub-Categories API (uses ApiResponse trait)
        $response = $this->getJson('/api/v1/service-sub-categories?service_category_id=' . $category->id);
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['data', 'success', 'message']);

        // 3. Test Vendors Matching API (uses ApiResponse trait)
        $response = $this->getJson('/api/v1/public/vendors?service_category=home-services&service_sub_category=plumbing');
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // 4. Test Public Booking Submission API (uses ApiResponse trait)
        $payload = [
            'name' => 'Test Bot User',
            'email' => 'botuser@example.com',
            'phone' => '1234567890',
            'location' => '123 Test Street, Bot Town',
            'service_category' => 'home-services',
            'service_sub_category' => 'plumbing',
            'date' => '2026-07-20',
            'time' => '10:00 AM',
            'notes' => 'Test instructions',
        ];

        $response = $this->postJson('/api/v1/public/bookings', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'matched_providers',
                'customer_id',
                'is_new_customer',
            ],
            'message',
        ]);
    }
}

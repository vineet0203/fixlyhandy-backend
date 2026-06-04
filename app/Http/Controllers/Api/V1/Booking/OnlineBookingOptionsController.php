<?php

namespace App\Http\Controllers\Api\V1\Booking;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Client;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnlineBookingOptionsController extends BaseController
{
    public function categories(): JsonResponse
    {
        $vendorId = $this->getVendorId();

        if (!$vendorId) {
            return $this->errorResponse('Authenticated user is not associated with a vendor.', 403);
        }

        $categories = collect($this->resolveClientTypeValues())
            ->map(fn (string $value) => [
                'id' => $value,
                'name' => ucfirst($value),
            ])
            ->values();

        return $this->successResponse($categories, 'Booking categories retrieved successfully.');
    }

    public function customers(Request $request): JsonResponse
    {
        $vendorId = $this->getVendorId();

        if (!$vendorId) {
            return $this->errorResponse('Authenticated user is not associated with a vendor.', 403);
        }

        $validated = $request->validate([
            'category' => 'required|string|in:commercial,residential',
        ]);

        $customers = Client::query()
            ->byVendor($vendorId)
            ->active()
            ->where('client_type', $validated['category'])
                ->orderBy('business_name')
                ->orderBy('first_name')
                ->orderBy('last_name')
            ->get([
                'id',
                'client_type',
                'business_name',
                'first_name',
                'last_name',
                'email',
                'address_line_1',
                'address_line_2',
                'city',
                'state',
                'country',
                'zip_code',
            ])
            ->map(function (Client $client) {
                return [
                    'id' => $client->id,
                    'name' => $client->full_name,
                    'category' => $client->client_type,
                    'address_preview' => $this->buildAddressLabel($client),
                ];
            })
            ->values();

        return $this->successResponse($customers, 'Booking customers retrieved successfully.');
    }

    public function employees(): JsonResponse
    {
        $vendorId = $this->getVendorId();

        if (!$vendorId) {
            return $this->errorResponse('Authenticated user is not associated with a vendor.', 403);
        }

        $employees = Employee::query()
            ->where('vendor_id', $vendorId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get([
                'id',
                'first_name',
                'last_name',
                'designation',
                'is_active',
            ])
            ->map(function (Employee $employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'designation' => $employee->designation,
                    'is_active' => (bool) $employee->is_active,
                ];
            })
            ->values();

        return $this->successResponse($employees, 'Booking employees retrieved successfully.');
    }

    public function locations(Request $request): JsonResponse
    {
        $vendorId = $this->getVendorId();

        if (!$vendorId) {
            return $this->errorResponse('Authenticated user is not associated with a vendor.', 403);
        }

        $validated = $request->validate([
            'customerId' => 'required|integer',
        ]);

        $client = Client::query()
            ->byVendor($vendorId)
            ->active()
            ->find($validated['customerId']);

        if (!$client) {
            return $this->notFoundResponse('Customer not found.');
        }

        $address = $this->buildAddressLabel($client);

        $locations = [];

        if ($address !== '') {
            $locations[] = [
                'id' => $client->id,
                'customer_id' => $client->id,
                'address' => $address,
                'source' => 'client_primary_address',
            ];
        }

        return $this->successResponse($locations, 'Booking locations retrieved successfully.');
    }

    private function getVendorId(): ?int
    {
        return auth()->user()?->vendor_id;
    }

    private function buildAddressLabel(Client $client): string
    {
        return collect([
            $client->address_line_1,
            $client->address_line_2,
            $client->city,
            $client->state,
            $client->zip_code,
            $client->country,
        ])
            ->filter(fn (?string $value) => filled($value))
            ->implode(', ');
    }

    private function resolveClientTypeValues(): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $column = DB::selectOne("SHOW COLUMNS FROM clients WHERE Field = 'client_type'");

            if ($column?->Type && preg_match("/^enum\((.*)\)$/", $column->Type, $matches)) {
                return collect(explode(',', $matches[1]))
                    ->map(fn (string $value) => trim($value, "'\" "))
                    ->filter()
                    ->values()
                    ->all();
            }
        }

        $values = Client::query()
            ->select('client_type')
            ->distinct()
            ->pluck('client_type')
            ->filter()
            ->values()
            ->all();

        if (!empty($values)) {
            return $values;
        }

        return ['residential', 'commercial'];
    }
}
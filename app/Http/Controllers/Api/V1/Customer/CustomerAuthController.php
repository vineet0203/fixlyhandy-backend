<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Api\V1\Customers\CustomerLoginRequest;
use App\Http\Requests\Api\V1\Customers\CustomerSetPasswordRequest;
use App\Services\Customer\CustomerAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomerAuthController extends BaseController
{
    public function __construct(private CustomerAccountService $customerAccountService) {}

    public function login(CustomerLoginRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $data = $this->customerAccountService->login($validated['email'], $validated['password']);

            return $this->successResponse($data, 'Customer login successful.');
        } catch (HttpException $exception) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        } catch (\Throwable $exception) {
            return $this->errorResponse('Customer login failed.', 500);
        }
    }

    public function setPassword(CustomerSetPasswordRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $this->customerAccountService->setPassword($validated['email'], $validated['token'], $validated['password']);

            return $this->successResponse(null, 'Password set successfully. You can now log in.');
        } catch (HttpException $exception) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        } catch (\Throwable $exception) {
            return $this->errorResponse('Failed to set password.', 500);
        }
    }

    public function me(): JsonResponse
    {
        $customer = request()->attributes->get('customer');

        $clientData = null;
        $customerEmail = is_array($customer) ? ($customer['email'] ?? null) : ($customer->email ?? null);
        if ($customerEmail) {
            $clientData = \DB::table('clients')
                ->where('email', $customerEmail)
                ->first();
        }

        $data = [
            'id'             => is_array($customer) ? ($customer['id'] ?? null) : ($customer->id ?? null),
            'name'           => is_array($customer) ? ($customer['name'] ?? null) : ($customer->name ?? null),
            'email'          => $customerEmail,
            'phone'          => is_array($customer) ? ($customer['phone'] ?? null) : ($customer->phone ?? null),
            'role'           => is_array($customer) ? ($customer['role'] ?? null) : ($customer->role ?? null),
            'status'         => is_array($customer) ? ($customer['status'] ?? null) : ($customer->status ?? null),
            'profile_photo'  => is_array($customer) ? ($customer['profile_photo'] ?? null) : ($customer->profile_photo ?? null),
            'address_line_1'               => $clientData->address_line_1 ?? null,
            'address_line_2'               => $clientData->address_line_2 ?? null,
            'city'                         => $clientData->city ?? null,
            'state'                        => $clientData->state ?? null,
            'country'                      => $clientData->country ?? null,
            'zip_code'                     => $clientData->zip_code ?? null,
            'client_type'                  => $clientData->client_type ?? null,
            'business_name'                => $clientData->business_name ?? null,
            'business_type'                => $clientData->business_type ?? null,
            'industry'                     => $clientData->industry ?? null,
            'business_registration_number' => $clientData->business_registration_number ?? null,
            'contact_person_name'          => $clientData->contact_person_name ?? null,
            'designation'                  => $clientData->designation ?? null,
            'alternate_mobile_number'      => $clientData->alternate_mobile_number ?? null,
            'billing_name'                 => $clientData->billing_name ?? null,
            'payment_term'                 => $clientData->payment_term ?? null,
            'preferred_currency'           => $clientData->preferred_currency ?? null,
            'is_tax_applicable'            => $clientData->is_tax_applicable ?? null,
            'tax_percentage'               => $clientData->tax_percentage ?? null,
            'website_url'                  => $clientData->website_url ?? null,
            'service_category'             => $clientData->service_category ?? null,
            'notes'                        => $clientData->notes ?? null,
            'first_name'                   => $clientData->first_name ?? null,
            'last_name'                    => $clientData->last_name ?? null,
        ];

        return $this->successResponse($data, 'Customer authenticated successfully.');
    }

    public function uploadProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $customerData = $request->attributes->get('customer');
        $customer = \App\Models\Customer::find($customerData['id']);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        if ($request->hasFile('photo')) {
            if ($customer->profile_photo) {
                $old = str_replace('/storage/', '', parse_url($customer->profile_photo, PHP_URL_PATH));
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('photo')->store('customer-photos', 'public');
            $customer->profile_photo = config('app.url') . Storage::url($path);
            $customer->save();
        }

        return response()->json([
            'success'       => true,
            'profile_photo' => $customer->profile_photo,
        ]);
    }

}

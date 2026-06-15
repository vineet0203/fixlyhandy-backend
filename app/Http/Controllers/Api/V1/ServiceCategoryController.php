<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Resources\Api\V1\ServiceCategory\ServiceCategoryResource;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ServiceCategoryController extends BaseController
{
    /**
     * Display a listing of service categories.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ServiceCategory::query();

            // Default to only active categories for public/general requests
            // If explicit query param 'all=true' is passed, return everything (useful for admin panel)
            if (!$request->boolean('all')) {
                $query->where('is_active', true);
            }

            $categories = $query->orderBy('sort_order', 'asc')
                ->orderBy('name', 'asc')
                ->get();

            return $this->successResponse(
                ServiceCategoryResource::collection($categories),
                'Service categories retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve service categories', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to retrieve service categories', 500);
        }
    }

    /**
     * Store a newly created service category in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:service_categories,slug',
                'description' => 'nullable|string',
                'price' => 'nullable|numeric|min:0',
                'icon' => 'nullable|string|max:255',
                'is_active' => 'boolean',
                'sort_order' => 'integer',
            ]);

            $category = ServiceCategory::create($validated);

            return $this->createdResponse(
                new ServiceCategoryResource($category),
                'Service category created successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to create service category', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create service category', 500);
        }
    }

    /**
     * Update the specified service category in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $category = ServiceCategory::find($id);

            if (!$category) {
                return $this->notFoundResponse('Service category not found');
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('service_categories', 'slug')->ignore($id),
                ],
                'description' => 'nullable|string',
                'price' => 'nullable|numeric|min:0',
                'icon' => 'nullable|string|max:255',
                'is_active' => 'boolean',
                'sort_order' => 'integer',
            ]);

            $category->update($validated);

            return $this->successResponse(
                new ServiceCategoryResource($category),
                'Service category updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to update service category', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to update service category', 500);
        }
    }

    /**
     * Remove the specified service category from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $category = ServiceCategory::find($id);

            if (!$category) {
                return $this->notFoundResponse('Service category not found');
            }

            $category->delete();

            return $this->successResponse(null, 'Service category deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete service category', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to delete service category', 500);
        }
    }

    /**
     * Toggle the status (is_active) of the service category.
     */
    public function toggle(int $id): JsonResponse
    {
        try {
            $category = ServiceCategory::find($id);

            if (!$category) {
                return $this->notFoundResponse('Service category not found');
            }

            $category->is_active = !$category->is_active;
            $category->save();

            return $this->successResponse(
                new ServiceCategoryResource($category),
                'Service category status toggled successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to toggle service category status', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to toggle service category status', 500);
        }
    }
}

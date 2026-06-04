<?php
// app/Http/Controllers/Api/V1/OptionsController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptionsController extends Controller
{
    /**
     * Get manager options with search
     */
    public function managers(Request $request)
    {
        try {
            Log::info('Fetching managers with params:', $request->all());
            
            $request->validate([
                'search' => 'nullable|string|max:50',
                'exclude_id' => 'nullable|integer|exists:employees,id',
                'include_inactive' => 'nullable|boolean'
            ]);

            // Start query - DON'T filter by designation first
            $query = Employee::query()
                ->select(
                    'id',
                    'first_name',
                    'last_name',
                    'employee_id',
                    'designation',
                    'email'
                )
                ->where('is_active', !$request->boolean('include_inactive', false));

            // Log the SQL before adding designation filter
            Log::info('Base query SQL:', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            // Exclude specific employee
            if ($request->exclude_id) {
                $query->where('id', '!=', $request->exclude_id);
            }

            // Search functionality
            if ($request->search && $request->search !== '') {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('first_name', 'like', $searchTerm)
                      ->orWhere('last_name', 'like', $searchTerm)
                      ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', $searchTerm)
                      ->orWhere('employee_id', 'like', $searchTerm)
                      ->orWhere('email', 'like', $searchTerm);
                });
            }

            // Get count first
            $count = $query->count();
            Log::info('Total employees found:', ['count' => $count]);

            // Get results
            $managers = $query->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(100)
                ->get();

            Log::info('Employees retrieved:', ['count' => $managers->count()]);

            // Format options
            $formattedOptions = $managers->map(function($manager) {
                $fullName = trim($manager->first_name . ' ' . $manager->last_name);
                return [
                    'value' => $manager->id,
                    'label' => $fullName . ' (' . $manager->employee_id . ')',
                    'designation' => $manager->designation,
                    'email' => $manager->email
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedOptions,
                'count' => $formattedOptions->count(),
                'message' => 'Managers retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching managers:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch managers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
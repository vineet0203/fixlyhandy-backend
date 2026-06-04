<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class EmployeeJwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();

            if (($payload->get('scope') ?? null) !== 'employee') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid employee token scope.',
                    'code' => 401,
                ], 401);
            }

            $employeeId = (int) $payload->get('sub');
            $vendorId = (int) $payload->get('vendor_id');

            $employee = Employee::where('id', $employeeId)
                ->where('vendor_id', $vendorId)
                ->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found.',
                    'code' => 401,
                ], 401);
            }

            if (!$employee->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee account is inactive.',
                    'code' => 403,
                ], 403);
            }

            $request->attributes->set('employee', [
                'id' => $employee->id,
                'vendor_id' => $employee->vendor_id,
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employee token expired.',
                'code' => 401,
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employee token invalid.',
                'code' => 401,
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employee token missing.',
                'code' => 401,
            ], 401);
        }

        return $next($request);
    }
}

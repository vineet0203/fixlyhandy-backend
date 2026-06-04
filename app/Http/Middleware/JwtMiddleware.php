<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. The requested user account could not be found.',
                    'timestamp' => now()->toISOString(),
                    'code' => 401,
                    'error_code' => 'USER_NOT_FOUND'
                ], 401);
            }

            // Check if user is active
            if (!$user->is_active || $user->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. This account has been deactivated. Please contact support for assistance.',
                    'timestamp' => now()->toISOString(),
                    'code' => 403,
                    'error_code' => 'ACCOUNT_DEACTIVATED'
                ], 403);
            }

            // Check if vendor is active (if user belongs to a vendor)
            if ($user->vendor_id && optional($user->vendor)->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. The associated vendor account is inactive. Please contact your administrator.',
                    'timestamp' => now()->toISOString(),
                    'code' => 403,
                    'error_code' => 'VENDOR_INACTIVE'
                ], 403);
            }
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication session expired. Please re-authenticate to continue.',
                'timestamp' => now()->toISOString(),
                'code' => 401,
                'error_code' => 'AUTH_TOKEN_EXPIRED'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed. The provided access token is invalid or could not be verified.',
                'timestamp' => now()->toISOString(),
                'code' => 401,
                'error_code' => 'AUTH_TOKEN_INVALID'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication token missing. Please include a valid access token in the request header.',
                'timestamp' => now()->toISOString(),
                'code' => 401,
                'error_code' => 'AUTH_TOKEN_MISSING'
            ], 401);
        }


        return $next($request);
    }
}

<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException; // ADD THIS

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        // Handle API requests
        if ($request->expectsJson() || $request->is('api/*') || strpos($request->path(), 'api/') === 0) {
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    protected function handleApiException($request, Throwable $exception)
    {
        // Authentication Exceptions
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please provide valid credentials to access this resource.',
                'timestamp' => now()->toISOString(),
                'code' => 401,
                'error_code' => 'AUTH_REQUIRED'
            ], 401);
        }

        // Authorization Exceptions
        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You do not have permission to perform this action.',
                'timestamp' => now()->toISOString(),
                'code' => 403,
                'error_code' => 'FORBIDDEN',
                'error_details' => config('app.debug') ? $exception->getMessage() : null,
            ], 403);
        }

        // Unauthorized HTTP Exceptions
        if ($exception instanceof UnauthorizedHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Authentication credentials were missing or invalid.',
                'timestamp' => now()->toISOString(),
                'code' => 401,
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        // Validation Exceptions
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed. Please review the submitted data.',
                'errors' => $exception->errors(),
                'timestamp' => now()->toISOString(),
                'code' => 422,
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        }

        // Not Found Exceptions
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'The requested API endpoint could not be found.',
                'timestamp' => now()->toISOString(),
                'code' => 404,
                'error_code' => 'ENDPOINT_NOT_FOUND'
            ], 404);
        }

        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'The requested resource was not found.',
                'timestamp' => now()->toISOString(),
                'code' => 404,
                'error_code' => 'RESOURCE_NOT_FOUND'
            ], 404);
        }

        // Method Not Allowed
        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'The HTTP method used is not supported for this endpoint.',
                'timestamp' => now()->toISOString(),
                'code' => 405,
                'error_code' => 'METHOD_NOT_ALLOWED'
            ], 405);
        }

        // Generic Server Error
        $response = [
            'success' => false,
            'message' => 'An unexpected error occurred. Please try again later.',
            'timestamp' => now()->toISOString(),
            'code' => 500,
            'error_code' => 'INTERNAL_SERVER_ERROR'
        ];

        if (config('app.debug')) {
            $response['debug'] = [
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        return response()->json($response, 500);
    }
}

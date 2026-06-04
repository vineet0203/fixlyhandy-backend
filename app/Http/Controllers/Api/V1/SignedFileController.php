<?php
// app/Http\Controllers\Api\V1\SignedFileController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\File\SignedUrlService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SignedFileController extends Controller
{
    use ApiResponse;

    public function __construct(
        private SignedUrlService $signedUrlService
    ) {}

    /**
     * Generate signed URL for temporary file
     */
    public function generateTemporarySignedUrl(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'expires_in' => 'sometimes|integer|min:1|max:1440', // Max 24 hours
        ]);

        try {
            $path = $request->input('path');
            $expiresIn = $request->input('expires_in', 60);

            $signedUrl = $this->signedUrlService->generateTemporarySignedUrl($path, $expiresIn);

            if (!$signedUrl) {
                return $this->errorResponse('File not found or expired', 404);
            }

            return $this->successResponse($signedUrl, 'Signed URL generated');
        } catch (\Exception $e) {
            Log::error('Error generating temporary signed URL', [
                'path' => $request->input('path'),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to generate signed URL', 500);
        }
    }

    /**
     * Generate signed URL for public file
     */
    public function generatePublicSignedUrl(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'expires_in' => 'sometimes|integer|min:1|max:1440',
        ]);

        try {
            $path = $request->input('path');
            $expiresIn = $request->input('expires_in', 60);

            $signedUrl = $this->signedUrlService->generatePublicSignedUrl($path, $expiresIn);

            if (!$signedUrl) {
                return $this->errorResponse('File not found', 404);
            }

            return $this->successResponse($signedUrl, 'Signed URL generated');
        } catch (\Exception $e) {
            Log::error('Error generating public signed URL', [
                'path' => $request->input('path'),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to generate signed URL', 500);
        }
    }

    /**
     * Generate signed URL for private file
     */
    public function generatePrivateSignedUrl(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'expires_in' => 'sometimes|integer|min:1|max:1440',
        ]);

        try {
            $path = $request->input('path');
            $expiresIn = $request->input('expires_in', 60);

            $signedUrl = $this->signedUrlService->generatePrivateSignedUrl($path, $expiresIn);

            if (!$signedUrl) {
                return $this->errorResponse('File not found', 404);
            }

            return $this->successResponse($signedUrl, 'Signed URL generated');
        } catch (\Exception $e) {
            Log::error('Error generating private signed URL', [
                'path' => $request->input('path'),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to generate signed URL', 500);
        }
    }

    /**
     * Serve file from signed URL
     * This route doesn't need authentication - the URL signature is the authentication
     */
    public function serveSigned(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $response = $this->signedUrlService->serveSignedFile($request);

            if (!$response) {
                return $this->errorResponse('File not found or URL expired', 404);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('Error serving signed file', [
                'request' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to serve file', 500);
        }
    }
}

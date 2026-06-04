<?php
// app/Http/Controllers/Api/V1/UploadController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Upload\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;

class UploadController extends Controller
{
    use ApiResponse;
    public function __construct(
        private FileUploadService $uploadService
    ) {}

    public function uploadTemporary(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        try {
            $result = $this->uploadService->uploadTemporaryFile(
                $request->file('file'),
                Auth::id()
            );

            return $this->successResponse($result, 'File uploaded successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function getUploadLimits(): JsonResponse
    {
        $limits = $this->uploadService->getUploadLimits();

        return $this->successResponse($limits, 'Upload limits retrieved');
    }

}

<?php

namespace App\Http\Controllers\Api\V1\Onboarding;

use App\Http\Controllers\Api\V1\BaseController;
use App\Mail\OnboardingFormMail;
use App\Models\AssignedDocument;
use App\Models\DocumentTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OnboardingController extends BaseController
{
    /**
     * Get all active document templates.
     */
    public function templates(): JsonResponse
    {
        try {
            $templates = DocumentTemplate::where('is_active', true)->get();

            return $this->successResponse($templates, 'Templates retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to fetch document templates', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch templates.', 500);
        }
    }

    /**
     * Assign a document to an employee and send email.
     */
    public function assign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_name' => 'required|string|max:255',
            'employee_email' => 'required|email|max:255',
            'template_id' => 'required|exists:document_templates,id',
            'customer_id' => 'nullable|exists:clients,id',
        ]);

        try {
            $token = Str::uuid()->toString();

            $assignment = AssignedDocument::create([
                'vendor_id' => auth()->id(),
                'customer_id' => $validated['customer_id'] ?? null,
                'employee_name' => $validated['employee_name'],
                'employee_email' => $validated['employee_email'],
                'template_id' => $validated['template_id'],
                'token' => $token,
                'status' => 'pending',
                'expires_at' => now()->addHours(48),
            ]);

            // Load the template relationship for the email
            $assignment->load('template');

            // Send email
            Mail::to($validated['employee_email'])->send(new OnboardingFormMail($assignment));

            Log::info('Onboarding document assigned', [
                'assignment_id' => $assignment->id,
                'employee_email' => $validated['employee_email'],
                'template_id' => $validated['template_id'],
                'token' => $token,
            ]);

            return $this->successResponse([
                'id' => $assignment->id,
                'token' => $token,
                'expires_at' => $assignment->expires_at->toISOString(),
            ], 'Document assigned and email sent successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to assign onboarding document', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to assign document. Please try again.', 500);
        }
    }

    /**
     * Validate token and return assignment details (PUBLIC - no auth).
     */
    public function getByToken(string $token): JsonResponse
    {
        try {
            $assignment = AssignedDocument::with('template')
                ->where('token', $token)
                ->first();

            if (!$assignment) {
                return $this->errorResponse('Invalid or expired link.', 404);
            }

            if ($assignment->status === 'completed') {
                return $this->errorResponse('This form has already been completed.', 410);
            }

            if ($assignment->isExpired()) {
                return $this->errorResponse('This link has expired. Please contact your employer.', 410);
            }

            return $this->successResponse([
                'id' => $assignment->id,
                'employee_name' => $assignment->employee_name,
                'employee_email' => $assignment->employee_email,
                'template' => [
                    'id' => $assignment->template->id,
                    'name' => $assignment->template->name,
                    'file_name' => $assignment->template->file_name,
                ],
                'expires_at' => $assignment->expires_at->toISOString(),
            ], 'Assignment details retrieved.');
        } catch (\Exception $e) {
            Log::error('Failed to validate onboarding token', ['token' => $token, 'error' => $e->getMessage()]);
            return $this->errorResponse('Something went wrong.', 500);
        }
    }

    /**
     * Submit completed PDF (PUBLIC - no auth, token-based).
     */
    public function submit(Request $request, string $token): JsonResponse
    {
        $request->validate([
            'completed_pdf' => 'required|file|mimes:pdf|max:10240',
        ]);

        try {
            $assignment = AssignedDocument::with('template')
                ->where('token', $token)
                ->where('status', 'pending')
                ->first();

            if (!$assignment) {
                return $this->errorResponse('Invalid or already completed assignment.', 404);
            }

            if ($assignment->isExpired()) {
                return $this->errorResponse('This link has expired.', 410);
            }

            // Ensure the storage directory exists
            $storagePath = storage_path('app/completed-documents');
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0775, true);
            }

            // Store the completed PDF
            $fileName = 'completed_' . $assignment->id . '_' . time() . '.pdf';
            $uploadedFile = $request->file('completed_pdf');

            Log::info('Upload file details', [
                'original_name' => $uploadedFile->getClientOriginalName(),
                'size' => $uploadedFile->getSize(),
                'mime' => $uploadedFile->getMimeType(),
                'valid' => $uploadedFile->isValid(),
                'error' => $uploadedFile->getError(),
            ]);

            $path = $uploadedFile->storeAs('completed-documents', $fileName);

            if ($path === false) {
                Log::error('storeAs() returned false — PDF file could not be saved', [
                    'assignment_id' => $assignment->id,
                    'target_dir' => $storagePath,
                    'file_name' => $fileName,
                    'dir_exists' => is_dir($storagePath),
                    'dir_writable' => is_writable($storagePath),
                    'disk_free' => disk_free_space(storage_path('app')),
                ]);
                return $this->errorResponse('Failed to save the PDF file. Please try again.', 500);
            }

            // Update assignment
            $assignment->update([
                'status' => 'completed',
                'completed_pdf_path' => $path,
                'completed_at' => now(),
            ]);

            Log::info('Onboarding document completed', [
                'assignment_id' => $assignment->id,
                'employee_name' => $assignment->employee_name,
                'file_path' => $path,
            ]);

            return $this->successResponse([
                'id' => $assignment->id,
                'status' => 'completed',
                'completed_at' => $assignment->completed_at->toISOString(),
            ], 'Form submitted successfully. Thank you!');
        } catch (\Exception $e) {
            Log::error('Failed to submit onboarding document', ['token' => $token, 'error' => $e->getMessage()]);
            return $this->errorResponse('Failed to submit form. Please try again.', 500);
        }
    }

    /**
     * Get assigned documents for the authenticated vendor.
     */
    public function listAssigned(Request $request): JsonResponse
    {
        try {
            $assignments = AssignedDocument::with('template')
                ->where('vendor_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($a) {
                    return [
                        'id' => $a->id,
                        'employee_name' => $a->employee_name,
                        'employee_email' => $a->employee_email,
                        'template_name' => $a->template->name,
                        'status' => $a->status,
                        'expires_at' => $a->expires_at->toISOString(),
                        'completed_at' => $a->completed_at?->toISOString(),
                        'created_at' => $a->created_at->toISOString(),
                        'has_pdf' => !empty($a->completed_pdf_path),
                    ];
                });

            return $this->successResponse($assignments, 'Assigned documents retrieved.');
        } catch (\Exception $e) {
            Log::error('Failed to list assigned documents', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to retrieve documents.', 500);
        }
    }

    /**
     * Download completed PDF.
     */
    public function download(int $id)
    {
        try {
            // Step 1: Find the assignment (without firstOrFail to give better errors)
            $assignment = AssignedDocument::where('id', $id)
                ->where('vendor_id', auth()->id())
                ->first();

            if (!$assignment) {
                Log::warning('Download failed: assignment not found or not owned by vendor', [
                    'id' => $id,
                    'vendor_id' => auth()->id(),
                ]);
                return $this->errorResponse('Document not found or access denied.', 404);
            }

            if ($assignment->status !== 'completed') {
                Log::warning('Download failed: assignment not completed', [
                    'id' => $id,
                    'status' => $assignment->status,
                ]);
                return $this->errorResponse('Document has not been completed yet.', 400);
            }

            if (!$assignment->completed_pdf_path) {
                Log::error('Download failed: completed_pdf_path is null', [
                    'id' => $id,
                    'status' => $assignment->status,
                ]);
                return $this->errorResponse('Completed PDF path not recorded.', 404);
            }

            if (!Storage::exists($assignment->completed_pdf_path)) {
                Log::error('Download failed: file does not exist on disk', [
                    'id' => $id,
                    'path' => $assignment->completed_pdf_path,
                    'full_path' => storage_path('app/' . $assignment->completed_pdf_path),
                ]);
                return $this->errorResponse('Completed PDF file not found on server. Path: ' . $assignment->completed_pdf_path, 404);
            }

            $downloadName = Str::slug($assignment->employee_name) . '_' . Str::slug($assignment->template->name ?? 'document') . '.pdf';

            return Storage::download($assignment->completed_pdf_path, $downloadName);
        } catch (\Exception $e) {
            Log::error('Failed to download completed PDF', ['id' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Failed to download document.', 500);
        }
    }

    /**
     * Serve the blank template PDF for a given assignment token (PUBLIC).
     * The employee needs this to fill the form in the browser.
     */
    public function templatePdf(string $token)
    {
        try {
            $assignment = AssignedDocument::with('template')
                ->where('token', $token)
                ->first();

            if (!$assignment) {
                return $this->errorResponse('Invalid link.', 404);
            }

            if ($assignment->status === 'completed') {
                return $this->errorResponse('This form has already been completed.', 410);
            }

            if ($assignment->isExpired()) {
                return $this->errorResponse('This link has expired.', 410);
            }

            $fileName = $assignment->template->file_name;
            $path = 'document-templates/' . $fileName;

            if (!Storage::exists($path)) {
                Log::error('Template PDF not found on disk', ['path' => $path]);
                return $this->errorResponse('Template PDF not found.', 404);
            }

            return response(Storage::get($path), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                // Prevent stale template PDFs from browser/proxy cache.
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to serve template PDF', ['token' => $token, 'error' => $e->getMessage()]);
            return $this->errorResponse('Failed to load template.', 500);
        }
    }
}

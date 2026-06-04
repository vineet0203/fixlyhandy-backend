<?php
// app/Http/Requests/Api/V1/File/FileRequest.php

namespace App\Http\Requests\Api\V1\File;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For public files, allow everyone
        if ($this->routeIs('files.public')) {
            return true;
        }

        // For temporary files, require authentication
        if ($this->routeIs('files.temporary')) {
            return auth()->check();
        }

        // For private files, require authentication
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'path' => [
                'required',
                'string',
                'max:1000',
                function ($attribute, $value, $fail) {
                    // Prevent path traversal
                    if (
                        str_contains($value, '..') ||
                        str_contains($value, '//') ||
                        str_contains($value, '\\')
                    ) {
                        $fail('Invalid file path.');
                    }

                    // Validate file extension for certain routes
                    if ($this->routeIs('files.temporary') || $this->routeIs('files.public')) {
                        $extension = pathinfo($value, PATHINFO_EXTENSION);
                        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx'];

                        if (!in_array(strtolower($extension), $allowedExtensions)) {
                            $fail('File type not allowed.');
                        }
                    }
                }
            ],
            'download' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'path.required' => 'File path is required.',
            'path.string' => 'File path must be a string.',
            'path.max' => 'File path is too long.',
            'download.boolean' => 'Download parameter must be true or false.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Decode URL-encoded paths
        if ($this->path) {
            $this->merge([
                'path' => urldecode($this->path),
            ]);
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ], 422));
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Unauthorized access.',
        ], 403));
    }
}

<?php
// app/Http/Requests/Api/V1/Jobs/AddAttachmentRequest.php

namespace App\Http\Requests\Api\V1\Jobs;

use Illuminate\Foundation\Http\FormRequest;

class AddAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|max:10240', // 10MB max
            'file_name' => 'nullable|string|max:255',
        ];
    }
}
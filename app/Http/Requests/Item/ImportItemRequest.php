<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;

class ImportItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canWrite() ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File Excel wajib diupload.',
            'file.file' => 'File upload harus berupa file yang valid.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ];
    }
}

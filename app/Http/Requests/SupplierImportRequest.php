<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SupplierImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->hasFile('import_file')) {
            $this->merge([
                'payload' => $this->file('import_file')?->get(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'strategy' => ['nullable', 'in:skip,overwrite,duplicate,reject'],
            'payload' => ['required', 'string'],
            'import_file' => ['nullable', 'file', 'mimes:json,txt'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedPayload(): array
    {
        $rawPayload = trim((string) $this->input('payload'));
        $rawPayload = preg_replace('/^\xEF\xBB\xBF/', '', $rawPayload) ?? $rawPayload;

        $decoded = json_decode($rawPayload, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'payload' => 'Payload must be valid JSON.',
            ]);
        }

        return $decoded;
    }
}

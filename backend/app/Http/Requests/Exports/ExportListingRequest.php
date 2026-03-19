<?php

namespace App\Http\Requests\Exports;

use App\Enums\ExportScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ExportListingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'scope' => (string) $this->input('scope', ExportScope::RESULTS->value),
        ]);
    }

    public function rules(): array
    {
        return [
            'scope' => ['required', 'string', Rule::in(ExportScope::values())],
        ];
    }

    public function scope(): ExportScope
    {
        return ExportScope::from((string) $this->validated('scope'));
    }
}

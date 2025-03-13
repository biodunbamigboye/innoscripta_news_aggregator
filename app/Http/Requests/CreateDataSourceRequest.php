<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDataSourceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('data_sources', 'name')],
            'identifier' => ['required', 'string', 'max:50', Rule::unique('data_sources', 'identifier')],
            'uri' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sync_interval' => ['sometimes', 'integer', 'min:1'],
            'filters' => ['sometimes', 'array'],
        ];
    }
}

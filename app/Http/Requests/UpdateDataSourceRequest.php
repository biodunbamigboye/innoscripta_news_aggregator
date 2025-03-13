<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDataSourceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:50', Rule::unique('data_sources', 'name')->ignore($this->route('dataSource'))],
            'uri' => ['sometimes', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sync_interval' => ['sometimes', 'integer', 'min:1'],
            'filters' => ['sometimes', 'array'],

        ];
    }
}

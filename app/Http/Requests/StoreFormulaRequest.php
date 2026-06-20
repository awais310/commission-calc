<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                     => ['required', 'string', 'max:255'],
            'description'              => ['nullable', 'string', 'max:1000'],
            'expression'               => ['required', 'string', 'max:2000'],
            'variables'                => ['nullable', 'array'],
            'variables.*.variable_name' => ['required_with:variables', 'string', 'regex:/^[A-Z][a-zA-Z0-9]+$/'],
            'variables.*.expression'   => ['required_with:variables', 'string', 'max:1000'],
        ];
    }
}

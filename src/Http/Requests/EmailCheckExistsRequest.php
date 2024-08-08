<?php

namespace Wame\NovaEmailAutocompleteField\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailCheckExistsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'column' => 'required|string',
            'email' => 'required|email',
            'table' => 'required|string',
        ];
    }
}
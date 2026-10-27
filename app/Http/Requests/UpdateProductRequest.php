<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['bail', 'sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['bail', 'required', 'numeric', 'min:0', 'max:99999999.99', 'regex:/^\d+(\.\d{1,2})?$/D'],
        ];
    }
}

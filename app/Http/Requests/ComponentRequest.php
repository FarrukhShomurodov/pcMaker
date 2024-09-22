<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ComponentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:500',
            'component_category_id' => 'required|integer|exists:component_categories,id',
            'component_type_id' => 'required|integer|exists:component_types,id',
            'brand' => 'required|string|max:500',
            'quantity' => 'required|int',
            'price' => 'required|int',
            'photos' => 'sometimes|array',
            'description' => 'nullable|string',
            'photos.*' => 'image',
        ];
    }
}

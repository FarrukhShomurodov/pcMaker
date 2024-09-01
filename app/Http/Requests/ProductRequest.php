<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
            'product_category_id' => 'required|integer|exists:product_categories,id',
            'product_sub_category_id' => 'nullable|integer|exists:product_sub_categories,id',
            'brand' => 'required|string|max:500',
            'quantity' => 'required|int',
            'price' => 'required|decimal:2',
            'description' => 'required|string',
            'photos' => 'sometimes|array',
            'photos.*' => 'image',
        ];
    }
}

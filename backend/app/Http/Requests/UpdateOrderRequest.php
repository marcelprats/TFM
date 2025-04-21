<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:pending,reserved,completed,cancelled',
            'cancellation_reason' => 'nullable|string|max:255',
            'confirmed_product_ids' => 'nullable|array',
        ];
    }
}

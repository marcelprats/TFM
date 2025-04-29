<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Eloquent\Relations\Relation;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('cartItem');
        return $this->user()?->can('update', $item);
    }

    public function rules(): array
    {
        $item     = $this->route('cartItem');
        $stock    = optional($item->product)->stock ?: 0;
        $morphMap = Relation::morphMap();
        $alias    = array_search(get_class($this->user()), $morphMap) ?: get_class($this->user());

        return [
            'quantity' => [
                'required','integer','min:1',
                "max:{$stock}"
            ],
            'selected' => ['sometimes','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.max' => 'Només queden :max unitats disponibles.',
        ];
    }
}

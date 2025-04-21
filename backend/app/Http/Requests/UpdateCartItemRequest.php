<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize()
    {
        $item = $this->route('item'); // via Route Model Binding
        return $this->user() !== null
            && $item
            && $item->cart->owner_id === $this->user()->id
            && $item->cart->owner_type === \get_class($this->user());
    }

    public function rules()
    {
        return [
            'quantity' => 'required|integer|min:1',
            'selected' => 'sometimes|boolean',
        ];
    }
}

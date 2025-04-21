<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Producte;

class AddCartItemRequest extends FormRequest
{
    /**
     * Only authenticated users (clients o vendors) can add items.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare default values before validation.
     * Aquí establim 'selected' a true si no s'envia.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('selected')) {
            $this->merge(['selected' => true]);
        }
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:productes,id'],
            'quantity'   => [
                'required',
                'integer',
                'min:1',
                // Comprobació d'estoc disponible
                function ($attribute, $value, $fail) {
                    $stock = Producte::find($this->input('product_id'))?->stock ?? 0;
                    if ($value > $stock) {
                        $fail("Només hi ha {$stock} unitats disponibles.");
                    }
                },
            ],
            'selected'   => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Cal indicar quin producte vols afegir.',
            'product_id.exists'   => 'El producte seleccionat no existeix.',
            'quantity.required'   => 'Cal indicar la quantitat.',
            'quantity.integer'    => 'La quantitat ha de ser un valor enter.',
            'quantity.min'        => 'La quantitat mínima és 1.',
            'selected.boolean'    => 'El valor de seleccionat ha de ser cert o fals.',
        ];
    }
}

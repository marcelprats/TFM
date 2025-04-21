<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBotigaRequest extends FormRequest
{
    public function authorize()
    {
        // Només venedors autenticats poden crear botigues
        return $this->user() && $this->user() instanceof \App\Models\Vendor;
    }

    public function rules()
    {
        return [
            'nom'         => 'required|string|max:255',
            'descripcio'  => 'nullable|string',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
            'horaris'     => 'nullable|array',
            'horaris.*.dia'       => 'required_with:horaris|string',
            'horaris.*.franjes'   => 'required_with:horaris|array',
            'horaris.*.franjes.*.obertura'  => 'required|string',
            'horaris.*.franjes.*.tancament' => 'required|string',
        ];
    }
}

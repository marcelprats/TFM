<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBotigaRequest extends StoreBotigaRequest
{
    public function authorize()
    {
        $botiga = $this->route('botiga');
        // Només el venedor propietari pot actualitzar
        return parent::authorize() && $botiga && $this->user()->id === $botiga->vendor_id;
    }
}

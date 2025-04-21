<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutCartRequest extends FormRequest
{
    public function authorize()
    {
        // Hem de comprovar en la policy
        return $this->user() !== null;
    }

    public function rules()
    {
        return []; // cap camp extra
    }
}

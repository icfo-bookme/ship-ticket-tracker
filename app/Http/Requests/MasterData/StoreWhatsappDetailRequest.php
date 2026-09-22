<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class StoreWhatsappDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'tag' => 'required|string|max:255',
            'whatsapp_number' => 'required|digits_between:10,15',
            'form_no' => 'required|string|max:100',
            'url' => 'required|url|max:255',
        ];
    }
}

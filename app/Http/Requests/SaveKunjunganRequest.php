<?php

namespace App\Http\Requests;

use App\Support\LansiaFields;
use Illuminate\Foundation\Http\FormRequest;

class SaveKunjunganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active ?? false;
    }

    public function rules(): array
    {
        $rules = [];
        foreach (LansiaFields::allKunjunganRowColumns() as $col) {
            $rules[$col] = ['nullable', 'integer', 'min:0'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            '*.integer' => 'Nilai harus berupa bilangan bulat.',
            '*.min'     => 'Nilai tidak boleh bernilai negatif (minimal 0).',
        ];
    }
}

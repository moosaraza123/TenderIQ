<?php

namespace App\Modules\Tender\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenderFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword'      => ['nullable', 'string', 'max:200'],
            'country'      => ['nullable', 'string', 'max:5'],
            'category'     => ['nullable', 'string'],
            'sector'       => ['nullable', 'string'],
            'city'         => ['nullable', 'string'],
            'status'       => ['nullable', 'in:Published,Corrigendum,Cancelled'],
            'tender_type'  => ['nullable', 'string'],
            'closing_from' => ['nullable', 'date'],
            'closing_to'   => ['nullable', 'date', 'after_or_equal:closing_from'],
            'sort'         => ['nullable', 'in:closing_soon,newest,relevance'],
        ];
    }
}

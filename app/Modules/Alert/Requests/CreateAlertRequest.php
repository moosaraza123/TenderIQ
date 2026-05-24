<?php

namespace App\Modules\Alert\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keywords'    => ['required', 'array', 'min:1'],
            'keywords.*'  => ['string', 'max:100'],
            'categories'  => ['nullable', 'array'],
            'cities'      => ['nullable', 'array'],
            'min_budget'  => ['nullable', 'numeric', 'min:0'],
            'max_budget'  => ['nullable', 'numeric', 'gt:min_budget'],
            'frequency'   => ['nullable', 'in:instant,daily,weekly'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
        ];
    }
}

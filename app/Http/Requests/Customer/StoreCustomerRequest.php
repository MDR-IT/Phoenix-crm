<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customers.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'phone_alt' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', Rule::in(array_keys(config('phoenix.genders')))],
            'county_id' => ['nullable', 'exists:counties,id'],
            'town' => ['nullable', 'string', 'max:120'],
            'consent_marketing' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

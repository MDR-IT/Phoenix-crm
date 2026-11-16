<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $this->user()?->can('update', $lead) ?? false;
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
            'product_id' => ['nullable', 'exists:products,id'],
            'lead_source_id' => ['nullable', 'exists:lead_sources,id'],
            'campaign' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['required', Rule::in(array_keys(config('phoenix.lead_priorities')))],
            'status' => ['required', Rule::in(array_keys(config('phoenix.lead_statuses')))],
            'follow_up_at' => ['nullable', 'date'],
            'consent_contact' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
            'status_notes' => ['nullable', 'string'],
        ];
    }
}

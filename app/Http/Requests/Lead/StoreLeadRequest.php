<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('leads.create') || $this->user()?->can('leads.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'first_name' => ['required_without:customer_id', 'nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['required_without:customer_id', 'nullable', 'string', 'max:32'],
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
            'assignment_strategy' => ['nullable', Rule::in(['round_robin', 'product', 'region', 'team'])],
            'team_id' => ['nullable', 'required_if:assignment_strategy,team', 'exists:teams,id'],
            'priority' => ['required', Rule::in(array_keys(config('phoenix.lead_priorities')))],
            'status' => ['required', Rule::in(array_keys(config('phoenix.lead_statuses')))],
            'follow_up_at' => ['nullable', 'date'],
            'consent_contact' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
            'acknowledge_duplicates' => ['sometimes', 'boolean'],
            'save_and_another' => ['sometimes', 'boolean'],
        ];
    }
}

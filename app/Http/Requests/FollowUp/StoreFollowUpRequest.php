<?php

namespace App\Http\Requests\FollowUp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('follow_ups.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lead_id' => ['required', 'exists:leads,id'],
            'type' => ['required', Rule::in(array_keys(config('phoenix.follow_up_types')))],
            'scheduled_at' => ['required', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

<?php

namespace App\Http\Requests\FollowUp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $followUp = $this->route('follow_up');

        return $this->user()?->can('update', $followUp) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(config('phoenix.follow_up_types')))],
            'scheduled_at' => ['required', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'outcome' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

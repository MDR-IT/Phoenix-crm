<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tasks.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['required', Rule::in(array_keys(config('phoenix.lead_priorities')))],
            'due_at' => ['nullable', 'date'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
        ];
    }
}

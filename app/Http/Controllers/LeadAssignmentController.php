<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadAssignmentController extends Controller
{
    public function __construct(
        private readonly LeadAssignmentService $assignmentService,
    ) {}

    public function store(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('assign', $lead);

        $validated = $request->validate([
            'mode' => ['required', 'in:manual,round_robin,product,region,team'],
            'assigned_to' => ['nullable', 'required_if:mode,manual', 'exists:users,id'],
            'team_id' => ['nullable', 'required_if:mode,team', 'exists:teams,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['mode'] === 'manual') {
            $assignee = ! empty($validated['assigned_to'])
                ? User::findOrFail($validated['assigned_to'])
                : null;
            $this->assignmentService->assign(
                $lead,
                $assignee,
                $request->user(),
                'manual',
                $validated['notes'] ?? null
            );
        } else {
            $this->assignmentService->assignByStrategy(
                $lead,
                $request->user(),
                $validated['mode'],
                isset($validated['team_id']) ? (int) $validated['team_id'] : null
            );
        }

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', 'Lead assignment updated.');
    }
}

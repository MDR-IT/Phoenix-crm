<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadStatusController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function update(Request $request, Lead $lead): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(config('phoenix.lead_statuses')))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->leadService->changeStatus(
            $lead,
            $validated['status'],
            $request->user(),
            $validated['notes'] ?? null
        );

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'status' => $validated['status'],
                'label' => config("phoenix.lead_statuses.{$validated['status']}"),
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Lead status updated.');
    }
}

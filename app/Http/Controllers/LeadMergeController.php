<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadMergeController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function create(Request $request, Lead $lead): View
    {
        $this->authorize('merge', Lead::class);
        $this->authorize('view', $lead);

        $candidates = Lead::query()
            ->with('customer')
            ->where('id', '!=', $lead->id)
            ->when($request->string('q')->toString(), function ($query, string $search) {
                $query->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone_normalized', 'like', "%{$search}%"));
            })
            ->latest()
            ->limit(30)
            ->get();

        return view('leads.merge', [
            'primary' => $lead->load('customer'),
            'candidates' => $candidates,
        ]);
    }

    public function store(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('merge', Lead::class);
        $this->authorize('view', $lead);

        $validated = $request->validate([
            'secondary_lead_id' => ['required', 'exists:leads,id', Rule::notIn([(string) $lead->id])],
        ]);

        $secondary = Lead::findOrFail($validated['secondary_lead_id']);
        $this->authorize('view', $secondary);

        $merged = $this->leadService->merge($lead, $secondary, $request->user());

        return redirect()
            ->route('leads.show', $merged)
            ->with('success', 'Leads merged. Secondary lead '.$secondary->reference.' was archived.');
    }
}

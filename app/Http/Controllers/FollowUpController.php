<?php

namespace App\Http\Controllers;

use App\Http\Requests\FollowUp\StoreFollowUpRequest;
use App\Http\Requests\FollowUp\UpdateFollowUpRequest;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use App\Services\FollowUpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function __construct(
        private readonly FollowUpService $followUpService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', FollowUp::class);

        $filters = $request->only(['q', 'status', 'type', 'assigned_to', 'bucket']);
        if (empty($filters['bucket']) && empty($filters['status']) && empty($filters['q'])) {
            $filters['bucket'] = 'today';
        }

        $base = FollowUp::query()->visibleTo($request->user());

        $counts = [
            'today' => (clone $base)->dueToday()->count(),
            'overdue' => (clone $base)->overdue()->count(),
            'upcoming' => (clone $base)->upcoming()->count(),
        ];

        $followUps = FollowUp::query()
            ->with(['lead.customer', 'assignee', 'creator'])
            ->visibleTo($request->user())
            ->filter($filters)
            ->orderBy('scheduled_at')
            ->paginate(20)
            ->withQueryString();

        return view('follow-ups.index', [
            'followUps' => $followUps,
            'filters' => $filters,
            'counts' => $counts,
            'types' => config('phoenix.follow_up_types'),
            'statuses' => config('phoenix.follow_up_statuses'),
            'agents' => $this->assignableUsers(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', FollowUp::class);

        $lead = $request->filled('lead_id')
            ? Lead::with('customer')->find($request->integer('lead_id'))
            : null;

        return view('follow-ups.create', [
            'lead' => $lead,
            'types' => config('phoenix.follow_up_types'),
            'agents' => $this->assignableUsers(),
            'leads' => Lead::query()
                ->visibleTo($request->user())
                ->with('customer')
                ->latest()
                ->limit(100)
                ->get(),
        ]);
    }

    public function store(StoreFollowUpRequest $request): RedirectResponse
    {
        $this->authorize('create', FollowUp::class);

        $data = $request->validated();
        $lead = Lead::findOrFail($data['lead_id']);
        $this->authorize('view', $lead);

        $followUp = $this->followUpService->schedule($lead, $data, $request->user());

        if ($request->boolean('from_lead')) {
            return redirect()
                ->route('leads.show', $lead)
                ->with('success', $followUp->typeLabel().' scheduled.');
        }

        return redirect()
            ->route('follow-ups.index')
            ->with('success', $followUp->typeLabel().' scheduled for '.$lead->reference.'.');
    }

    public function edit(FollowUp $followUp): View
    {
        $this->authorize('update', $followUp);

        return view('follow-ups.edit', [
            'followUp' => $followUp->load(['lead.customer', 'assignee']),
            'types' => config('phoenix.follow_up_types'),
            'agents' => $this->assignableUsers(),
        ]);
    }

    public function update(UpdateFollowUpRequest $request, FollowUp $followUp): RedirectResponse
    {
        $this->followUpService->update($followUp, $request->validated(), $request->user());

        return redirect()
            ->route('follow-ups.index')
            ->with('success', 'Follow-up updated.');
    }

    public function complete(Request $request, FollowUp $followUp): RedirectResponse
    {
        $this->authorize('complete', $followUp);

        $request->validate([
            'outcome' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->followUpService->complete($followUp, $request->user(), $request->input('outcome'));

        return back()->with('success', 'Follow-up completed.');
    }

    public function cancel(Request $request, FollowUp $followUp): RedirectResponse
    {
        $this->authorize('update', $followUp);

        $this->followUpService->cancel($followUp, $request->input('outcome'));

        return back()->with('success', 'Follow-up cancelled.');
    }

    private function assignableUsers()
    {
        return User::query()->where('is_active', true)->orderBy('name')->get();
    }
}

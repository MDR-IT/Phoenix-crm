<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Models\County;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Services\ActivityTimelineService;
use App\Services\DuplicateLeadDetector;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService,
        private readonly DuplicateLeadDetector $duplicateDetector,
        private readonly ActivityTimelineService $timelineService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        $filters = $request->only([
            'q', 'status', 'priority', 'product_id', 'lead_source_id', 'county_id', 'assigned_to', 'archived',
        ]);

        $leads = Lead::query()
            ->with(['customer', 'assignee', 'product', 'leadSource', 'county'])
            ->visibleTo($request->user())
            ->filter($filters)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('leads.index', [
            'leads' => $leads,
            'filters' => $filters,
            'statuses' => config('phoenix.lead_statuses'),
            'priorities' => config('phoenix.lead_priorities'),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'sources' => LeadSource::query()->where('is_active', true)->orderBy('name')->get(),
            'counties' => County::query()->where('is_active', true)->orderBy('name')->get(),
            'agents' => $this->assignableUsers(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Lead::class);

        return view('leads.create', $this->formData([
            'selectedCustomer' => $request->filled('customer_id')
                ? Customer::find($request->integer('customer_id'))
                : null,
            'duplicates' => collect(),
        ]));
    }

    public function store(StoreLeadRequest $request): RedirectResponse|View
    {
        $this->authorize('create', Lead::class);

        $data = $request->validated();
        $data['consent_contact'] = $request->boolean('consent_contact');

        $duplicates = $this->duplicateDetector->findPossibleDuplicates(
            $data['phone'] ?? null,
            $data['phone_alt'] ?? null,
            $data['email'] ?? null
        );

        if ($duplicates->isNotEmpty() && ! $request->boolean('acknowledge_duplicates')) {
            return view('leads.create', $this->formData([
                'selectedCustomer' => ! empty($data['customer_id']) ? Customer::find($data['customer_id']) : null,
                'duplicates' => $duplicates,
                'old' => $data,
            ]))->with('warning', 'Possible duplicate leads found. Review and confirm to continue.');
        }

        $result = $this->leadService->createManual($data, $request->user());
        $lead = $result['lead'];

        $message = 'Lead '.$lead->reference.' created successfully.';
        if ($result['duplicates']->isNotEmpty()) {
            $message .= ' Possible duplicates were noted for review.';
        }

        if ($request->boolean('save_and_another')) {
            return redirect()
                ->route('leads.create')
                ->with('success', $message);
        }

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', $message)
            ->with('duplicates', $result['duplicates']);
    }

    public function show(Lead $lead): View
    {
        $this->authorize('view', $lead);

        $lead->load([
            'customer.county',
            'assignee',
            'creator',
            'product',
            'leadSource',
            'county',
            'audits.user',
            'statusHistories.user',
            'assignmentHistories.fromUser',
            'assignmentHistories.toUser',
            'assignmentHistories.assigner',
            'noteEntries.user',
            'tasks.assignee',
            'followUps.assignee',
            'activities.user',
        ]);

        $duplicates = $this->duplicateDetector->findPossibleDuplicates(
            $lead->customer->phone,
            $lead->customer->phone_alt,
            $lead->customer->email,
            $lead->id
        );

        return view('leads.show', [
            'lead' => $lead,
            'duplicates' => $duplicates,
            'timeline' => $this->timelineService->forLead($lead),
            'statuses' => config('phoenix.lead_statuses'),
            'followUpTypes' => config('phoenix.follow_up_types'),
            'priorities' => config('phoenix.lead_priorities'),
            'agents' => $this->assignableUsers(),
            'teams' => Team::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Lead $lead): View
    {
        $this->authorize('update', $lead);

        $lead->load('customer');

        return view('leads.edit', $this->formData([
            'lead' => $lead,
        ]));
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $data = $request->validated();
        $data['consent_contact'] = $request->boolean('consent_contact');

        $lead = $this->leadService->updateLead($lead, $data, $request->user());

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', 'Lead updated successfully.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $this->authorize('archive', $lead);

        $lead->delete();

        return redirect()
            ->route('leads.index')
            ->with('success', 'Lead archived successfully.');
    }

    public function restore(int $lead): RedirectResponse
    {
        $model = Lead::withTrashed()->findOrFail($lead);
        $this->authorize('archive', $model);

        $model->restore();

        return redirect()
            ->route('leads.show', $model)
            ->with('success', 'Lead restored successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Lead::class);

        $filters = $request->only([
            'q', 'status', 'priority', 'product_id', 'lead_source_id', 'county_id', 'assigned_to', 'archived',
        ]);
        $format = $request->string('format')->toString() === 'excel' ? 'excel' : 'csv';

        $leads = Lead::query()
            ->with(['customer', 'assignee', 'product', 'leadSource', 'county'])
            ->visibleTo($request->user())
            ->filter($filters)
            ->latest()
            ->get();

        $filename = 'leads-'.now()->format('Ymd-His').($format === 'excel' ? '.xls' : '.csv');
        $headers = [
            'Reference', 'Customer', 'Phone', 'Email', 'Product', 'Source', 'Campaign',
            'Status', 'Priority', 'Assignee', 'County', 'Town', 'Follow up', 'Created at',
        ];

        return response()->streamDownload(function () use ($leads, $headers, $format) {
            $out = fopen('php://output', 'w');

            if ($format === 'excel') {
                echo '<html><head><meta charset="UTF-8"></head><body><table border="1">';
                echo '<tr>';
                foreach ($headers as $header) {
                    echo '<th>'.e($header).'</th>';
                }
                echo '</tr>';
                foreach ($leads as $lead) {
                    echo '<tr>';
                    foreach ($this->exportRow($lead) as $cell) {
                        echo '<td>'.e((string) $cell).'</td>';
                    }
                    echo '</tr>';
                }
                echo '</table></body></html>';

                return;
            }

            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers);
            foreach ($leads as $lead) {
                fputcsv($out, $this->exportRow($lead));
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => $format === 'excel'
                ? 'application/vnd.ms-excel; charset=UTF-8'
                : 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return list<string|null>
     */
    private function exportRow(Lead $lead): array
    {
        return [
            $lead->reference,
            $lead->customer?->full_name,
            $lead->customer?->phone,
            $lead->customer?->email,
            $lead->product?->name,
            $lead->leadSource?->name,
            $lead->campaign,
            $lead->statusLabel(),
            $lead->priorityLabel(),
            $lead->assignee?->name,
            $lead->county?->name,
            $lead->town,
            optional($lead->follow_up_at)->format('Y-m-d H:i'),
            optional($lead->created_at)->format('Y-m-d H:i'),
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function formData(array $extra = []): array
    {
        return array_merge([
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'sources' => LeadSource::query()->where('is_active', true)->orderBy('name')->get(),
            'counties' => County::query()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => config('phoenix.lead_statuses'),
            'priorities' => config('phoenix.lead_priorities'),
            'genders' => config('phoenix.genders'),
            'agents' => $this->assignableUsers(),
            'teams' => Team::query()->where('is_active', true)->orderBy('name')->get(),
            'customers' => Customer::query()->orderBy('full_name')->limit(200)->get(),
        ], $extra);
    }

    private function assignableUsers()
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}

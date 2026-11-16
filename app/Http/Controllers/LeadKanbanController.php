<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadKanbanController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        $statuses = config('phoenix.lead_statuses');

        $leads = Lead::query()
            ->with(['customer', 'assignee', 'product'])
            ->visibleTo($request->user())
            ->whereNull('deleted_at')
            ->latest()
            ->get()
            ->groupBy('status');

        $columns = [];
        foreach ($statuses as $key => $label) {
            $columns[$key] = [
                'label' => $label,
                'leads' => $leads->get($key, collect()),
            ];
        }

        return view('leads.kanban', compact('columns', 'statuses'));
    }
}

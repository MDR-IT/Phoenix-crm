<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        $leadQuery = Lead::query()->visibleTo($user);
        $followUpQuery = FollowUp::query()->visibleTo($user);
        $taskQuery = Task::query()->visibleTo($user);

        $kpis = [
            [
                'label' => 'New Leads',
                'value' => (clone $leadQuery)->where('status', 'new')->count(),
                'hint' => 'Open pipeline',
            ],
            [
                'label' => 'Assigned to me',
                'value' => Lead::query()->where('assigned_to', $user->id)->count(),
                'hint' => 'Active ownership',
            ],
            [
                'label' => 'Follow-ups today',
                'value' => (clone $followUpQuery)->dueToday()->count(),
                'hint' => 'Callbacks & meetings',
            ],
            [
                'label' => 'Overdue follow-ups',
                'value' => (clone $followUpQuery)->overdue()->count(),
                'hint' => 'Needs action',
            ],
        ];

        $followUpsToday = FollowUp::query()
            ->with(['lead.customer', 'assignee'])
            ->visibleTo($user)
            ->dueToday()
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        $followUpsOverdue = FollowUp::query()
            ->with(['lead.customer', 'assignee'])
            ->visibleTo($user)
            ->overdue()
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        $followUpsUpcoming = FollowUp::query()
            ->with(['lead.customer', 'assignee'])
            ->visibleTo($user)
            ->upcoming()
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        $overdueTasks = Task::query()
            ->with(['assignee', 'taskable'])
            ->visibleTo($user)
            ->overdue()
            ->orderBy('due_at')
            ->limit(8)
            ->get();

        $uncontactedHours = (int) config('phoenix.uncontacted_reminder_hours', 24);
        $uncontactedLeads = Lead::query()
            ->with('customer')
            ->visibleTo($user)
            ->whereIn('status', ['new', 'assigned'])
            ->whereNull('last_contacted_at')
            ->where('created_at', '<=', now()->subHours($uncontactedHours))
            ->latest()
            ->limit(8)
            ->get();

        return view('dashboard', [
            'kpis' => $kpis,
            'followUpsToday' => $followUpsToday,
            'followUpsOverdue' => $followUpsOverdue,
            'followUpsUpcoming' => $followUpsUpcoming,
            'overdueTasks' => $overdueTasks,
            'uncontactedLeads' => $uncontactedLeads,
            'uncontactedHours' => $uncontactedHours,
            'openTaskCount' => (clone $taskQuery)->open()->count(),
        ]);
    }
}

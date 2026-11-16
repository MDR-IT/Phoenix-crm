<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        $leadQuery = Lead::query()->visibleTo($user);

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
                'label' => 'Follow-ups due',
                'value' => (clone $leadQuery)->whereNotNull('follow_up_at')->where('follow_up_at', '<=', now()->endOfDay())->count(),
                'hint' => 'Phase 3 expands this',
            ],
            [
                'label' => 'Converted',
                'value' => (clone $leadQuery)->where('status', 'converted')->count(),
                'hint' => 'Won leads',
            ],
        ];

        return view('dashboard', compact('kpis'));
    }
}

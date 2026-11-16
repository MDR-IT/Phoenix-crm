<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $kpis = [
            [
                'label' => 'New Leads',
                'value' => '—',
                'hint' => 'Phase 2',
            ],
            [
                'label' => 'Open Follow-ups',
                'value' => '—',
                'hint' => 'Phase 3',
            ],
            [
                'label' => 'Calls Today',
                'value' => '—',
                'hint' => 'Phase 5',
            ],
            [
                'label' => 'Conversions',
                'value' => '—',
                'hint' => 'Phase 6',
            ],
        ];

        return view('dashboard', compact('kpis'));
    }
}

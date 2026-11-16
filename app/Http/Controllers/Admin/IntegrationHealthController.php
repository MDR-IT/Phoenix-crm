<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Integrations\IntegrationHealthService;

class IntegrationHealthController extends Controller
{
    public function __invoke(IntegrationHealthService $health)
    {
        abort_unless(
            auth()->user()?->can('integrations.view') || auth()->user()?->can('integrations.manage'),
            403
        );

        $data = $health->dashboard();

        return view('integrations.health', $data);
    }
}

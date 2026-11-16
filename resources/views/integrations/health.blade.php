<x-app-layout>
    <x-slot name="header">Integration health</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-1">Integration health</h1>
            <p class="text-secondary small mb-0">Digital lead channels — sandbox until credentials are configured. Never treated as live without secrets.</p>
        </div>
        @can('integrations.manage')
            <a href="{{ route('integrations.settings.index') }}" class="btn btn-pc-gold">Manage settings</a>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="pc-panel"><div class="small text-secondary">Webhooks (24h)</div><div class="h3 mb-0">{{ $stats['webhooks_24h'] }}</div></div></div>
        <div class="col-md-3"><div class="pc-panel"><div class="small text-secondary">Processed (24h)</div><div class="h3 mb-0">{{ $stats['processed_24h'] }}</div></div></div>
        <div class="col-md-3"><div class="pc-panel"><div class="small text-secondary">Failed (open)</div><div class="h3 mb-0">{{ $stats['failed_open'] }}</div></div></div>
        <div class="col-md-3"><div class="pc-panel"><div class="small text-secondary">Digital leads (24h)</div><div class="h3 mb-0">{{ $stats['digital_leads_24h'] }}</div></div></div>
    </div>

    <div class="pc-panel mb-4">
        <h2 class="h5">Channel status</h2>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Provider</th>
                    <th>Mode</th>
                    <th>Health</th>
                    <th>Enabled</th>
                    <th>Last success</th>
                    <th>Last error</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($settings as $setting)
                    <tr>
                        <td>
                            <strong>{{ $setting->display_name }}</strong>
                            <div class="small text-secondary">{{ $setting->provider }}</div>
                        </td>
                        <td>
                            @if($setting->usesSandbox())
                                <span class="badge text-bg-warning">Sandbox / pending</span>
                            @else
                                <span class="badge text-bg-success">Live</span>
                            @endif
                        </td>
                        <td>{{ $setting->healthLabel() }}</td>
                        <td>{{ $setting->is_enabled ? 'Yes' : 'No' }}</td>
                        <td class="small">{{ optional($setting->last_success_at)->format('d M Y H:i') ?: '—' }}</td>
                        <td class="small text-danger">{{ \Illuminate\Support\Str::limit($setting->last_error_message, 80) ?: '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="pc-panel">
        <h2 class="h5">Instagram — unsupported (by design)</h2>
        <p class="small text-secondary">Only official Meta Instagram Messaging webhooks are supported. The following are explicitly out of scope:</p>
        <ul class="mb-0">
            @foreach ($unsupported_instagram as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">Integration settings</x-slot>

    <div class="mb-3">
        <h1 class="h4 mb-1">Integration settings</h1>
        <p class="text-secondary small mb-0">Credentials are encrypted at rest. Leave blank to keep existing secrets. Without credentials the channel stays in sandbox / pending configuration.</p>
    </div>

    <div class="row g-3">
        @foreach ($settings as $setting)
            <div class="col-md-6">
                <div class="pc-panel h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h2 class="h5 mb-1">{{ $setting->display_name }}</h2>
                            <div class="small text-secondary">{{ $setting->provider }}</div>
                        </div>
                        @if($setting->usesSandbox())
                            <span class="badge text-bg-warning">Pending / sandbox</span>
                        @else
                            <span class="badge text-bg-success">Live ready</span>
                        @endif
                    </div>
                    <p class="small mb-3">Health: {{ $setting->healthLabel() }} · Enabled: {{ $setting->is_enabled ? 'yes' : 'no' }}</p>
                    @can('integrations.manage')
                        <a href="{{ route('integrations.settings.edit', $setting->provider) }}" class="btn btn-sm btn-pc-gold">Configure</a>
                    @endcan
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>

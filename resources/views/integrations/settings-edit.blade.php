<x-app-layout>
    <x-slot name="header">Configure {{ $setting->display_name }}</x-slot>

    <div class="mb-3">
        <a href="{{ route('integrations.settings.index') }}" class="small">&larr; All integrations</a>
        <h1 class="h4 mb-1 mt-2">{{ $setting->display_name }}</h1>
        <p class="text-secondary small mb-0">Secrets are stored encrypted. Masked values (••••) are unchanged unless you overwrite or clear them.</p>
    </div>

    <div class="pc-panel">
        <form method="POST" action="{{ route('integrations.settings.update', $setting->provider) }}">
            @csrf
            @method('PUT')

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Mode</label>
                    <select name="mode" class="form-select" required>
                        @foreach (['pending' => 'Pending configuration', 'sandbox' => 'Sandbox', 'live' => 'Live'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('mode', $setting->mode) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Assignment strategy</label>
                    <select name="assignment_strategy" class="form-select">
                        @foreach (['round_robin' => 'Round robin', 'product' => 'By product', 'region' => 'By region', 'team' => 'By team', 'none' => 'No auto-assign'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('assignment_strategy', $setting->config['assignment_strategy'] ?? 'round_robin') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_enabled" value="1" id="is_enabled" @checked(old('is_enabled', $setting->is_enabled))>
                        <label class="form-check-label" for="is_enabled">Enabled</label>
                    </div>
                </div>
            </div>

            <h2 class="h6">Credentials</h2>
            <div class="row g-3 mb-3">
                @foreach ($requiredKeys as $key)
                    <div class="col-md-6">
                        <label class="form-label">{{ str_replace('_', ' ', ucfirst($key)) }}</label>
                        <input type="text" name="credentials[{{ $key }}]" class="form-control" value="{{ old('credentials.'.$key, $masked[$key] ?? '') }}" autocomplete="off" placeholder="Leave masked value to keep">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="clear_credentials[]" value="{{ $key }}" id="clear_{{ $key }}">
                            <label class="form-check-label small" for="clear_{{ $key }}">Clear this secret</label>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($setting->provider === 'instagram')
                <div class="alert alert-warning small">
                    Instagram supports official Meta Messaging webhooks only. Organic scraping and unofficial APIs are not available.
                </div>
            @endif

            <button type="submit" class="btn btn-pc-gold">Save settings</button>
        </form>
    </div>
</x-app-layout>

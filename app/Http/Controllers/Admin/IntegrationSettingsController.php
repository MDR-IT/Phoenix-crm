<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationSetting;
use App\Services\Integrations\IntegrationSettingsService;
use Illuminate\Http\Request;

class IntegrationSettingsController extends Controller
{
    public function __construct(private readonly IntegrationSettingsService $settings) {}

    public function index()
    {
        $this->authorizeIntegrations();
        $settings = $this->settings->ensureDefaults();

        return view('integrations.settings', [
            'settings' => $settings,
            'catalog' => $this->settings->catalog(),
        ]);
    }

    public function edit(string $provider)
    {
        $this->authorizeManage();
        $setting = $this->settings->get($provider);
        $creds = $setting->credentials();

        // Mask secrets for display
        $masked = [];
        foreach ($creds as $key => $value) {
            $masked[$key] = $this->mask((string) $value);
        }

        return view('integrations.settings-edit', [
            'setting' => $setting,
            'masked' => $masked,
            'requiredKeys' => $setting->requiredCredentialKeys(),
        ]);
    }

    public function update(Request $request, string $provider)
    {
        $this->authorizeManage();
        $setting = $this->settings->get($provider);

        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'mode' => ['required', 'in:pending,sandbox,live'],
            'assignment_strategy' => ['nullable', 'in:none,round_robin,product,region,team'],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:2000'],
            'clear_credentials' => ['nullable', 'array'],
        ]);

        $incoming = $validated['credentials'] ?? [];
        $clear = $validated['clear_credentials'] ?? [];

        // Ignore masked placeholders (unchanged secrets)
        foreach ($incoming as $key => $value) {
            if (is_string($value) && str_contains($value, '••••')) {
                unset($incoming[$key]);
            }
            if (in_array($key, $clear, true)) {
                $incoming[$key] = '';
            }
        }

        $this->settings->update(
            $provider,
            $incoming,
            [
                'assignment_strategy' => $validated['assignment_strategy'] ?? ($setting->config['assignment_strategy'] ?? 'round_robin'),
            ],
            (bool) ($validated['is_enabled'] ?? false),
            $validated['mode'],
            $request->user()
        );

        return redirect()
            ->route('integrations.settings.edit', $provider)
            ->with('success', 'Integration settings saved. Secrets are stored encrypted.');
    }

    private function mask(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (strlen($value) <= 8) {
            return '••••••••';
        }

        return substr($value, 0, 4).str_repeat('•', max(4, strlen($value) - 8)).substr($value, -4);
    }

    private function authorizeIntegrations(): void
    {
        abort_unless(
            auth()->user()?->can('integrations.view') || auth()->user()?->can('integrations.manage'),
            403
        );
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('integrations.manage'), 403);
    }
}

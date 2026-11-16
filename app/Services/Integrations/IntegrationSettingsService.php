<?php

namespace App\Services\Integrations;

use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Support\Collection;

class IntegrationSettingsService
{
    /**
     * @return list<array{provider: string, display_name: string, required: list<string>}>
     */
    public function catalog(): array
    {
        return [
            [
                'provider' => 'whatsapp',
                'display_name' => 'WhatsApp Business Cloud API',
                'required' => ['access_token', 'phone_number_id', 'app_secret', 'verify_token'],
            ],
            [
                'provider' => 'meta',
                'display_name' => 'Meta (Facebook Lead Ads + Messenger)',
                'required' => ['access_token', 'app_secret', 'verify_token', 'page_id'],
            ],
            [
                'provider' => 'instagram',
                'display_name' => 'Instagram (supported Meta APIs)',
                'required' => ['access_token', 'app_secret', 'verify_token'],
            ],
            [
                'provider' => 'website',
                'display_name' => 'Website lead form',
                'required' => ['api_key'],
            ],
        ];
    }

    public function ensureDefaults(): Collection
    {
        foreach ($this->catalog() as $item) {
            IntegrationSetting::query()->firstOrCreate(
                ['provider' => $item['provider']],
                [
                    'display_name' => $item['display_name'],
                    'is_enabled' => false,
                    'mode' => IntegrationSetting::MODE_PENDING,
                    'health_status' => IntegrationSetting::HEALTH_PENDING,
                    'config' => [
                        'assignment_strategy' => 'round_robin',
                        'notify_roles' => ['Sales Manager', 'Call Centre Supervisor', 'Marketing Officer'],
                    ],
                ]
            );
        }

        return IntegrationSetting::query()->orderBy('id')->get();
    }

    public function get(string $provider): IntegrationSetting
    {
        $this->ensureDefaults();

        return IntegrationSetting::query()->where('provider', $provider)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $config
     */
    public function update(
        string $provider,
        array $credentials,
        array $config,
        bool $isEnabled,
        string $mode,
        ?User $actor = null,
    ): IntegrationSetting {
        $setting = $this->get($provider);

        $mergedCreds = array_merge($setting->credentials(), array_filter(
            $credentials,
            fn ($v) => $v !== null && $v !== ''
        ));

        // Allow clearing a key by sending empty string with explicit clear flag handled in controller.
        foreach ($credentials as $key => $value) {
            if ($value === '' || $value === null) {
                unset($mergedCreds[$key]);
            } else {
                $mergedCreds[$key] = $value;
            }
        }

        $setting->setCredentials($mergedCreds);
        $setting->config = array_merge($setting->config ?? [], $config);
        $setting->is_enabled = $isEnabled;
        $setting->mode = $mode;
        $setting->updated_by = $actor?->id;

        $required = $setting->requiredCredentialKeys();
        if (! $setting->hasCredentials($required)) {
            $setting->mode = IntegrationSetting::MODE_PENDING;
            $setting->health_status = IntegrationSetting::HEALTH_PENDING;
        } elseif ($mode === IntegrationSetting::MODE_LIVE && $isEnabled) {
            $setting->health_status = IntegrationSetting::HEALTH_OK;
        } else {
            $setting->mode = IntegrationSetting::MODE_SANDBOX;
            $setting->health_status = IntegrationSetting::HEALTH_SANDBOX;
        }

        $setting->save();

        return $setting->fresh();
    }

    public function markSuccess(string $provider): void
    {
        $setting = $this->get($provider);
        $setting->forceFill([
            'last_success_at' => now(),
            'health_status' => $setting->usesSandbox()
                ? IntegrationSetting::HEALTH_SANDBOX
                : IntegrationSetting::HEALTH_OK,
            'last_error_message' => null,
        ])->save();
    }

    public function markError(string $provider, string $message): void
    {
        $setting = $this->get($provider);
        $setting->forceFill([
            'last_error_at' => now(),
            'last_error_message' => $message,
            'health_status' => $setting->usesSandbox()
                ? IntegrationSetting::HEALTH_SANDBOX
                : IntegrationSetting::HEALTH_ERROR,
        ])->save();
    }
}

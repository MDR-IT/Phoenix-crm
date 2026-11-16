<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class IntegrationSetting extends Model
{
    public const MODE_SANDBOX = 'sandbox';

    public const MODE_LIVE = 'live';

    public const MODE_PENDING = 'pending';

    public const HEALTH_OK = 'healthy';

    public const HEALTH_DEGRADED = 'degraded';

    public const HEALTH_ERROR = 'error';

    public const HEALTH_PENDING = 'pending_configuration';

    public const HEALTH_SANDBOX = 'sandbox';

    protected $fillable = [
        'provider',
        'display_name',
        'is_enabled',
        'credentials_encrypted',
        'config',
        'mode',
        'health_status',
        'last_success_at',
        'last_error_at',
        'last_error_message',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'config' => 'array',
            'last_success_at' => 'datetime',
            'last_error_at' => 'datetime',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function credentials(): array
    {
        if (blank($this->credentials_encrypted)) {
            return [];
        }

        try {
            $decoded = Crypt::decryptString($this->credentials_encrypted);
            $data = json_decode($decoded, true);

            return is_array($data) ? $data : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function setCredentials(array $credentials): void
    {
        $filtered = array_filter($credentials, fn ($v) => $v !== null && $v !== '');
        $this->credentials_encrypted = empty($filtered)
            ? null
            : Crypt::encryptString(json_encode($filtered));
    }

    public function hasCredentials(array $requiredKeys = []): bool
    {
        $creds = $this->credentials();
        if ($creds === []) {
            return false;
        }

        foreach ($requiredKeys as $key) {
            if (blank($creds[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    public function isLiveReady(array $requiredKeys = []): bool
    {
        return $this->is_enabled
            && $this->mode === self::MODE_LIVE
            && $this->hasCredentials($requiredKeys);
    }

    public function usesSandbox(): bool
    {
        return ! $this->isLiveReady($this->requiredCredentialKeys());
    }

    /**
     * @return list<string>
     */
    public function requiredCredentialKeys(): array
    {
        return match ($this->provider) {
            'whatsapp' => ['access_token', 'phone_number_id', 'app_secret', 'verify_token'],
            'meta' => ['access_token', 'app_secret', 'verify_token', 'page_id'],
            'instagram' => ['access_token', 'app_secret', 'verify_token'],
            'website' => ['api_key'],
            default => [],
        };
    }

    public function modeLabel(): string
    {
        return match ($this->mode) {
            self::MODE_LIVE => 'Live',
            self::MODE_SANDBOX => 'Sandbox',
            default => 'Pending configuration',
        };
    }

    public function healthLabel(): string
    {
        return match ($this->health_status) {
            self::HEALTH_OK => 'Healthy',
            self::HEALTH_DEGRADED => 'Degraded',
            self::HEALTH_ERROR => 'Error',
            self::HEALTH_SANDBOX => 'Sandbox (not live)',
            default => 'Pending configuration',
        };
    }
}

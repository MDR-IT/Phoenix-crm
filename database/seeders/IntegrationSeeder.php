<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Integrations\IntegrationSettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class IntegrationSeeder extends Seeder
{
    public function run(): void
    {
        app(IntegrationSettingsService::class)->ensureDefaults();

        $email = config('phoenix.integrations.system_user_email', 'integrations@phoenixcapital.local');

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Integration Bot',
                'password' => Hash::make(Str::password(32)),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if (method_exists($user, 'assignRole')) {
            try {
                $user->assignRole('Marketing Officer');
            } catch (\Throwable) {
                // Role may not exist yet in partial test seeds.
            }
        }

        // Bootstrap env credentials into encrypted settings when present (never invent secrets).
        $this->bootstrapFromEnv('whatsapp', [
            'access_token' => config('phoenix.integrations.whatsapp.access_token'),
            'phone_number_id' => config('phoenix.integrations.whatsapp.phone_number_id'),
            'app_secret' => config('phoenix.integrations.whatsapp.app_secret'),
            'verify_token' => config('phoenix.integrations.whatsapp.verify_token'),
        ]);

        $this->bootstrapFromEnv('meta', [
            'access_token' => config('phoenix.integrations.meta.access_token'),
            'app_secret' => config('phoenix.integrations.meta.app_secret'),
            'verify_token' => config('phoenix.integrations.meta.verify_token'),
            'page_id' => config('phoenix.integrations.meta.page_id'),
        ]);

        $this->bootstrapFromEnv('instagram', [
            'access_token' => config('phoenix.integrations.instagram.access_token'),
            'app_secret' => config('phoenix.integrations.instagram.app_secret'),
            'verify_token' => config('phoenix.integrations.instagram.verify_token'),
        ]);

        $this->bootstrapFromEnv('website', [
            'api_key' => config('phoenix.integrations.website.api_key'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function bootstrapFromEnv(string $provider, array $credentials): void
    {
        $filtered = array_filter($credentials, fn ($v) => filled($v));
        if ($filtered === []) {
            return;
        }

        app(IntegrationSettingsService::class)->update(
            $provider,
            $filtered,
            [],
            true,
            'live',
            null
        );
    }
}

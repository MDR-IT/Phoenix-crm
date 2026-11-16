<?php

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Models\Lead;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Integrations\IntegrationSettingsService;
use App\Services\Integrations\SignatureVerifier;
use Database\Seeders\CrmLookupSeeder;
use Database\Seeders\IntegrationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DigitalLeadIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CrmLookupSeeder::class);
        $this->seed(IntegrationSeeder::class);

        User::factory()->create([
            'name' => 'Agent RR',
            'email' => 'agent-rr@phoenixcapital.local',
        ])->assignRole('Sales Agent');
    }

    public function test_website_form_submission_creates_lead_in_sandbox(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/leads/website', [
            'first_name' => 'Amina',
            'last_name' => 'Otieno',
            'phone' => '0712345678',
            'email' => 'amina@example.com',
            'message' => 'Need a business loan',
            'consent' => true,
            'idempotency_key' => 'test-web-1',
            'campaign' => 'homepage',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('sandbox', true);
        $response->assertJsonPath('mode', 'sandbox_pending_configuration');

        $this->assertDatabaseHas('leads', [
            'channel' => 'website',
        ]);

        $lead = Lead::query()->where('channel', 'website')->first();
        $this->assertNotNull($lead);
        $this->assertSame('Amina Otieno', $lead->customer->full_name);

        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'website',
            'external_id' => 'test-web-1',
            'status' => WebhookEvent::STATUS_PROCESSED,
        ]);

        $this->assertDatabaseHas('channel_interactions', [
            'channel' => 'website',
            'lead_id' => $lead->id,
        ]);

        $this->assertDatabaseHas('activities', [
            'subject_id' => $lead->id,
            'type' => 'channel_interaction',
        ]);
    }

    public function test_website_submission_is_idempotent(): void
    {
        Notification::fake();

        $payload = [
            'first_name' => 'Brian',
            'last_name' => 'Kamau',
            'phone' => '0722000001',
            'message' => 'Callback please',
            'idempotency_key' => 'idem-web-99',
        ];

        $this->postJson('/api/leads/website', $payload)->assertCreated();
        $second = $this->postJson('/api/leads/website', $payload);

        $second->assertOk();
        $this->assertSame(1, Lead::query()->where('channel', 'website')->count());
        $this->assertSame(1, WebhookEvent::query()->where('external_id', 'idem-web-99')->count());
    }

    public function test_website_rejects_spam_honeypot(): void
    {
        $response = $this->postJson('/api/leads/website', [
            'first_name' => 'Bot',
            'phone' => '0700000000',
            'website_url' => 'http://spam.test',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Lead::query()->count());
    }

    public function test_website_live_mode_requires_valid_api_key(): void
    {
        app(IntegrationSettingsService::class)->update(
            'website',
            ['api_key' => 'secret-live-key'],
            [],
            true,
            'live'
        );

        $this->postJson('/api/leads/website', [
            'first_name' => 'NoKey',
            'phone' => '0711111111',
        ], [
            'X-Phoenix-Api-Key' => 'wrong',
        ])->assertUnauthorized();

        $this->postJson('/api/leads/website', [
            'first_name' => 'WithKey',
            'last_name' => 'Ok',
            'phone' => '0711111112',
            'idempotency_key' => 'live-web-1',
        ], [
            'X-Phoenix-Api-Key' => 'secret-live-key',
        ])->assertCreated()
            ->assertJsonPath('sandbox', false)
            ->assertJsonPath('mode', 'live');
    }

    public function test_whatsapp_webhook_creates_lead_in_sandbox(): void
    {
        Notification::fake();

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '254700111222',
                            'id' => 'wamid.TEST123',
                            'timestamp' => (string) time(),
                            'type' => 'text',
                            'text' => ['body' => 'Hi, loan info please'],
                        ]],
                        'contacts' => [[
                            'profile' => ['name' => 'Wanjiku'],
                            'wa_id' => '254700111222',
                        ]],
                        'metadata' => ['phone_number_id' => 'sandbox'],
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/webhooks/whatsapp', $payload);

        $response->assertOk();
        $response->assertJsonPath('sandbox', true);

        $this->assertDatabaseHas('leads', ['channel' => 'whatsapp']);
        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'whatsapp',
            'external_id' => 'wamid.TEST123',
            'status' => WebhookEvent::STATUS_PROCESSED,
        ]);
        $this->assertDatabaseHas('channel_interactions', [
            'channel' => 'whatsapp',
            'external_id' => 'wamid.TEST123',
        ]);
    }

    public function test_whatsapp_webhook_idempotent_by_message_id(): void
    {
        Notification::fake();

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '254700333444',
                            'id' => 'wamid.IDEMPOTENT',
                            'timestamp' => (string) time(),
                            'type' => 'text',
                            'text' => ['body' => 'Ping'],
                        ]],
                        'contacts' => [['profile' => ['name' => 'Sam'], 'wa_id' => '254700333444']],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/api/webhooks/whatsapp', $payload)->assertOk();
        $this->postJson('/api/webhooks/whatsapp', $payload)->assertOk();

        $this->assertSame(1, Lead::query()->where('channel', 'whatsapp')->count());
        $this->assertSame(1, WebhookEvent::query()->where('external_id', 'wamid.IDEMPOTENT')->count());
    }

    public function test_meta_leadgen_webhook_creates_facebook_lead_in_sandbox(): void
    {
        Notification::fake();

        $payload = [
            'object' => 'page',
            'entry' => [[
                'changes' => [[
                    'field' => 'leadgen',
                    'value' => [
                        'leadgen_id' => 'leadgen-555',
                        'page_id' => 'page-1',
                        'form_id' => 'form-1',
                        'ad_id' => 'ad-9',
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/webhooks/meta', $payload);
        $response->assertOk();
        $response->assertJsonPath('sandbox', true);

        $this->assertDatabaseHas('leads', ['channel' => 'facebook']);
        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'meta',
            'external_id' => 'leadgen-555',
            'status' => WebhookEvent::STATUS_PROCESSED,
        ]);
    }

    public function test_meta_live_mode_rejects_invalid_signature(): void
    {
        app(IntegrationSettingsService::class)->update(
            'meta',
            [
                'access_token' => 'token',
                'app_secret' => 'app-secret-value',
                'verify_token' => 'verify-me',
                'page_id' => '123',
            ],
            [],
            true,
            'live'
        );

        $body = json_encode(['object' => 'page', 'entry' => []]);
        $response = $this->call(
            'POST',
            '/api/webhooks/meta',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => 'sha256=deadbeef',
            ],
            $body
        );

        $response->assertOk();
        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'meta',
            'status' => WebhookEvent::STATUS_REJECTED,
        ]);
    }

    public function test_meta_signature_verifier_accepts_valid_hmac(): void
    {
        $raw = '{"object":"page"}';
        $secret = 'test-secret';
        $header = 'sha256='.hash_hmac('sha256', $raw, $secret);

        $this->assertTrue(app(SignatureVerifier::class)->verifyMetaHubSignature($header, $raw, $secret));
        $this->assertFalse(app(SignatureVerifier::class)->verifyMetaHubSignature('sha256=nope', $raw, $secret));
    }

    public function test_whatsapp_verify_challenge(): void
    {
        app(IntegrationSettingsService::class)->update(
            'whatsapp',
            [
                'access_token' => 't',
                'phone_number_id' => '1',
                'app_secret' => 's',
                'verify_token' => 'phoenix-verify',
            ],
            [],
            true,
            'live'
        );

        $this->get('/api/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=phoenix-verify&hub_challenge=12345')
            ->assertOk()
            ->assertSee('12345');

        $this->get('/api/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=12345')
            ->assertForbidden();
    }

    public function test_integration_screens_require_permission(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('Sales Agent');

        $this->actingAs($agent)->get(route('integrations.health'))->assertForbidden();

        $marketer = User::factory()->create();
        $marketer->assignRole('Marketing Officer');

        $this->actingAs($marketer)->get(route('integrations.health'))->assertOk();
        $this->actingAs($marketer)->get(route('integrations.settings.index'))->assertOk();
        $this->actingAs($marketer)->get(route('integrations.whatsapp'))->assertOk();
        $this->actingAs($marketer)->get(route('integrations.social-leads'))->assertOk();
        $this->actingAs($marketer)->get(route('integrations.webhooks.index'))->assertOk();
    }

    public function test_integration_settings_seeded_as_pending_without_credentials(): void
    {
        $setting = IntegrationSetting::query()->where('provider', 'whatsapp')->first();
        $this->assertNotNull($setting);
        $this->assertTrue($setting->usesSandbox());
        $this->assertContains($setting->health_status, [
            IntegrationSetting::HEALTH_PENDING,
            IntegrationSetting::HEALTH_SANDBOX,
        ]);
    }
}

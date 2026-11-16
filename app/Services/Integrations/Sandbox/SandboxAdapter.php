<?php

namespace App\Services\Integrations\Sandbox;

use App\Services\Integrations\NormalizedLeadPayload;
use Illuminate\Support\Str;

/**
 * Test / sandbox payloads used when live credentials are missing.
 * Never reports mode as live — callers must surface "pending configuration" / sandbox.
 */
class SandboxAdapter
{
    public function sampleWhatsAppPayload(): NormalizedLeadPayload
    {
        return new NormalizedLeadPayload(
            channel: 'whatsapp',
            externalId: 'sandbox-wa-'.Str::uuid(),
            fullName: 'Sandbox WhatsApp Lead',
            phone: '+254712000001',
            message: 'Hello, I need a microfinance loan (sandbox).',
            messageType: 'text',
            senderHandle: '254712000001',
            consentContact: true,
            occurredAt: now(),
            meta: ['sandbox' => true, 'pending_configuration' => true],
            eventType: 'whatsapp.message',
        );
    }

    public function sampleMetaLeadPayload(string $eventType = 'leadgen'): NormalizedLeadPayload
    {
        return new NormalizedLeadPayload(
            channel: $eventType === 'messenger' ? 'messenger' : 'facebook',
            externalId: 'sandbox-meta-'.Str::uuid(),
            firstName: 'Sandbox',
            lastName: 'Meta Lead',
            phone: '+254712000002',
            email: 'sandbox.meta@example.com',
            message: $eventType === 'messenger'
                ? 'Hi, interested in your loan products (sandbox Messenger).'
                : 'Facebook Lead Ad sandbox submission',
            messageType: $eventType === 'messenger' ? 'text' : 'lead_form',
            campaign: 'sandbox-campaign',
            consentContact: true,
            occurredAt: now(),
            meta: ['sandbox' => true, 'pending_configuration' => true],
            eventType: $eventType,
        );
    }

    public function sampleInstagramPayload(): NormalizedLeadPayload
    {
        return new NormalizedLeadPayload(
            channel: 'instagram',
            externalId: 'sandbox-ig-'.Str::uuid(),
            fullName: 'Sandbox Instagram Lead',
            message: 'Interested via Instagram DM (sandbox).',
            messageType: 'text',
            senderHandle: 'sandbox_ig_user',
            phone: '+254712000003',
            consentContact: true,
            occurredAt: now(),
            meta: ['sandbox' => true, 'pending_configuration' => true],
            eventType: 'instagram_message',
        );
    }

    public function sampleWebsitePayload(): NormalizedLeadPayload
    {
        return new NormalizedLeadPayload(
            channel: 'website',
            externalId: 'sandbox-web-'.Str::uuid(),
            firstName: 'Sandbox',
            lastName: 'Website Lead',
            phone: '+254712000004',
            email: 'sandbox.web@example.com',
            message: 'Website form sandbox submission from phoenixcapital embed.',
            messageType: 'form',
            campaign: 'sandbox-website',
            consentContact: true,
            occurredAt: now(),
            meta: ['sandbox' => true, 'pending_configuration' => true],
            eventType: 'form_submit',
        );
    }

    /**
     * @return array{ok: bool, sandbox: bool, error: string}
     */
    public function simulateWhatsAppSend(string $to, string $body): array
    {
        return [
            'ok' => false,
            'sandbox' => true,
            'error' => 'WhatsApp send skipped — credentials pending configuration (sandbox). Message to '.$to.' was not delivered: '.Str::limit($body, 60),
        ];
    }
}

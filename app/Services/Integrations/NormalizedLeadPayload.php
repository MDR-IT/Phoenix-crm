<?php

namespace App\Services\Integrations;

/**
 * Normalized inbound lead/message payload shared by all digital channels.
 */
class NormalizedLeadPayload
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $channel,
        public readonly string $externalId,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $fullName = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $message = null,
        public readonly ?string $messageType = 'text',
        public readonly ?string $senderHandle = null,
        public readonly ?string $campaign = null,
        public readonly ?string $productHint = null,
        public readonly bool $consentContact = true,
        public readonly ?\DateTimeInterface $occurredAt = null,
        public readonly array $meta = [],
        public readonly array $raw = [],
        public readonly bool $isUnsupported = false,
        public readonly ?string $unsupportedReason = null,
        public readonly ?string $eventType = null,
    ) {}

    public function resolvedFirstName(): string
    {
        if (filled($this->firstName)) {
            return (string) $this->firstName;
        }

        if (filled($this->fullName)) {
            $parts = preg_split('/\s+/', trim((string) $this->fullName), 2) ?: [];

            return $parts[0] ?: 'Unknown';
        }

        return 'Unknown';
    }

    public function resolvedLastName(): string
    {
        if (filled($this->lastName)) {
            return (string) $this->lastName;
        }

        if (filled($this->fullName)) {
            $parts = preg_split('/\s+/', trim((string) $this->fullName), 2) ?: [];

            return $parts[1] ?? 'Lead';
        }

        return 'Lead';
    }

    /**
     * @return array<string, mixed>
     */
    public function toLeadData(): array
    {
        return [
            'first_name' => $this->resolvedFirstName(),
            'last_name' => $this->resolvedLastName(),
            'phone' => $this->phone,
            'email' => $this->email,
            'consent_contact' => $this->consentContact,
            'campaign' => $this->campaign,
            'description' => $this->message,
            'notes' => $this->message,
            'channel' => $this->channel,
        ];
    }
}

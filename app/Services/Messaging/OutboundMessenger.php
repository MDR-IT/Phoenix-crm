<?php

namespace App\Services\Messaging;

/**
 * Facade over configurable email/SMS providers. Defaults to stubs until credentials exist.
 */
class OutboundMessenger
{
    public function __construct(
        private readonly MessageProviderInterface $emailProvider,
        private readonly MessageProviderInterface $smsProvider,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function email(string $to, string $subject, string $body, array $context = []): MessageResult
    {
        return $this->emailProvider->send($to, $subject, $body, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function sms(string $to, string $body, array $context = []): MessageResult
    {
        return $this->smsProvider->send($to, 'SMS', $body, $context);
    }

    public function emailConfigured(): bool
    {
        return $this->emailProvider->isConfigured();
    }

    public function smsConfigured(): bool
    {
        return $this->smsProvider->isConfigured();
    }
}

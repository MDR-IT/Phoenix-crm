<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Log;

/**
 * Configurable SMS stub — reads provider name/keys from config/env only; never hardcodes secrets.
 */
class NullSmsProvider implements MessageProviderInterface
{
    public function send(string $to, string $subject, string $body, array $context = []): MessageResult
    {
        $message = sprintf(
            '[sms stub] driver=%s to=%s (not sent — set PHOENIX_SMS_DRIVER and provider credentials)',
            $this->driver(),
            $to
        );

        Log::info($message, [
            'channel' => 'sms',
            'configured' => $this->isConfigured(),
            'body_preview' => mb_substr($body, 0, 80),
            'context' => $context,
        ]);

        return MessageResult::stubbed($this->driver(), 'sms', $message);
    }

    public function driver(): string
    {
        return (string) config('phoenix.messaging.sms.driver', 'null');
    }

    public function isConfigured(): bool
    {
        $driver = $this->driver();

        if ($driver === 'null' || $driver === 'log') {
            return false;
        }

        return filled(config('phoenix.messaging.sms.api_key'))
            || filled(config('phoenix.messaging.sms.username'));
    }
}

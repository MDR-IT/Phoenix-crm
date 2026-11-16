<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Log;

/**
 * Configurable email stub — never hardcodes secrets; logs when credentials are absent.
 */
class NullEmailProvider implements MessageProviderInterface
{
    public function send(string $to, string $subject, string $body, array $context = []): MessageResult
    {
        $message = sprintf(
            '[email stub] driver=%s to=%s subject=%s (not sent — configure PHOENIX_EMAIL_DRIVER / mailer credentials)',
            $this->driver(),
            $to,
            $subject
        );

        Log::info($message, [
            'channel' => 'email',
            'configured' => $this->isConfigured(),
            'context' => $context,
        ]);

        return MessageResult::stubbed($this->driver(), 'email', $message);
    }

    public function driver(): string
    {
        return (string) config('phoenix.messaging.email.driver', 'log');
    }

    public function isConfigured(): bool
    {
        $mailer = config('mail.default');

        if ($mailer === 'log' || $mailer === 'array') {
            return false;
        }

        return filled(config('mail.mailers.smtp.username'))
            || filled(env('PHOENIX_EMAIL_API_KEY'));
    }
}

<?php

namespace App\Services\Messaging;

interface MessageProviderInterface
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function send(string $to, string $subject, string $body, array $context = []): MessageResult;

    public function driver(): string;

    public function isConfigured(): bool;
}

<?php

namespace App\Services\Messaging;

class MessageResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $driver,
        public readonly string $channel,
        public readonly ?string $message = null,
        public readonly bool $stubbed = false,
    ) {}

    public static function stubbed(string $driver, string $channel, string $message): self
    {
        return new self(true, $driver, $channel, $message, true);
    }

    public static function failed(string $driver, string $channel, string $message): self
    {
        return new self(false, $driver, $channel, $message, false);
    }
}

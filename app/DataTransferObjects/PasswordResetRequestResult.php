<?php

namespace App\DataTransferObjects;

use App\Models\User;

class PasswordResetRequestResult
{
    public function __construct(
        public readonly bool $sent,
        public readonly string $message,
        public readonly ?User $user = null,
        public readonly ?string $token = null,
        public readonly ?string $channel = null,
        public readonly ?string $destination = null,
        public readonly ?\DateTimeInterface $expiresAt = null,
    ) {}

    public static function notSent(string $message): self
    {
        return new self(sent: false, message: $message);
    }

    public static function sent(
        string $message,
        User $user,
        string $token,
        string $channel,
        string $destination,
        \DateTimeInterface $expiresAt,
    ): self {
        return new self(
            sent: true,
            message: $message,
            user: $user,
            token: $token,
            channel: $channel,
            destination: $destination,
            expiresAt: $expiresAt,
        );
    }
}

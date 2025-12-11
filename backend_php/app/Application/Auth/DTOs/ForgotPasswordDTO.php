<?php

namespace App\Application\Auth\DTOs;

class ForgotPasswordDTO
{
    public function __construct(
        public string $email
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['email']
        );
    }
}

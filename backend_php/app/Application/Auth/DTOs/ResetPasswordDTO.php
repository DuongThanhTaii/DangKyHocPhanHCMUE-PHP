<?php

namespace App\Application\Auth\DTOs;

class ResetPasswordDTO
{
    public function __construct(
        public string $token,
        public string $email,
        public string $password,
        public string $passwordConfirmation
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['token'],
            $data['email'],
            $data['password'],
            $data['password_confirmation']
        );
    }
}

<?php

namespace App\Application\Auth\DTOs;

class ChangePasswordDTO
{
    public function __construct(
        public string $oldPassword,
        public string $newPassword,
        public string $newPasswordConfirmation
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['old_password'],
            $data['new_password'],
            $data['new_password_confirmation']
        );
    }
}

<?php

namespace App\Domain\Auth\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object for Email
 * 
 * Encapsulates email validation and guarantees a valid email throughout the domain.
 */
final class Email
{
    private string $value;

    public function __construct(string $email)
    {
        $email = trim(strtolower($email));
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email không hợp lệ: {$email}");
        }

        $this->value = $email;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

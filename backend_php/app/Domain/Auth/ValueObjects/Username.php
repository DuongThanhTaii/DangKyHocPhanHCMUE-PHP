<?php

namespace App\Domain\Auth\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object for Username
 * 
 * Encapsulates username validation rules.
 */
final class Username
{
    private string $value;

    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 50;

    public function __construct(string $username)
    {
        $username = trim($username);
        
        if (strlen($username) < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                "Tên đăng nhập phải có ít nhất " . self::MIN_LENGTH . " ký tự"
            );
        }

        if (strlen($username) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                "Tên đăng nhập không được quá " . self::MAX_LENGTH . " ký tự"
            );
        }

        $this->value = $username;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Username $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

<?php

namespace App\Domain\Auth\ValueObjects;

use InvalidArgumentException;
use Illuminate\Support\Facades\Hash;

/**
 * Value Object for Password
 * 
 * Handles password validation, hashing, and comparison.
 * Never stores plain password - only hashed version.
 */
final class Password
{
    private string $hashedValue;

    private const MIN_LENGTH = 6;

    /**
     * Create from plain password (for new passwords)
     */
    public static function fromPlain(string $plainPassword): self
    {
        self::validate($plainPassword);
        
        $instance = new self();
        $instance->hashedValue = Hash::make($plainPassword);
        
        return $instance;
    }

    /**
     * Create from already hashed password (from database)
     */
    public static function fromHash(string $hashedPassword): self
    {
        $instance = new self();
        $instance->hashedValue = $hashedPassword;
        
        return $instance;
    }

    /**
     * Validate password meets requirements
     */
    private static function validate(string $password): void
    {
        if (strlen($password) < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                "Mật khẩu phải có ít nhất " . self::MIN_LENGTH . " ký tự"
            );
        }
    }

    /**
     * Verify a plain password against this hashed password
     */
    public function verify(string $plainPassword): bool
    {
        return Hash::check($plainPassword, $this->hashedValue);
    }

    /**
     * Get the hashed value (for storage)
     */
    public function hash(): string
    {
        return $this->hashedValue;
    }

    /**
     * Private constructor - use static factory methods
     */
    private function __construct()
    {
    }
}

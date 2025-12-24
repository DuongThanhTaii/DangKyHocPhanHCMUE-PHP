<?php

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use App\Domain\Auth\ValueObjects\Email;
use App\Domain\Auth\ValueObjects\Username;
use InvalidArgumentException;

/**
 * Unit Tests for Auth Value Objects
 */
class ValueObjectsTest extends TestCase
{
    // ==================== EMAIL VALUE OBJECT ====================

    public function test_email_accepts_valid_email(): void
    {
        $email = new Email('test@example.com');
        $this->assertEquals('test@example.com', $email->value());
    }

    public function test_email_normalizes_to_lowercase(): void
    {
        $email = new Email('TEST@EXAMPLE.COM');
        $this->assertEquals('test@example.com', $email->value());
    }

    public function test_email_trims_whitespace(): void
    {
        $email = new Email('  test@example.com  ');
        $this->assertEquals('test@example.com', $email->value());
    }

    public function test_email_rejects_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Email('not-an-email');
    }

    public function test_email_rejects_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Email('');
    }

    public function test_email_equals_comparison(): void
    {
        $email1 = new Email('test@example.com');
        $email2 = new Email('TEST@example.com');
        $email3 = new Email('other@example.com');

        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    // ==================== USERNAME VALUE OBJECT ====================

    public function test_username_accepts_valid_username(): void
    {
        $username = new Username('johndoe');
        $this->assertEquals('johndoe', $username->value());
    }

    public function test_username_rejects_too_short(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Username('ab'); // < 3 chars
    }

    public function test_username_rejects_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Username(str_repeat('a', 51)); // > 50 chars
    }

    public function test_username_trims_whitespace(): void
    {
        $username = new Username('  johndoe  ');
        $this->assertEquals('johndoe', $username->value());
    }

    public function test_username_accepts_minimum_length(): void
    {
        $username = new Username('abc'); // exactly 3 chars
        $this->assertEquals('abc', $username->value());
    }

    public function test_username_accepts_maximum_length(): void
    {
        $maxUsername = str_repeat('a', 50);
        $username = new Username($maxUsername);
        $this->assertEquals($maxUsername, $username->value());
    }

    public function test_username_equals_comparison(): void
    {
        $u1 = new Username('johndoe');
        $u2 = new Username('johndoe');
        $u3 = new Username('janedoe');

        $this->assertTrue($u1->equals($u2));
        $this->assertFalse($u1->equals($u3));
    }
}

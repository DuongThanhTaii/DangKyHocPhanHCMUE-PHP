<?php

namespace App\Domain\Auth\Services;

class DjangoPasswordService
{
    /**
     * Check if the plain password matches the Django PBKDF2 hash.
     * Format: pbkdf2_sha256$iterations$salt$hash
     */
    public function check(string $password, string $djangoHash): bool
    {
        $parts = explode('$', $djangoHash);

        if (count($parts) !== 4) {
            return false;
        }

        [$algorithm, $iterations, $salt, $hash] = $parts;

        if ($algorithm !== 'pbkdf2_sha256') {
            return false;
        }

        $calcHash = hash_pbkdf2('sha256', $password, $salt, (int) $iterations, 32, true);
        $encodedHash = base64_encode($calcHash);

        return hash_equals($hash, $encodedHash);
    }
}

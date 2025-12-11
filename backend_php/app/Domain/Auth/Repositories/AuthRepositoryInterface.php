<?php

namespace App\Domain\Auth\Repositories;

use App\Domain\Auth\Entities\UserEntity;

interface AuthRepositoryInterface
{
    /**
     * Find account by username
     *
     * @param string $username
     * @return object|null Returns an object with password and status, or null
     */
    public function findAccountByUsername(string $username): ?object;

    /**
     * Get User Entity by Account ID
     *
     * @param string|int $accountId
     * @return UserEntity|null
     */
    public function getUserByAccountId(string|int $accountId): ?UserEntity;

    /**
     * Find account by email (via UserProfile)
     *
     * @param string $email
     * @return object|null (TaiKhoan model)
     */
    public function findAccountByEmail(string $email): ?object;

    /**
     * Update password for account
     *
     * @param string|int $accountId
     * @param string $hashedPassword
     * @return void
     */
    public function updatePassword(string|int $accountId, string $hashedPassword): void;

    /**
     * Validate password for account
     */
    public function validatePassword(string|int $accountId, string $plainPassword): bool;
}

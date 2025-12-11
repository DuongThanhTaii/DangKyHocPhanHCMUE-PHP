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
}

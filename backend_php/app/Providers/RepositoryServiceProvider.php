<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Auth\Repositories\AuthRepositoryInterface;
use App\Infrastructure\Auth\Persistence\Repositories\EloquentAuthRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AuthRepositoryInterface::class,
            EloquentAuthRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}

<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'pdt' => \App\Http\Middleware\EnsureUserIsPdt::class,
            'sinh_vien' => \App\Http\Middleware\EnsureUserIsSinhVien::class,
            'giang_vien' => \App\Http\Middleware\EnsureUserIsGiangVien::class,
            'tro_ly_khoa' => \App\Http\Middleware\EnsureUserIsTroLyKhoa::class,
            'truong_khoa' => \App\Http\Middleware\EnsureUserIsTruongKhoa::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

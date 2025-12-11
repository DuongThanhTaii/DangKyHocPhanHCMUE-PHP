<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make sure you match
| the Django URLs 1-1.
|
*/

Route::prefix('auth')->group(function () {
    // POST /api/auth/login
    Route::post('login', [AuthController::class, 'login']);

    // TODO: Implement other auth routes
    // Route::post('refresh', [AuthController::class, 'refresh']);
    // Route::get('me', [AuthController::class, 'me']);
});

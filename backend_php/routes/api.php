<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;

// Endpoint: POST /api/auth/login
Route::post('/auth/login', [AuthController::class, 'login']);

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('change-password', [AuthController::class, 'changePassword']);
});

Route::group(['prefix' => 'auth'], function () {
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

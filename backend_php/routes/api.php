<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;

// Endpoint: POST /api/auth/login
Route::post('/auth/login', [AuthController::class, 'login']);

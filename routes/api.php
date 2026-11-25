<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    try { DB::select('SELECT 1'); }
    catch (\Throwable $e) { return response()->json(['success' => false, 'message' => 'Database belum terhubung.'], 503); }
    return response()->json(['success' => true, 'message' => 'Laravel dan PostgreSQL terhubung.', 'data' => ['status' => 'ok']]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/users/me', [UserController::class, 'me']);
    Route::put('/users/profile', [UserController::class, 'updateProfile']);
    Route::put('/users/email', [UserController::class, 'updateEmail']);
    Route::post('/users/avatar', [UserController::class, 'uploadAvatar']);
    Route::post('/users/onboarding', [UserController::class, 'onboarding']);
    Route::apiResource('/courses', CourseController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::apiResource('/tasks', TaskController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::get('/dashboard/summary', [AnalyticsController::class, 'dashboard']);
    Route::get('/analytics', [AnalyticsController::class, 'index']);
    Route::post('/analytics/focus-sessions', [AnalyticsController::class, 'storeFocus']);
});

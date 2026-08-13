<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Middleware\Api\V1\GuestMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {

    Route::middleware([GuestMiddleware::class, 'throttle:login'])->name('auth.')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::withoutMiddleware(GuestMiddleware::class)->middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('tasks')->controller(TaskController::class)->name('tasks.')->group(function () {
            Route::get('trashed', 'trashed')->name('trashed');
            Route::post('{task}/restore', 'restore')->withTrashed()->name('restore');
        });
        Route::apiResource('tasks', TaskController::class);
        Route::prefix('projects')->controller(ProjectController::class)->name('projects.')->group(function () {
            Route::get('trashed', 'trashed')->name('trashed');
            Route::post('{project}/restore', 'restore')->withTrashed()->name('restore');
        });
        Route::apiResource('projects', ProjectController::class);
    });
});

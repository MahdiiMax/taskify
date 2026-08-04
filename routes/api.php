<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Middleware\Api\V1\GuestMiddleware;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix("v1")->group(function (){

    Route::middleware(GuestMiddleware::class)->group(function (){
    Route::post('/register',[AuthController::class, "register"]);
    Route::post('/login',[AuthController::class, "login"]);
    });

    Route::middleware("auth:sanctum")->group(function (){
        Route::post('/logout',[AuthController::class, "logout"]);
        Route::prefix("tasks")->controller(TaskController::class)->name("tasks.")->group(function (){
            Route::get("trashed","trashed")->name("trashed");
            Route::post("{task}/restore","restore")->withTrashed()->name("restore");
        });
        Route::apiResource("tasks",TaskController::class);
    });
});
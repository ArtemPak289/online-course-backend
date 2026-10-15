<?php

use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Публичные маршруты аутентификации
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Публичные маршруты категорий
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

// Публичные маршруты курсов и уроков (с поддержкой опциональной аутентификации)
Route::middleware('auth.api:optional')->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::get('/courses/{course}/lessons', [LessonController::class, 'index']);
    Route::get('/lessons/{lesson}', [LessonController::class, 'show']);
    Route::get('/lessons/{lesson}/attachment', [LessonController::class, 'downloadAttachment']);
});

// Маршруты, требующие аутентификации
Route::middleware('auth.api')->group(function () {
    // Сессия / Выход
    Route::post('/logout', [AuthController::class, 'logout']);

    // Профиль пользователя
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'changePassword']);

    // Категории (создание, изменение, удаление)
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Курсы (создание, изменение, удаление)
    Route::post('/courses', [CourseController::class, 'store']);
    Route::put('/courses/{course}', [CourseController::class, 'update']);
    Route::delete('/courses/{course}', [CourseController::class, 'destroy']);

    // Уроки (создание, изменение, удаление)
    Route::post('/courses/{course}/lessons', [LessonController::class, 'store']);
    Route::match(['put', 'patch'], '/lessons/{lesson}', [LessonController::class, 'update']);
    Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy']);

    // Управление пользователями (Администратор)
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole']);
        Route::patch('/users/{user}/block', [AdminUserController::class, 'updateBlockStatus']);
    });
});

<?php

use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PurchaseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 1. Публичные маршруты аутентификации и внешних платёжных webhook
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/payments/webhook', [PurchaseController::class, 'webhook']);

// 2. Публичные маршруты для просмотра каталога (с поддержкой опциональной аутентификации для авторов/админов)
Route::middleware('auth.api:optional')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::get('/courses/{course}/lessons', [LessonController::class, 'index']);
    Route::get('/lessons/{lesson}', [LessonController::class, 'show']);
    Route::get('/lessons/{lesson}/download', [LessonController::class, 'download']);
});

// 3. Защищённые маршруты, требующие валидного API-токена
Route::middleware('auth.api')->group(function () {
    // Сессия и профиль
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'changePassword']);

    // Категории (создание, изменение, удаление — только администратор)
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::match(['put', 'patch'], '/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Курсы (создание, изменение, удаление — автор или администратор)
    Route::post('/courses', [CourseController::class, 'store']);
    Route::match(['put', 'patch'], '/courses/{course}', [CourseController::class, 'update']);
    Route::delete('/courses/{course}', [CourseController::class, 'destroy']);

    // Уроки (создание, изменение, удаление — автор курса или администратор)
    Route::post('/courses/{course}/lessons', [LessonController::class, 'store']);
    Route::match(['put', 'patch'], '/lessons/{lesson}', [LessonController::class, 'update']);
    Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy']);

    // Покупка курса, прогресс обучения и сертификаты
    Route::post('/courses/{course}/purchase', [PurchaseController::class, 'store']);
    Route::get('/purchases', [PurchaseController::class, 'index']);
    Route::post('/lessons/{lesson}/complete', [PurchaseController::class, 'completeLesson']);
    Route::get('/courses/{course}/progress', [PurchaseController::class, 'progress']);
    Route::get('/courses/{course}/certificate', [PurchaseController::class, 'certificate']);

    // Управление пользователями (только администратор)
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole']);
        Route::patch('/users/{user}/block', [AdminUserController::class, 'updateBlockStatus']);
    });
});

<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\MyRoutineController;
use App\Http\Controllers\RoutineController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC ROUTES
// ============================================

// Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Categories, Exercises, Routines (solo lectura)
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('exercises', ExerciseController::class)->only(['index', 'show']);
Route::apiResource('routines', RoutineController::class)->only(['index', 'show']);

// Relaciones públicas
Route::get('/categories/{category}/exercises', [CategoryController::class, 'exercises']);
Route::get('/routines/{routine}/exercises', [RoutineController::class, 'exercises']);

// ============================================
// PROTECTED ROUTES (require token)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    // User
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Categories, Exercises, Routines (escritura)
    Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('exercises', ExerciseController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('routines', RoutineController::class)->only(['store', 'update', 'destroy']);

    // Routines - gestión de ejercicios
    Route::post('/routines/{routine}/exercises', [RoutineController::class, 'addExercise']);
    Route::delete('/routines/{routine}/exercises/{exercise}', [RoutineController::class, 'removeExercise']);

    // My Routines (suscripciones del usuario)
    Route::apiResource('my-routines', MyRoutineController::class)->only(['index', 'store', 'destroy'])->parameters(['my-routines' => 'routine']);
});



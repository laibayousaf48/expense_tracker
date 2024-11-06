<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ExpenseController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware(['auth:sanctum'])->post('/logout', [AuthController::class, 'logout']);

// Password reset routes
Route::post('/password/reset', [AuthController::class, 'resetPassword']);
Route::post('/password/reset/confirm', [AuthController::class, 'confirmReset']);

// Profile update route
Route::middleware(['auth:sanctum'])->post('/profile/update', [AuthController::class, 'updateProfile']);



Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::get('/expenses/{id}', [ExpenseController::class, 'show']);
    Route::post('/expenses/{id}', [ExpenseController::class, 'update']);
    Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy']);
    Route::get('/analytics', [ExpenseController::class, 'analytics']);
});

Route::middleware('auth:sanctum')->post('/budgets', [BudgetController::class, 'store']);
Route::middleware('auth:sanctum')->get('/budgets', [BudgetController::class, 'index']);
Route::middleware('auth:sanctum')->delete('/budgets', [BudgetController::class, 'destroy']);


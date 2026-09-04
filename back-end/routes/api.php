<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\OperationsController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/products', [ProductController::class, 'index']);

    Route::middleware('role:sales')->group(function (): void {
        Route::get('/opportunities', [OpportunityController::class, 'index']);
        Route::post('/opportunities', [OpportunityController::class, 'store']);
        Route::patch('/opportunities/{opportunity}', [OpportunityController::class, 'update']);
    });

    Route::middleware('role:operations')->group(function (): void {
        Route::post('/inventory-adjustments', [OperationsController::class, 'adjustInventory']);
        Route::post('/capacity-adjustments', [OperationsController::class, 'adjustCapacity']);
    });

    Route::middleware('role:finance')->group(function (): void {
        Route::post('/receipts', [FinanceController::class, 'storeReceipt']);
        Route::post('/payments', [FinanceController::class, 'storePayment']);
        Route::post('/expenses', [FinanceController::class, 'storeExpense']);
        Route::get('/cash-summary', [FinanceController::class, 'cashSummary']);
    });

    Route::middleware('role:admin')->group(function (): void {
        Route::get('/settings', [AdminController::class, 'settings']);
        Route::patch('/settings', [AdminController::class, 'updateSettings']);
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/admin/access-check', [AuthController::class, 'adminAccess']);
    });
});

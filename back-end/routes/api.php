<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

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

    Route::get('/admin/access-check', [AuthController::class, 'adminAccess'])
        ->middleware('role:admin');
});

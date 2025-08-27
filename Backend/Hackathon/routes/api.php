<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VanController;
use App\Http\Controllers\DistributorController;
use App\Http\Controllers\ManagerController;

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Van Rep Routes
    Route::prefix('van-rep')->group(function () {
        Route::get('/stock', [VanController::class, 'getVanStock']);       // van stock
        Route::get('/products', [VanController::class, 'getProducts']);    // all products
        Route::post('/requisitions', [VanController::class, 'createRequisition']); 
        Route::get('/requisitions', [VanController::class, 'getMyRequisitions']); 
        Route::get('/capacity', [VanController::class, 'getCapacityInfo']); 
    });

    // Distributor Routes
    Route::prefix('distributor')->group(function () {
        Route::get('/stock', [DistributorController::class, 'getStock']);
        Route::get('/pending-requisitions', [DistributorController::class, 'getPendingRequisitions']);
        Route::put('/requisitions/{id}/approve', [DistributorController::class, 'approveRequisition']);
        Route::put('/requisitions/{id}/reject', [DistributorController::class, 'rejectRequisition']);
    });

    // Manager Routes
    Route::prefix('manager')->group(function () {
        Route::get('/dashboard/stock-overview', [ManagerController::class, 'stockOverview']);
        Route::get('/dashboard/pending-requisitions', [ManagerController::class, 'pendingRequisitions']);
        Route::get('/stock-movements', [ManagerController::class, 'stockMovements']);
    });
});

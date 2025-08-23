<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VanController;

/*Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Van Sales Rep Routes
    Route::prefix('van')->group(function () {
        
        // Get van stock for authenticated van rep
        Route::get('/stock', [VanController::class, 'getVanStock']);
        
        // Get all products available for requisition
        Route::get('/products', [VanController::class, 'getProducts']);
        
        // Create new requisition request
        Route::post('/requisitions', [VanController::class, 'createRequisition']);
        
        // Get requisitions for authenticated van rep
        Route::get('/requisitions', [VanController::class, 'getMyRequisitions']);
        
        // Get available distributors
        Route::get('/distributors', [VanController::class, 'getDistributors']);
        
            // Get van capacity information
            Route::get('/capacity', [VanController::class, 'getCapacityInfo']);
        });
    });

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckAdminRole;

// Debug route to verify API is working
Route::get('test', function() {
    return response()->json(['message' => 'API is working']);
});

// Public routes
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'getUser']);
    
    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    
    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    
    // Orders
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    
    // User Profile - keep only these routes
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    
    // Admin Product Management Routes
    Route::middleware(['auth:sanctum', CheckAdminRole::class])->prefix('admin')->group(function () {
        Route::get('/categories', [ProductController::class, 'categories']);
        Route::post('/categories', [ProductController::class, 'storeCategory']);
        Route::post('/users', [UserController::class, 'createAdmin']);
    });
});

// Add this route for debugging
Route::get('/test-route', function() {
    return response()->json(['message' => 'API is working']);
});

// Debug route - test if API is accessible
Route::get('/ping', function() {
    return response()->json(['message' => 'pong']);
});

// User routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Profile routes
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
});
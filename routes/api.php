<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ActivityCategoryController;
use App\Http\Controllers\ActivityTagController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CountryLocationDetailController;
use App\Http\Controllers\CountryTravelInfoController;
use App\Http\Controllers\CountryEventController;
use App\Http\Controllers\CountrySeasonController;
use App\Http\Controllers\CountryAdditionalInfoController;
use App\Http\Controllers\CountryFaqController;
use App\Http\Controllers\CountrySeoController;

// for future use Public product routes
// Route::prefix('products')->group(function () {
//     Route::get('/', [ProductController::class, 'index']); // Public
//     Route::get('{id}', [ProductController::class, 'show']); // Public
// });

// // for future use Admin-only product routes
// Route::middleware(['auth:api', 'admin'])->prefix('products')->group(function () {
//     Route::post('/', [ProductController::class, 'store']);
//     Route::put('{id}', [ProductController::class, 'update']);
//     Route::delete('{id}', [ProductController::class, 'destroy']);
// });

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // Route::get('/getuserdetails', [AuthController::class, 'getUserDetails']);
    // Route::get('/user', [UserController::class, 'getUser']);
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile', [UserProfileController::class, 'update']);
});

Route::middleware(['auth:api', 'admin'])->group(function () {
    // Admin Side Users Routes
    Route::post('/users/create', [UserController::class, 'createUser']);
    Route::get('/users', [UserController::class, 'getAllUsers']); 

    // Admin Side Acitivty Category Routes
    Route::apiResource('activity-categories', ActivityCategoryController::class);
    // Admin Side Acitivty Tag Routes
    Route::apiResource('activity-tags', ActivityTagController::class);
    // Admin Side Acitivty Attribute Routes
    Route::apiResource('activity-attributes', ActivityAttributeController::class);
    // Admin Side Destination Countriwa Routes
    Route::apiResource('countries', CountryController::class);
    // Admin Side Destination Countries Location & Details Routes
    Route::prefix('countries/{id}/country-location-details')->group(function () {
        Route::post('/', [CountryLocationDetailController::class, 'store']); 
        Route::get('/', [CountryLocationDetailController::class, 'show']); 
        Route::put('/', [CountryLocationDetailController::class, 'update']);
        Route::delete('/', [CountryLocationDetailController::class, 'destroy']);
    });
    // Admin Side Destination Countries Travel Info Routes
    Route::prefix('countries/{id}/country-travel-info')->group(function () {
        Route::post('/', [CountryTravelInfoController::class, 'store']); 
        Route::get('/', [CountryTravelInfoController::class, 'show']); 
        Route::put('/', [CountryTravelInfoController::class, 'update']);
        Route::delete('/', [CountryTravelInfoController::class, 'destroy']);
    });
    // Admin Side Destination Countries Season and Event Routes
    Route::prefix('countries/{id}')->group(function () {
        Route::apiResource('country-seasons', CountrySeasonController::class);
        Route::apiResource('country-events', CountryEventController::class);
    });
    // Admin Side Destination Countries additional info Routes
    Route::prefix('countries/{id}')->group(function () {
        Route::apiResource('country-additional-info', CountryAdditionalInfoController::class);
    });
    // Admin Side Destination Countries faq Routes
    Route::prefix('countries/{id}')->group(function () {
        Route::apiResource('country-faqs', CountryFaqController::class);
    });
    // Admin Side Destination Countries SEO data Routes
    Route::prefix('countries/{id}')->group(function () {
        Route::get('country-seo', [CountrySeoController::class, 'show']);
        Route::post('country-seo', [CountrySeoController::class, 'store']);
    });

    // Product Routes old Approach
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('{id}', [ProductController::class, 'show']);
        Route::put('{id}', [ProductController::class, 'update']);
        Route::delete('{id}', [ProductController::class, 'destroy']);
    });
});


<?php

// Admin
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\StripePaymentController;

use App\Http\Controllers\Admin\UserController;
// use App\Http\Controllers\Admin\UserProfileController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\CountryImportController;
use App\Http\Controllers\Admin\StateImportController;
use App\Http\Controllers\Admin\CityImportController;
use App\Http\Controllers\Admin\PlaceImportController;

use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\TransferController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ItineraryController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\BlogController;


// Public
use App\Http\Controllers\Public\PublicRegionController;
use App\Http\Controllers\Public\PublicCountryController;
use App\Http\Controllers\Public\PublicStateController;
use App\Http\Controllers\Public\PublicCitiesController;
use App\Http\Controllers\Public\PublicPlaceController;
use App\Http\Controllers\Public\PublicActivityController;
use App\Http\Controllers\Public\PublicItineraryController;
use App\Http\Controllers\Public\PublicPackageController;
use App\Http\Controllers\Public\PublicTransferController;
use App\Http\Controllers\Public\PublicHomeSearchController;
use App\Http\Controllers\Public\PublicShopController;
use App\Http\Controllers\Public\PublicCategoryController;
use App\Http\Controllers\Public\PublicTagController;
use App\Http\Controllers\Public\PublicFilterController;

Route::get('/test', function () {
    return response()->json(['message' => 'Route Working!']);
});

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

// Stripe Payment api
// Route::post('/create-payment-intent', [StripePaymentController::class, 'createPaymentIntent']);
Route::post('/create-payment-intent', [StripePaymentController::class, 'bookAndCreatePaymentIntent']);
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {

    // Admin Side Users Routes
    Route::post('/users/create', [UserController::class, 'createUser']);
    Route::get('/users', [UserController::class, 'getAllUsers']); 

    // Admin Side Category Routes
    Route::apiResource('/categories', CategoryController::class);

    // Admin Side Acitivty Tag Routes
    Route::apiResource('/tags', TagController::class);

    Route::prefix('attributes')->group(function () {
        Route::get('/slug/{slug}', [AttributeController::class, 'getValuesBySlug']);
        Route::get('/{id}', [AttributeController::class, 'show']);
        Route::get('/', [AttributeController::class, 'index']);
        Route::post('/', [AttributeController::class, 'store']);
        Route::put('/{id}', [AttributeController::class, 'update']);
        Route::delete('/{id}', [AttributeController::class, 'destroy']);
    });


    // Admin Side Destination Countries Routes
    Route::apiResource('/countries', CountryController::class);


    Route::post('/import-countries', [CountryImportController::class, 'import']);
    Route::post('/import-states', [StateImportController::class, 'import']);
    Route::post('/import-cities', [CityImportController::class, 'import']);
    Route::post('/import-places', [PlaceImportController::class, 'import']);

    Route::prefix('/cities')->group(function () {
        Route::get('/', [CityController::class, 'index']);
        Route::get('/{id}', [CityController::class, 'show']);
        Route::post('/{id?}', [CityController::class, 'store']);
        Route::delete('/{id}', [CityController::class, 'destroy']);
    });

    // Admin Side media route
    Route::prefix('media')->group(function () {
        Route::get('/', [MediaController::class, 'index']);
        Route::get('/{id}', [MediaController::class, 'show']);
        Route::post('/store', [MediaController::class, 'store']);
        Route::put('/update/{id}', [MediaController::class, 'update']);
        Route::delete('/delete/{id}', [MediaController::class, 'destroy']);
    });

    // Admin Side vendors route
    Route::prefix('/vendors')->group(function () {
        // Route::post('/', [VendorController::class, 'store']); // Create
        // Route::put('/{id}', [VendorController::class, 'update']); // Update
        // Route::patch('/{id}', [VendorController::class, 'update']); // Partial Update
        Route::get('/', [VendorController::class, 'index']);      // List vendors
        Route::get('/{id}', [VendorController::class, 'show']); // Show a vendor
        Route::delete('/{id}', [VendorController::class, 'destroy']);  // Delete vendor
        Route::post('/store/{request_type}', [VendorController::class, 'store']);
        Route::put('/update/{request_type}/{id}', [VendorController::class, 'update']);
        // Route::delete('/relation/{request-type}/{id}', [VendorController::class, 'destroy']);
    });

    // Admin Side Transfer route
    Route::prefix('/transfers')->group(function () {
        Route::post('/', [TransferController::class, 'save']); // Create
        Route::put('/{id}', [TransferController::class, 'save']); // Update
        Route::patch('/{id}', [TransferController::class, 'save']); // Partial Update
        Route::get('/', [TransferController::class, 'index']);
        Route::get('/{id}', [TransferController::class, 'show']);
        Route::delete('/{id}', [TransferController::class, 'destroy']);
    });

    // Admin Side activity route
    // Route::apiResource('activities', ActivityController::class);
    Route::prefix('/activities')->group(function () {
        Route::post('/', [ActivityController::class, 'store']); // Create
        Route::put('/{id}', [ActivityController::class, 'update']); // Update
        Route::patch('/{id}', [ActivityController::class, 'update']); // Partial Update
        Route::get('/', [ActivityController::class, 'index']); // Get all
        Route::get('/{id}', [ActivityController::class, 'show']); // Get single
        Route::delete('/{id}', [ActivityController::class, 'destroy']); // Delete
        Route::delete('/{id}/partial-delete', [ActivityController::class, 'partialDelete']); //partialDelete
        Route::post('/bulk-delete', [ActivityController::class, 'bulkDestroy']);
    });

    // Admin Side Itinerary route
    // Route::apiResource('itineraries', ItineraryController::class);
    Route::prefix('/itineraries')->group(function () {
        Route::post('/', [ItineraryController::class, 'store']); // Create
        Route::put('/{id}', [ItineraryController::class, 'update']); // Update
        Route::patch('/{id}', [ItineraryController::class, 'update']); // Partial Update
        Route::get('/', [ItineraryController::class, 'index']); // Get all
        Route::get('/{id}', [ItineraryController::class, 'show']); // Get single
        Route::delete('/{id}', [ItineraryController::class, 'destroy']); // Delete
        Route::delete('/{id}/partial-delete', [ItineraryController::class, 'partialDelete']); //partialDelete
        Route::post('/bulk-delete', [ItineraryController::class, 'bulkDestroy']);
    });

    // Admin Side Package route
    // Route::apiResource('packages', PackageController::class);
    Route::prefix('/packages')->group(function () {
        Route::post('/', [PackageController::class, 'store']); // Create
        Route::put('/{id}', [PackageController::class, 'update']); // Update
        Route::patch('/{id}', [PackageController::class, 'update']); // Partial Update
        Route::get('/', [PackageController::class, 'index']); // Get all
        Route::get('/{id}', [PackageController::class, 'show']); // Get single
        Route::delete('/{id}', [PackageController::class, 'destroy']); // Delete
        Route::delete('/{id}/partial-delete', [PackageController::class, 'partialDelete']); //partialDelete
        Route::post('/bulk-delete', [PackageController::class, 'bulkDestroy']);
    });

    // Admin Side Order Create Update Delete route
    Route::prefix('orders')->group(function () {
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::delete('/{id}', [OrderController::class, 'destroy']);
    });

    Route::prefix('blogs')->group(function () {
        Route::get('/', [BlogController::class, 'index']); // List all blogs
        Route::get('{id}', [BlogController::class, 'show']); // Show a single blog
        Route::post('/', [BlogController::class, 'store']); // Store a new blog
        Route::put('{id}', [BlogController::class, 'update']); // Update an existing blog
        Route::delete('{id}', [BlogController::class, 'destroy']); // Delete a blog
    });

});

// *****************************************************************************************************************
// Public-------------------------API___________________Public API_________________Public -----------------------API

// *****************************************************************************************************************

Route::get('/categories', [PublicCategoryController::class, 'getAllCategories']);
Route::get('/tags', [PublicTagController::class, 'getAllTags']);

Route::prefix('region')->group(function () {
    Route::get('/{slug}', [PublicRegionController::class, 'getRegionDetails']);
    Route::get('/{region_slug}/cities', [PublicRegionController::class, 'getCitiesByRegion']);
    Route::get('/{region_slug}/region-packages', [PublicRegionController::class, 'getPackagesByRegion']);

    Route::get('/{region_slug}/region-all-items', [PublicRegionController::class, 'getAllItemsByRegion']);
});

// get all featured Cities for home page
Route::get('/featured-cities', [PublicCitiesController::class, 'getFeaturedCities']);

// get single city page by slug
Route::get('/city/{slug}', [PublicCitiesController::class, 'getCityDetails']);

// getting activity beahlf of city
Route::get('/{city_slug}/activities', [PublicRegionController::class, 'getActivityByCity']);

// getting itinerary beahlf of city
Route::get('/{city_slug}/itineraries/', [PublicRegionController::class, 'getItinerariesByCity']);

// getting package beahlf of city

Route::get('/{city_slug}/packages/', [PublicRegionController::class, 'getPackagesByCity']);

// getting all items beahlf of city
Route::get('/{city_slug}/all-items/', [PublicRegionController::class, 'getAllItemsByCity']);

// activity api
Route::prefix('activities')->group(function () {
    Route::get('/', [PublicActivityController::class, 'getActivities']);
    Route::get('/featured-activities', [PublicActivityController::class, 'getFeaturedActivities']);
    Route::get('/{activity_slug}', [PublicActivityController::class, 'getActivityBySlug']);
});

// transfer api
Route::prefix('transfers')->group(function () {
    Route::get('/', [PublicTransferController::class, 'index']);
    Route::get('/{id}', [PublicTransferController::class, 'show']);
});

// itineraries api
Route::prefix('itineraries')->group(function () {
    Route::get('/', [PublicItineraryController::class, 'index']);
    Route::get('/featured-itineraries', [PublicItineraryController::class, 'getFeaturedItineraries']);
    Route::get('/{slug}', [PublicItineraryController::class, 'show']);
});

// Packages api
Route::prefix('packages')->group(function () {
    Route::get('/', [PublicPackageController::class, 'index']);
    Route::get('/featured-packages', [PublicPackageController::class, 'getFeaturedPackages']);
    Route::get('/{slug}', [PublicPackageController::class, 'show']);
});

// Search API
Route::get('/regions-cities', [PublicHomeSearchController::class, 'getRegionsAndCities']);
Route::get('/homesearch', [PublicHomeSearchController::class, 'homeSearch']);

// Filter API
Route::get('/filter', [PublicFilterController::class, 'filter']);

// Shop Page all items API
Route::get('/shop', [PublicShopController::class, 'index']);

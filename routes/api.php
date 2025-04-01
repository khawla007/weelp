<?php

// Admin
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserProfileController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\CountryLocationDetailController;
use App\Http\Controllers\Admin\CountryTravelInfoController;
use App\Http\Controllers\Admin\CountryEventController;
use App\Http\Controllers\Admin\CountrySeasonController;
use App\Http\Controllers\Admin\CountryAdditionalInfoController;
use App\Http\Controllers\Admin\CountryFaqController;
use App\Http\Controllers\Admin\CountrySeoController;
use App\Http\Controllers\Admin\CountryImportController;
use App\Http\Controllers\Admin\StateImportController;
use App\Http\Controllers\Admin\CityImportController;
use App\Http\Controllers\Admin\PlaceImportController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ItineraryController;
use App\Http\Controllers\Admin\CityController;


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

Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {

    // Admin Side Users Routes
    Route::post('/users/create', [UserController::class, 'createUser']);
    Route::get('/users', [UserController::class, 'getAllUsers']); 

    // Admin Side Category Routes
    Route::apiResource('/categories', CategoryController::class);

    // Admin Side Acitivty Tag Routes
    Route::apiResource('/tags', TagController::class);

    // Admin Side Acitivty Attribute Routes
    Route::apiResource('/attributes', AttributeController::class);

    // Admin Side Destination Countries Routes
    Route::apiResource('/countries', CountryController::class);

    // Admin Side Destination Countries Location & Details Routes
    Route::prefix('/countries/{id}/country-location-details')->group(function () {
        Route::post('/', [CountryLocationDetailController::class, 'store']); 
        Route::get('/', [CountryLocationDetailController::class, 'show']); 
        Route::put('/', [CountryLocationDetailController::class, 'update']);
        Route::delete('/', [CountryLocationDetailController::class, 'destroy']);
    });
    // Admin Side Destination Countries Travel Info Routes
    Route::prefix('/countries/{id}/country-travel-info')->group(function () {
        Route::post('/', [CountryTravelInfoController::class, 'store']); 
        Route::get('/', [CountryTravelInfoController::class, 'show']); 
        Route::put('/', [CountryTravelInfoController::class, 'update']);
        Route::delete('/', [CountryTravelInfoController::class, 'destroy']);
    });
    // Admin Side Destination Countries Season and Event Routes
    Route::prefix('/countries/{id}')->group(function () {
        Route::apiResource('country-seasons', CountrySeasonController::class);
        Route::apiResource('country-events', CountryEventController::class);
    });
    // Admin Side Destination Countries additional info Routes
    Route::prefix('/countries/{id}')->group(function () {
        Route::apiResource('country-additional-info', CountryAdditionalInfoController::class);
    });
    // Admin Side Destination Countries faq Routes
    Route::prefix('/countries/{id}')->group(function () {
        Route::apiResource('country-faqs', CountryFaqController::class);
    });
    // Admin Side Destination Countries SEO data Routes
    Route::prefix('/countries/{id}')->group(function () {
        Route::get('country-seo', [CountrySeoController::class, 'show']);
        Route::post('country-seo', [CountrySeoController::class, 'store']);
    });

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

    // Admin Side activity route
    // Route::apiResource('activities', ActivityController::class);
    Route::prefix('/activity')->group(function () {
        Route::post('/', [ActivityController::class, 'save']); // Create
        Route::put('/{id}', [ActivityController::class, 'save']); // Update
        Route::patch('/{id}', [ActivityController::class, 'save']); // Partial Update
        Route::get('/', [ActivityController::class, 'index']); // Get all
        Route::get('/{id}', [ActivityController::class, 'show']); // Get single
        Route::delete('/{id}', [ActivityController::class, 'destroy']); // Delete
    });

    // Admin Side Itinerary route
    // Route::apiResource('itineraries', ItineraryController::class);
    Route::prefix('/itinerary')->group(function () {
        Route::post('/', [ItineraryController::class, 'save']); // Create
        Route::put('/{id}', [ItineraryController::class, 'save']); // Update
        Route::patch('/{id}', [ItineraryController::class, 'save']); // Partial Update
        Route::get('/', [ItineraryController::class, 'index']); // Get all
        Route::get('/{id}', [ItineraryController::class, 'show']); // Get single
        Route::delete('/{id}', [ItineraryController::class, 'destroy']); // Delete
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
    
    // Route::get('/{region_slug}/{city_slug}', [PublicRegionController::class, 'getPlacesByCity']);
    // Route::get('/{region_slug}/{city_slug}/activities', [PublicRegionController::class, 'getActivityByCity']);
    // Route::get('/{region_slug}/{city_slug}/itineraries/', [PublicRegionController::class, 'getItinerariesByCity']);
    // Route::get('/{region_slug}/{city_slug}/packages/', [PublicRegionController::class, 'getPackagesByCity']);

    // Route::get('/{region_slug}/country-{country_slug}', [PublicRegionController::class, 'getStatesByCountry']);
    // Route::get('/{region_slug}/country-{country_slug}/{state_slug}', [PublicRegionController::class, 'getCitiesByState']);
    // Route::get('/{region_slug}/{country_slug}/{state_slug}/{city_slug}', [PublicRegionController::class, 'getPlacesInCity']);
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

// Route::get('/countries', [PublicCountryController::class, 'getCountries']);
// Route::prefix('countries')->group(function () {
    // Route::get('/{country_slug}', [PublicStateController::class, 'getStatesByCountry']);
    // Route::get('/{country_slug}/{state_slug}', [PublicCitiesController::class, 'getCitiesByState']);
    // Route::get('/{country_slug}/{state_slug}/{city_slug}', [PublicPlaceController::class, 'getPlacesByCity']);
// });

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

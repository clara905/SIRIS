<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\AssetController;
use App\Http\Controllers\Api\Admin\BidangController;
use App\Http\Controllers\Api\Admin\SatkerController;
use App\Http\Controllers\Api\Admin\SubBidangController;
use App\Http\Controllers\Api\Admin\ThreatController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\VulnerabilityController;
use App\Http\Controllers\Api\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'SIRIS API is running',
        'application' => 'SIRIS',
    ]);
});

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin')
        ->middleware('role:admin')
        ->group(function () {

            Route::get('/dashboard', [DashboardController::class, 'index']);

            Route::get('/test', function () {
                return response()->json([
                    'success' => true,
                    'message' => 'Admin endpoint berhasil diakses.',
                ]);
            });

            Route::apiResource('users', UserController::class);

            Route::apiResource('bidangs', BidangController::class);

            Route::apiResource('sub-bidangs', SubBidangController::class);

            Route::apiResource('satkers', SatkerController::class);

            Route::apiResource('assets', AssetController::class);

            Route::apiResource(
                'vulnerabilities',
                VulnerabilityController::class
            );

            Route::apiResource('threats', ThreatController::class);
        });
});
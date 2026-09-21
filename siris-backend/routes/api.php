<?php

use App\Http\Controllers\Api\Admin\AssetController;
use App\Http\Controllers\Api\Admin\BidangController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\RiskAssessmentController;
use App\Http\Controllers\Api\Admin\SatkerController;
use App\Http\Controllers\Api\Admin\SubBidangController;
use App\Http\Controllers\Api\Admin\ThreatController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\VulnerabilityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaptchaController;
use App\Http\Controllers\Api\Satker\PortalController;
use App\Http\Controllers\Api\SubmissionReviewController;
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

Route::get('/captcha', CaptchaController::class)->middleware('throttle:20,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

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
            Route::get('risk-assessments/rules', [RiskAssessmentController::class, 'rules']);
            Route::get('risk-assessments/threat-options', [RiskAssessmentController::class, 'threatOptions']);
            Route::apiResource('risk-assessments', RiskAssessmentController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::get('/test', function () {
                return response()->json([
                    'success' => true,
                    'message' => 'Admin endpoint berhasil diakses.',
                ]);
            });

            Route::get('users/roles', [UserController::class, 'roles']);
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

Route::middleware(['auth:sanctum', 'role:satker'])->prefix('satker')->controller(PortalController::class)->group(function () {
    Route::get('dashboard', 'dashboard');
    Route::get('sub-bidang', 'subBidang');
    Route::get('assets', 'assets');
    Route::post('assets', 'storeAsset');
    Route::get('assets/{id}', 'asset')->whereNumber('id');
    Route::put('assets/{id}', 'updateAsset')->whereNumber('id');
    Route::get('options', 'options');
    Route::post('threats', 'storeThreat');
    Route::get('pengajuan', 'submissions');
    Route::post('pengajuan', 'storeSubmission');
    Route::get('pengajuan/{id}', 'submission')->whereNumber('id');
    Route::put('pengajuan/{id}', 'updateSubmission')->whereNumber('id');
    Route::get('risk-assessments', 'assessments');
    Route::get('risk-assessments/{id}', 'assessment')->whereNumber('id');
    Route::get('notifications', 'notifications');
    Route::patch('notifications/read-all', 'readAll');
    Route::patch('notifications/{id}/read', 'readNotification')->whereNumber('id');
    Route::patch('treatments/{id}/progress', 'treatment')->whereNumber('id');
});
Route::middleware(['auth:sanctum', 'role:kasub'])->prefix('kasub')->controller(SubmissionReviewController::class)->group(function () {
    Route::get('pengajuan', 'index');
    Route::patch('pengajuan/{id}/review', 'review')->whereNumber('id');
    Route::post('pengajuan/{id}/assessment', 'assessment')->whereNumber('id');
    Route::post('pengajuan/{id}/treatment', 'treatment')->whereNumber('id');
});

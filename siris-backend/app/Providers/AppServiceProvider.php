<?php

namespace App\Providers;

use App\Models\RiskAssessment;
use App\Models\RiskTreatment;
use App\Policies\SatkerAssetPolicy;
use App\Services\SatkerPortal;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('satker-view-asset', [SatkerAssetPolicy::class, 'view']);
        Gate::define('satker-edit-asset', [SatkerAssetPolicy::class, 'update']);
        RiskAssessment::saved(function (RiskAssessment $risk): void {
            if ($risk->wasRecentlyCreated || $risk->wasChanged(['skor', 'level'])) {
                SatkerPortal::assessmentChanged($risk);
            }
        });
        RiskTreatment::saved(function (RiskTreatment $treatment): void {
            SatkerPortal::treatmentChanged($treatment);
        });
    }
}

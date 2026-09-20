<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Bidang;
use App\Models\RiskAssessment;
use App\Models\RiskTreatment;
use App\Models\Threat;
use App\Models\Vulnerability;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        $totalAssets = Asset::count();

        $totalThreats = Threat::where('is_active', true)->count();

        $totalVulnerabilities = Vulnerability::where('is_active', true)->count();

        $totalRisks = RiskAssessment::count();

        /*
        |--------------------------------------------------------------------------
        | RISK LEVEL
        |--------------------------------------------------------------------------
        */

        $riskByLevel = RiskAssessment::select(
            'level',
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('level')
            ->pluck('total', 'level');

        $riskLevels = [
            'rendah' => (int) ($riskByLevel['rendah'] ?? 0),
            'sedang' => (int) ($riskByLevel['sedang'] ?? 0),
            'tinggi' => (int) ($riskByLevel['tinggi'] ?? 0),
            'sangat_tinggi' => (int) ($riskByLevel['sangat_tinggi'] ?? 0),
            'ekstrem' => (int) ($riskByLevel['ekstrem'] ?? 0),
        ];

        /*
        |--------------------------------------------------------------------------
        | RISK TREATMENT STATUS
        |--------------------------------------------------------------------------
        */

        $treatmentByStatus = RiskTreatment::select(
            'status',
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('status')
            ->pluck('total', 'status');

        $treatmentStatus = [
            'belum' => (int) ($treatmentByStatus['belum'] ?? 0),
            'proses' => (int) ($treatmentByStatus['proses'] ?? 0),
            'selesai' => (int) ($treatmentByStatus['selesai'] ?? 0),
        ];

        /*
        |--------------------------------------------------------------------------
        | MONITORING BIDANG
        |--------------------------------------------------------------------------
        */

        $bidangMonitoring = Bidang::query()
            ->withCount('assets')
            ->withCount('users')
            ->withCount([
                'assets as total_risiko' => function ($query) {
                    $query->whereHas('riskAssessments');
                },
            ])
            ->get()
            ->map(function ($bidang) {
                return [
                    'id' => $bidang->id,
                    'nama_bidang' => $bidang->nama_bidang,
                    'total_asset' => $bidang->assets_count,
                    'total_user' => $bidang->users_count,
                    'total_risiko' => $bidang->total_risiko,
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | RECENT RISK ASSESSMENTS
        |--------------------------------------------------------------------------
        */

        $recentRisks = RiskAssessment::with([
            'asset',
            'threat:id,kode_ancaman,nama_ancaman',
            'dinilaiOleh:id,name',
            'treatments:id,risk_assessment_id,status',
        ])
            ->latest('tanggal_penilaian')
            ->limit(10)
            ->get()
            ->map(function ($risk) {
                return [
                    'id' => $risk->id,
                    'asset' => $risk->asset,
                    'threat' => $risk->threat,
                    'likelihood' => $risk->likelihood,
                    'impact' => $risk->impact,
                    'skor' => $risk->skor,
                    'level' => $risk->level,
                    'dinilai_oleh' => $risk->dinilaiOleh,
                    'tanggal_penilaian' => $risk->tanggal_penilaian,
                    'treatments' => $risk->treatments,
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | HIGH RISK
        |--------------------------------------------------------------------------
        */

        $highRiskCount = RiskAssessment::whereIn('level', [
            'tinggi',
            'sangat_tinggi',
            'ekstrem',
        ])->count();

        /*
        |--------------------------------------------------------------------------
        | CRITICAL / EXTREME
        |--------------------------------------------------------------------------
        */

        $extremeRiskCount = RiskAssessment::where(
            'level',
            'ekstrem'
        )->count();

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' => 'Data dashboard admin berhasil diambil.',

            'data' => [
                'summary' => [
                    'total_assets' => $totalAssets,
                    'total_threats' => $totalThreats,
                    'total_vulnerabilities' => $totalVulnerabilities,
                    'total_risks' => $totalRisks,
                    'high_risks' => $highRiskCount,
                    'extreme_risks' => $extremeRiskCount,
                ],

                'risk_levels' => $riskLevels,

                'treatment_status' => $treatmentStatus,

                'bidang_monitoring' => $bidangMonitoring,

                'recent_risks' => $recentRisks,
            ],
        ]);
    }
}

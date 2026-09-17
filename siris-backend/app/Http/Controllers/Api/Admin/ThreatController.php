<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Threat;
use App\Models\Vulnerability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ThreatController extends Controller
{
    public function index(Request $request)
    {
        $query = Threat::with([
            'asset',
            'dibuatOleh',
            'vulnerabilities',
        ]);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('kode_ancaman', 'like', "%{$search}%")
                  ->orWhere('nama_ancaman', 'like', "%{$search}%");
            });
        }

        $threats = $query
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $threats,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_ancaman' => [
                'required',
                'string',
                'max:100',
                'unique:threats,kode_ancaman',
            ],
            'nama_ancaman' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'asset_id' => ['nullable', 'exists:assets,id'],
            'is_active' => ['sometimes', 'boolean'],

            'vulnerability_ids' => ['nullable', 'array'],
            'vulnerability_ids.*' => [
                'integer',
                'exists:vulnerabilities,id',
            ],

            'new_vulnerabilities' => ['nullable', 'array'],
            'new_vulnerabilities.*.nama_kerentanan' => [
                'required',
                'string',
                'max:255',
            ],
            'new_vulnerabilities.*.deskripsi' => [
                'nullable',
                'string',
            ],
        ]);

        $threat = DB::transaction(function () use ($validated, $request) {

            $threat = Threat::create([
                'kode_ancaman' => $validated['kode_ancaman'],
                'nama_ancaman' => $validated['nama_ancaman'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'asset_id' => $validated['asset_id'] ?? null,
                'dibuat_oleh' => $request->user()->id,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $vulnerabilityIds = $validated['vulnerability_ids'] ?? [];

            /*
             * Vulnerability yang dibuat langsung dari form Threat
             */
            foreach ($validated['new_vulnerabilities'] ?? [] as $newVulnerability) {

                $vulnerability = Vulnerability::create([
                    'nama_kerentanan' => $newVulnerability['nama_kerentanan'],
                    'deskripsi' => $newVulnerability['deskripsi'] ?? null,
                    'is_active' => true,
                ]);

                $vulnerabilityIds[] = $vulnerability->id;
            }

            if (!empty($vulnerabilityIds)) {
                $threat->vulnerabilities()->sync(
                    array_unique($vulnerabilityIds)
                );
            }

            return $threat;
        });

        $threat->load([
            'asset',
            'dibuatOleh',
            'vulnerabilities',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Threat berhasil dibuat.',
            'data' => $threat,
        ], 201);
    }

    public function show(Threat $threat)
    {
        $threat->load([
            'asset',
            'dibuatOleh',
            'vulnerabilities',
            'riskAssessments',
        ]);

        return response()->json([
            'success' => true,
            'data' => $threat,
        ]);
    }

    public function update(Request $request, Threat $threat)
    {
        $validated = $request->validate([
            'kode_ancaman' => [
                'required',
                'string',
                'max:100',
                Rule::unique('threats', 'kode_ancaman')
                    ->ignore($threat->id),
            ],
            'nama_ancaman' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'asset_id' => ['nullable', 'exists:assets,id'],
            'is_active' => ['sometimes', 'boolean'],

            'vulnerability_ids' => ['nullable', 'array'],
            'vulnerability_ids.*' => [
                'integer',
                'exists:vulnerabilities,id',
            ],

            'new_vulnerabilities' => ['nullable', 'array'],
            'new_vulnerabilities.*.nama_kerentanan' => [
                'required',
                'string',
                'max:255',
            ],
            'new_vulnerabilities.*.deskripsi' => [
                'nullable',
                'string',
            ],
        ]);

        DB::transaction(function () use (
            $validated,
            $threat
        ) {

            $threat->update([
                'kode_ancaman' => $validated['kode_ancaman'],
                'nama_ancaman' => $validated['nama_ancaman'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'asset_id' => $validated['asset_id'] ?? null,
                'is_active' => $validated['is_active'] ?? $threat->is_active,
            ]);

            $vulnerabilityIds = $validated['vulnerability_ids'] ?? [];

            foreach ($validated['new_vulnerabilities'] ?? [] as $newVulnerability) {

                $vulnerability = Vulnerability::create([
                    'nama_kerentanan' => $newVulnerability['nama_kerentanan'],
                    'deskripsi' => $newVulnerability['deskripsi'] ?? null,
                    'is_active' => true,
                ]);

                $vulnerabilityIds[] = $vulnerability->id;
            }

            /*
             * sync() otomatis:
             * - menambahkan vulnerability baru
             * - mempertahankan yang masih dipilih
             * - menghapus yang tidak dipilih
             */
            $threat->vulnerabilities()->sync(
                array_unique($vulnerabilityIds)
            );
        });

        $threat->load([
            'asset',
            'dibuatOleh',
            'vulnerabilities',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Threat berhasil diperbarui.',
            'data' => $threat,
        ]);
    }

    public function destroy(Threat $threat)
    {
        $threat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Threat berhasil dihapus.',
        ]);
    }
}
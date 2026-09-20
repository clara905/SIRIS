<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Satker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SatkerController extends Controller
{
    public function index(): JsonResponse
    {
        $satkers = Satker::with('bidang')
            ->withCount([
                'users',
                'assets',
            ])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $satkers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bidang_id' => ['nullable', 'exists:bidangs,id'],
            'kode_satker' => ['required', 'string', 'max:100', 'unique:satkers,kode_satker'],
            'nama_satker' => ['required', 'string', 'max:255'],
        ]);

        $satker = Satker::create($validated);

        $satker->load('bidang');

        return response()->json([
            'success' => true,
            'message' => 'Satker berhasil dibuat.',
            'data' => $satker,
        ], 201);
    }

    public function show(Satker $satker): JsonResponse
    {
        $satker->load([
            'bidang',
            'users',
            'assets',
        ]);

        return response()->json([
            'success' => true,
            'data' => $satker,
        ]);
    }

    public function update(Request $request, Satker $satker): JsonResponse
    {
        $validated = $request->validate([
            'bidang_id' => ['nullable', 'exists:bidangs,id'],
            'kode_satker' => [
                'required',
                'string',
                'max:100',
                Rule::unique('satkers', 'kode_satker')
                    ->ignore($satker->id),
            ],
            'nama_satker' => ['required', 'string', 'max:255'],
        ]);

        $satker->update($validated);

        $satker->load('bidang');

        return response()->json([
            'success' => true,
            'message' => 'Satker berhasil diperbarui.',
            'data' => $satker,
        ]);
    }

    public function destroy(Satker $satker): JsonResponse
    {
        if ($satker->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Satker tidak dapat dihapus karena masih memiliki user.',
            ], 422);
        }

        $satker->delete();

        return response()->json([
            'success' => true,
            'message' => 'Satker berhasil dihapus.',
        ]);
    }
}

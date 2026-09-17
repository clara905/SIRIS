<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::with([
            'bidang',
            'subBidang',
            'satker',
        ]);

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('bidang_id')) {
            $query->where('bidang_id', $request->bidang_id);
        }

        if ($request->filled('satker_id')) {
            $query->where('satker_id', $request->satker_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('kode_aset', 'like', "%{$search}%")
                  ->orWhere('nama_aset', 'like', "%{$search}%");
            });
        }

        $assets = $query
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $assets,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_aset' => ['required', 'string', 'max:100', 'unique:assets,kode_aset'],
            'nama_aset' => ['required', 'string', 'max:255'],
            'kategori' => [
                'required',
                Rule::in(['physical', 'software', 'digital']),
            ],
            'bidang_id' => ['nullable', 'exists:bidangs,id'],
            'sub_bidang_id' => ['nullable', 'exists:sub_bidangs,id'],
            'satker_id' => ['nullable', 'exists:satkers,id'],
            'deskripsi' => ['nullable', 'string'],
            'nilai_kekritisan' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $asset = Asset::create($validated);

        $asset->load([
            'bidang',
            'subBidang',
            'satker',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asset berhasil dibuat.',
            'data' => $asset,
        ], 201);
    }

    public function show(Asset $asset)
    {
        $asset->load([
            'bidang',
            'subBidang',
            'satker',
            'threats',
        ]);

        return response()->json([
            'success' => true,
            'data' => $asset,
        ]);
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'kode_aset' => [
                'required',
                'string',
                'max:100',
                Rule::unique('assets', 'kode_aset')->ignore($asset->id),
            ],
            'nama_aset' => ['required', 'string', 'max:255'],
            'kategori' => [
                'required',
                Rule::in(['physical', 'software', 'digital']),
            ],
            'bidang_id' => ['nullable', 'exists:bidangs,id'],
            'sub_bidang_id' => ['nullable', 'exists:sub_bidangs,id'],
            'satker_id' => ['nullable', 'exists:satkers,id'],
            'deskripsi' => ['nullable', 'string'],
            'nilai_kekritisan' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $asset->update($validated);

        $asset->load([
            'bidang',
            'subBidang',
            'satker',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asset berhasil diperbarui.',
            'data' => $asset,
        ]);
    }

    public function destroy(Asset $asset)
    {
        if ($asset->threats()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Asset tidak dapat dihapus karena masih memiliki threat.',
            ], 422);
        }

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset berhasil dihapus.',
        ]);
    }
}
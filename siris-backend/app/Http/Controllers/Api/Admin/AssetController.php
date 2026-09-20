<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssetRequest;
use App\Models\Asset;
use Illuminate\Http\Request;

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
                    ->orWhere('nama_aset', 'like', "%{$search}%")
                    ->orWhere('snumber', 'like', "%{$search}%")
                    ->orWhere('merk', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%");
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

    public function store(AssetRequest $request)
    {
        $validated = $request->assetAttributes();

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

    public function update(AssetRequest $request, Asset $asset)
    {
        $validated = $request->assetAttributes();

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

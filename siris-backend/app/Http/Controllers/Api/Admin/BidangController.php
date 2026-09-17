<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use Illuminate\Http\Request;

class BidangController extends Controller
{
    public function index()
    {
        $bidangs = Bidang::withCount([
            'subBidangs',
            'users',
            'assets',
        ])
        ->latest()
        ->get();

        return response()->json([
            'success' => true,
            'data' => $bidangs,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_bidang' => ['required', 'string', 'max:255'],
        ]);

        $bidang = Bidang::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bidang berhasil dibuat.',
            'data' => $bidang,
        ], 201);
    }

    public function show(Bidang $bidang)
    {
        $bidang->load([
            'subBidangs',
            'satkers',
        ]);

        return response()->json([
            'success' => true,
            'data' => $bidang,
        ]);
    }

    public function update(Request $request, Bidang $bidang)
    {
        $validated = $request->validate([
            'nama_bidang' => ['required', 'string', 'max:255'],
        ]);

        $bidang->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bidang berhasil diperbarui.',
            'data' => $bidang,
        ]);
    }

    public function destroy(Bidang $bidang)
    {
        if ($bidang->subBidangs()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Bidang tidak dapat dihapus karena masih memiliki Sub Bidang.',
            ], 422);
        }

        $bidang->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bidang berhasil dihapus.',
        ]);
    }
}
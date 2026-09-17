<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubBidang;
use Illuminate\Http\Request;

class SubBidangController extends Controller
{
    public function index()
    {
        $subBidangs = SubBidang::with('bidang')
            ->withCount(['users', 'assets'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subBidangs,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bidang_id' => ['required', 'exists:bidangs,id'],
            'nama_sub_bidang' => ['required', 'string', 'max:255'],
        ]);

        $subBidang = SubBidang::create($validated);

        $subBidang->load('bidang');

        return response()->json([
            'success' => true,
            'message' => 'Sub Bidang berhasil dibuat.',
            'data' => $subBidang,
        ], 201);
    }

    public function show(SubBidang $subBidang)
    {
        $subBidang->load([
            'bidang',
            'users',
            'assets',
        ]);

        return response()->json([
            'success' => true,
            'data' => $subBidang,
        ]);
    }

    public function update(Request $request, SubBidang $subBidang)
    {
        $validated = $request->validate([
            'bidang_id' => ['required', 'exists:bidangs,id'],
            'nama_sub_bidang' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $subBidang->update($validated);

        $subBidang->load('bidang');

        return response()->json([
            'success' => true,
            'message' => 'Sub Bidang berhasil diperbarui.',
            'data' => $subBidang,
        ]);
    }

    public function destroy(SubBidang $subBidang)
    {
        if ($subBidang->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Sub Bidang tidak dapat dihapus karena masih digunakan user.',
            ], 422);
        }

        $subBidang->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sub Bidang berhasil dihapus.',
        ]);
    }
}
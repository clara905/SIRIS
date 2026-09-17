<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with([
            'role',
            'bidang',
            'subBidang',
            'satker',
        ])
        ->latest()
        ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'bidang_id' => ['nullable', 'exists:bidangs,id'],
            'sub_bidang_id' => ['nullable', 'exists:sub_bidangs,id'],
            'satker_id' => ['nullable', 'exists:satkers,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        $user->load([
            'role',
            'bidang',
            'subBidang',
            'satker',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat.',
            'data' => $user,
        ], 201);
    }

    public function show(User $user)
    {
        $user->load([
            'role',
            'bidang',
            'subBidang',
            'satker',
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'bidang_id' => ['nullable', 'exists:bidangs,id'],
            'sub_bidang_id' => ['nullable', 'exists:sub_bidangs,id'],
            'satker_id' => ['nullable', 'exists:satkers,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        $user->load([
            'role',
            'bidang',
            'subBidang',
            'satker',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diperbarui.',
            'data' => $user,
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin yang sedang login tidak dapat dihapus.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus.',
        ]);
    }
}